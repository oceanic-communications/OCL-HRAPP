<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionSection extends Model
{
    public const BODY_MAX_WORDS = 3000;

    protected $fillable = [
        'induction_policy_version_id',
        'sort_order',
        'title',
        'body',
        'requires_signature',
        'acknowledgement_hint',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_signature' => 'boolean',
            'sort_order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<InductionSection>  $query
     * @return Builder<InductionSection>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
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
    public function completions(): HasMany
    {
        return $this->hasMany(InductionSectionCompletion::class);
    }
}
