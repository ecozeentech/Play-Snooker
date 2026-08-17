<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records every administrative action (manual wallet adjustments, forced
 * bet/escrow settlements, user deactivation, ...) to a separate audit trail
 * that is never mixed with regular financial transaction records.
 */
class AuditLogService
{
    public function record(User $admin, string $action, ?Model $subject = null, array $before = [], array $after = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $admin->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
        ]);
    }
}
