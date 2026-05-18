<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'slug',
        'module_code',
        'resource_code',
        'action',
    ];

    public function roleTemplates(): BelongsToMany
    {
        return $this->belongsToMany(RoleTemplate::class, 'permission_role_template');
    }
}
