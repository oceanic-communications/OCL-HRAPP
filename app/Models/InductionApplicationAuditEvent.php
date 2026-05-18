<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only compliance trail for policy application (employee-facing induction behaviour).
 */
class InductionApplicationAuditEvent extends Model
{
    public $timestamps = false;

    protected $table = 'induction_application_audit_events';

    protected $fillable = [
        'occurred_at',
        'user_id',
        'event_code',
        'induction_policy_id',
        'induction_policy_version_id',
        'induction_section_id',
        'induction_enrollment_id',
        'induction_section_completion_id',
        'portal_user_notification_id',
        'actor_user_id',
        'ip_address',
        'user_agent',
        'correlation_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static fn () => false);
        static::deleting(static fn () => false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
