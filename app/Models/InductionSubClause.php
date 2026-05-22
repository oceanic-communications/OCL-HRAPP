<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InductionSubClause extends Model
{
    public const BODY_MAX_WORDS = 3000;

    protected $fillable = [
        'induction_section_id',
        'sort_order',
        'title',
        'body',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<InductionSubClause>  $query
     * @return Builder<InductionSubClause>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @return BelongsTo<InductionSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(InductionSection::class, 'induction_section_id');
    }
}
