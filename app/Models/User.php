<?php

namespace App\Models;

use App\Support\PortalPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'email',
        'password',
        'is_staff_super_user',
        'archived_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff_super_user' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * @return HasMany<PortalUserNotification, $this>
     */
    public function portalNotifications(): HasMany
    {
        return $this->hasMany(PortalUserNotification::class);
    }

    /**
     * @var Collection<int, string>|null
     */
    protected ?Collection $resolvedPermissionSlugsCache = null;

    /**
     * @return Collection<int, string>
     */
    public function resolvedPermissionSlugs(): Collection
    {
        if ($this->is_staff_super_user) {
            return collect();
        }

        if ($this->resolvedPermissionSlugsCache !== null) {
            return $this->resolvedPermissionSlugsCache;
        }

        $slugs = $this->roles()
            ->with('roleTemplate.permissions')
            ->orderBy('roles.id')
            ->get()
            ->flatMap(function (Role $role) {
                return $role->roleTemplate?->permissions->pluck('slug') ?? collect();
            })
            ->unique()
            ->values();

        return $this->resolvedPermissionSlugsCache = $slugs;
    }

    public function flushResolvedPermissionSlugs(): void
    {
        $this->resolvedPermissionSlugsCache = null;
    }

    public function getNameAttribute(): string
    {
        $parts = array_filter(
            [$this->title, $this->first_name, $this->last_name],
            fn (?string $v) => $v !== null && $v !== '',
        );

        return implode(' ', $parts) ?: 'User';
    }

    public function isStaffSuperUser(): bool
    {
        return $this->is_staff_super_user === true;
    }

    /**
     * @deprecated Use isStaffSuperUser()
     */
    public function isAdmin(): bool
    {
        return $this->isStaffSuperUser();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function homeRoute(): string
    {
        return route('dashboard');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->is_staff_super_user) {
            return true;
        }

        $have = $this->resolvedPermissionSlugs();

        foreach (PortalPermissions::grantingSlugsFor($slug) as $grantingSlug) {
            if ($have->contains($grantingSlug)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(string ...$slugs): bool
    {
        if ($this->is_staff_super_user) {
            return true;
        }

        if ($slugs === []) {
            return false;
        }

        $have = $this->resolvedPermissionSlugs();

        foreach ($slugs as $slug) {
            if ($have->contains($slug)) {
                return true;
            }
        }

        return false;
    }

    public function assignRoleByTemplateSlug(string $templateSlug): void
    {
        $role = Role::query()
            ->whereHas('roleTemplate', fn (Builder $q) => $q->where('slug', $templateSlug))
            ->firstOrFail();

        $this->roles()->sync([$role->id]);
        $this->flushResolvedPermissionSlugs();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
