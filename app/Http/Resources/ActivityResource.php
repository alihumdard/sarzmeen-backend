<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subject = $this->subject;

        return [
            'id' => (string) $this->id,
            'log' => $this->log_name,
            'logLabel' => Str::headline($this->log_name ?? ''),
            'event' => $this->event,
            'eventLabel' => Str::headline($this->event ?? $this->description),
            'causer' => $this->causer?->name ?? 'System',
            'causerId' => $this->causer?->id,
            'subjectType' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subjectId' => $this->subject_id,
            // Resolved where the record still exists; deleted rows fall back
            // to the id so the entry stays meaningful.
            'subjectTitle' => $subject && method_exists($subject, 'activityTitle')
                ? $subject->activityTitle()
                : ($this->subject_id ? '#'.$this->subject_id : null),
            'changes' => $this->formatChanges(),
            'properties' => $this->properties,
            'createdAt' => $this->created_at?->toIso8601String(),
            'date' => $this->created_at?->format('M d, Y h:i A'),
        ];
    }

    /**
     * Flattens spatie's {old, attributes} pair into one row per changed field,
     * which is what the table renders.
     */
    private function formatChanges(): array
    {
        $props = $this->properties ?? collect();
        $new = $props['attributes'] ?? [];
        $old = $props['old'] ?? [];

        if (empty($new)) {
            return [];
        }

        $changes = [];

        foreach ($new as $field => $value) {
            $changes[] = [
                'field' => Str::headline($field),
                'from' => $this->stringify($old[$field] ?? null),
                'to' => $this->stringify($value),
            ];
        }

        return $changes;
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
