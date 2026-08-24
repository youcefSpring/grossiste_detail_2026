<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Writes a trail entry whenever the model changes.
 * Deliberately quiet: no entry without a logged-in user, and noisy columns are skipped.
 */
trait RecordsActivity
{
    protected static array $auditIgnored = [
        'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
    ];

    public static function bootRecordsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created', [], $model->auditableValues()));

        static::updated(function ($model) {
            $changes = collect($model->getChanges())
                ->except(static::$auditIgnored)
                ->all();

            if (! $changes) {
                return;
            }

            $before = collect($model->getOriginal())->only(array_keys($changes))->all();

            $model->recordActivity('updated', $before, $changes);
        });

        static::deleted(fn ($model) => $model->recordActivity('deleted', $model->auditableValues(), []));
    }

    public function recordActivity(string $action, array $old, array $new): void
    {
        if (! auth()->hasUser()) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'label' => $this->auditLabel(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** What a person calls this record. */
    public function auditLabel(): ?string
    {
        foreach (['invoice_number', 'reference', 'name'] as $attribute) {
            if (! empty($this->{$attribute})) {
                return (string) $this->{$attribute};
            }
        }

        return null;
    }

    protected function auditableValues(): array
    {
        return collect($this->getAttributes())->except(static::$auditIgnored)->all();
    }
}
