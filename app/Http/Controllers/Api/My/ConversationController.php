<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants', 'latestMessage', 'property'])
            ->latest('updated_at')
            ->paginate(20);

        return ConversationResource::collection($conversations);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->participants()->where('user_id', $user->id)->exists(),
            403,
        );

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->paginate(50);

        return response()->json([
            'conversation' => new ConversationResource($conversation->load(['participants', 'property'])),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipientId' => 'required|exists:users,id',
            'propertyId' => 'nullable|exists:properties,id',
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        $recipientId = (int) $validated['recipientId'];

        abort_if($recipientId === $user->id, 422, 'Cannot message yourself');

        $conversation = DB::transaction(function () use ($user, $validated, $recipientId) {
            $existing = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $recipientId))
                ->when($validated['propertyId'] ?? null, fn ($q, $pid) => $q->where('property_id', $pid))
                ->first();

            if ($existing) {
                $message = $existing->messages()->create([
                    'sender_id' => $user->id,
                    'body' => $validated['message'],
                ]);

                $existing->touch();

                return $existing;
            }

            $conversation = Conversation::create([
                'property_id' => $validated['propertyId'] ?? null,
                'subject' => '',
            ]);

            $conversation->participants()->attach([$user->id, $recipientId]);

            $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $validated['message'],
            ]);

            return $conversation;
        });

        $latestMessage = $conversation->messages()->latest()->first();
        $latestMessage->load('sender');

        $recipient = User::find($recipientId);
        $recipient->notify(new NewMessageNotification($latestMessage));

        $conversation->load(['participants', 'latestMessage', 'property']);

        return (new ConversationResource($conversation))
            ->response()
            ->setStatusCode(201);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->participants()->where('user_id', $user->id)->exists(),
            403,
        );

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $this->imageService->upload(
                $request->file('attachment'),
                "messages/{$conversation->id}",
                1200,
            );
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['message'],
            'attachment' => $attachmentPath,
        ]);

        $conversation->touch();

        $message->load('sender');

        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->get();

        foreach ($otherParticipants as $participant) {
            $participant->notify(new NewMessageNotification($message));
        }

        return (new MessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    public function totalUnread(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with('participants')
            ->get();

        $total = $conversations->sum(fn ($c) => $c->unreadCountFor($user->id));

        return response()->json([
            'data' => ['count' => $total],
        ]);
    }
}
