<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionPolicyVersion extends Model
{
    protected $fillable = [
        'induction_policy_id',
        'version_label',
        'effective_date',
        'policy_pdf_disk',
        'policy_pdf_path',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InductionPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InductionPolicy::class, 'induction_policy_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InductionSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(InductionSection::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<InductionPolicyVersion>  $query
     * @return Builder<InductionPolicyVersion>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
