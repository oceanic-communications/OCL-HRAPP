<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InductionSectionQuestionResponse extends Model
{
    protected $fillable = [
        'induction_section_completion_id',
        'induction_section_question_id',
        'response',
    ];

    /**
     * @return BelongsTo<InductionSectionCompletion, $this>
     */
    public function completion(): BelongsTo
    {
        return $this->belongsTo(InductionSectionCompletion::class, 'induction_section_completion_id');
    }

    /**
     * @return BelongsTo<InductionSectionQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(InductionSectionQuestion::class, 'induction_section_question_id');
    }
}
