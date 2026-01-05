<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Log an audit event
     *
     * @param string $action The action being performed (e.g., 'enrollment_created', 'payment_paid')
     * @param Model $subject The model being acted upon
     * @param array $meta Additional metadata to store
     * @param int|null $branchId Branch ID related to the action
     * @param int|null $userId User ID related to the action (e.g., student/customer ID)
     * @return AuditLog
     */
    public function log(
        string $action,
        Model $subject,
        array $meta = [],
        ?int $branchId = null,
        ?int $userId = null
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'branch_id' => $branchId ?? $subject->branch_id ?? null,
            'user_id' => $userId,
            'meta_json' => $meta,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

