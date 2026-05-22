<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalUserNotification extends Model
{
    public const TYPE_INDUCTION_REPEAT = 'induction_repeat_required';

    public const TYPE_INDUCTION_POLICY_CHANGED = 'induction_policy_changed';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'action_url',
        'induction_policy_version_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<InductionPolicyVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(InductionPolicyVersion::class, 'induction_policy_version_id');
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
