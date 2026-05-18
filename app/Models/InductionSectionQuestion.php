<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionSectionQuestion extends Model
{
    protected $fillable = [
        'induction_section_id',
        'sort_order',
        'prompt',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InductionSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(InductionSection::class, 'induction_section_id');
    }

    /**
     * @return HasMany<InductionSectionQuestionResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(InductionSectionQuestionResponse::class);
    }
}
