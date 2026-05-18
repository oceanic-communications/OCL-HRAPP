<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InductionSectionCompletion extends Model
{
    protected $fillable = [
        'induction_enrollment_id',
        'induction_section_id',
        'completed_at',
        'employee_name_snapshot',
        'policy_version_label_snapshot',
        'ip_address',
        'user_agent',
        'signature_disk',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InductionEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(InductionEnrollment::class, 'induction_enrollment_id');
    }

    /**
     * @return BelongsTo<InductionSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(InductionSection::class, 'induction_section_id');
    }
}
