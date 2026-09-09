<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    private const SENSITIVE_FIELDS = [
        'password', 'password_confirmation', 'remember_token', 'fingerprint_template',
        'pending_password', 'id_document_path',
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $field) {
            $original[$field] = $model->getOriginal($field);
        }

        $this->record($model, 'updated', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $model->getAttributes(), []);
    }

    private function record(Model $model, string $action, array $oldValues, array $newValues): void
    {
        $oldValues = $this->sanitize($oldValues);
        $newValues = $this->sanitize($newValues);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $this->deviceIdentifier(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    private function deviceIdentifier(): ?string
    {
        $request = request();

        foreach (['X-MAC-Address', 'X-Client-MAC', 'X-Device-MAC', 'X-Client-Device-MAC', 'X-Network-MAC'] as $header) {
            $value = $request->header($header);
            if (!empty($value)) {
                return trim((string) $value);
            }
        }

        $value = $request->header('X-Forwarded-For');
        if (!empty($value)) {
            return trim(explode(',', $value)[0]);
        }

        return $request->ip();
    }

    private function sanitize(array $values): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            unset($values[$field]);
        }

        return $values;
    }
}