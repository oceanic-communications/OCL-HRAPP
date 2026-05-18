<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InductionPolicy extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

        return $this->versions()->create([
            'version_label' => 'Current',
            'published_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }
}
