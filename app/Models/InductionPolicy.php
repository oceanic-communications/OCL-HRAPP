<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionPolicy extends Model
{
    public const ABBREVIATION_MAX_LENGTH = 16;

    protected $fillable = [
        'name',
        'abbreviation',
        'slug',
        'is_active',
        'acknowledgement_mode',
        'numbering_scheme',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'numbering_scheme' => 'array',
        ];
    }

    /**
     * @return HasMany<InductionPolicyVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(InductionPolicyVersion::class);
    }

    public function publishedVersion(): ?InductionPolicyVersion
    {
        return $this->versions()
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->first();
    }

    public function ensureEditableVersion(): InductionPolicyVersion
    {
        $published = $this->publishedVersion();
        if ($published !== null) {
            return $published;
        }

        $latest = $this->versions()->orderByDesc('id')->first();
        if ($latest !== null) {
            if ($latest->published_at === null) {
                $latest->forceFill([
                    'published_at' => now(),
                    'created_by' => $latest->created_by ?? auth()->id(),
                ])->save();
            }

            return $latest;
        }

        return $this->versions()->create([
            'version_label' => 'Current',
            'published_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }
}
