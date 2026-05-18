<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionEnrollment extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'induction_policy_version_id',
        'status',
        'started_at',
        'completed_at',
        'completion_pdf_disk',
        'completion_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /**
     * @return HasMany<InductionSectionCompletion, $this>
     */
    public function sectionCompletions(): HasMany
    {
        return $this->hasMany(InductionSectionCompletion::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
