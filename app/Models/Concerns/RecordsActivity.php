<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Audit trail for the admin's Activity Logs screen.
 *
 * Only the fields named in each model's `$activityFields` are recorded, and
 * only when they actually change — an edit that touches nothing writes no
 * entry, which keeps the log readable instead of flooding it with noise.
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityFields ?? ['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::activityLogName())
            ->setDescriptionForEvent(fn (string $event) => $event);
    }

    /** Groups entries in the UI filter, e.g. "property", "project". */
    public static function activityLogName(): string
    {
        return str(class_basename(static::class))->snake()->toString();
    }

    /**
     * Shown in the log instead of a bare id, so a row reads
     * "Property · 5 Marla House in DHA" rather than "Property · 42".
     */
    public function activityTitle(): string
    {
        foreach (['title', 'name', 'question', 'label'] as $field) {
            if (! empty($this->{$field})) {
                return (string) $this->{$field};
            }
        }

        return '#'.$this->getKey();
    }
}
