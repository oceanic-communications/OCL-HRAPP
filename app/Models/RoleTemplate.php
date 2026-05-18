<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleTemplate extends Model
{
    public const SLUG_EMPLOYEE = 'employee';

    public const SLUG_MANAGER = 'manager';

    /** HR / Admin — full staff user admin + induction policy management. */
    public const SLUG_HR_ADMIN = 'hr_admin';

    public const SLUG_DIRECTOR_GM = 'director_gm';

    public const AUDIENCE_STAFF = 'staff';

    protected $fillable = [
        'slug',
        'name',
        'audience',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role_template');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
