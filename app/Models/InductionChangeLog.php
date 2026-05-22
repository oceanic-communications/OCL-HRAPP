<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InductionChangeLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'induction_policy_id',
        'induction_policy_version_id',
        'metadata',
        'changes',
        'staff_repeat_requested',
        'staff_repeat_applied',
        'ip_address',
        'user_agent',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'changes' => 'array',
            'staff_repeat_requested' => 'boolean',
            'staff_repeat_applied' => 'boolean',
            'correlation_id' => 'string',
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return BelongsTo<InductionPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InductionPolicy::class, 'induction_policy_id');
    }

    /**
     * @return BelongsTo<InductionPolicyVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(InductionPolicyVersion::class, 'induction_policy_version_id');
    }
}
