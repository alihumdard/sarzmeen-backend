<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function properties(Request $request): JsonResponse|StreamedResponse
    {
        $query = Property::with(['propertyType', 'location', 'owner'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->input('purpose')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->input('to')))
            ->latest();

        if ($request->input('format') === 'csv') {
            return $this->streamCsv(
                'properties-report.csv',
                ['ID', 'Reference', 'Title', 'Purpose', 'Status', 'Price', 'Type', 'Location', 'Views', 'Owner', 'Created'],
                $query,
                fn (Property $p) => [
                    $p->id,
                    $p->reference,
                    $p->title,
                    $p->purpose->value,
                    $p->status->value,
                    $p->price,
                    $p->propertyType->name,
                    $p->full_location,
                    $p->views,
                    $p->owner?->name ?? '',
                    $p->created_at->toDateString(),
                ],
            );
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 25)),
        ]);
    }

    public function inquiries(Request $request): JsonResponse|StreamedResponse
    {
        $query = Inquiry::with(['property', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->input('to')))
            ->latest();

        if ($request->input('format') === 'csv') {
            return $this->streamCsv(
                'inquiries-report.csv',
                ['ID', 'Name', 'Email', 'Phone', 'Status', 'Property', 'Source', 'Created'],
                $query,
                fn (Inquiry $i) => [
                    $i->id,
                    $i->name,
                    $i->email,
                    $i->phone,
                    $i->status->value,
                    $i->property?->title ?? '',
                    $i->source ?? '',
                    $i->created_at->toDateString(),
                ],
            );
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 25)),
        ]);
    }

    public function users(Request $request): JsonResponse|StreamedResponse
    {
        $query = User::withCount(['conversations'])
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->input('to')))
            ->latest();

        if ($request->input('format') === 'csv') {
            return $this->streamCsv(
                'users-report.csv',
                ['ID', 'Name', 'Email', 'Phone', 'Status', 'Role', 'Conversations', 'Registered'],
                $query,
                fn (User $u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->phone ?? '',
                    $u->status->value,
                    $u->roles->first()?->name ?? 'user',
                    $u->conversations_count,
                    $u->created_at->toDateString(),
                ],
            );
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 25)),
        ]);
    }

    private function streamCsv(string $filename, array $headers, $query, \Closure $rowMapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $query, $rowMapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $query->chunk(500, function ($records) use ($handle, $rowMapper) {
                foreach ($records as $record) {
                    fputcsv($handle, $rowMapper($record));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
