<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionSection extends Model
{
    protected $fillable = [
        'induction_policy_version_id',
        'sort_order',
        'title',
        'body',
        'requires_signature',
        'acknowledgement_hint',
    ];

    protected function casts(): array
    {
        return [
            'requires_signature' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    /**
     * @return HasMany<InductionSectionQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(InductionSectionQuestion::class)->orderBy('sort_order');
    }
}
