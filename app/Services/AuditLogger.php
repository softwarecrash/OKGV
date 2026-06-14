<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * Store a security-relevant action without exposing sensitive payloads.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        string $action,
        ?Authenticatable $actor = null,
        ?Model $subject = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
