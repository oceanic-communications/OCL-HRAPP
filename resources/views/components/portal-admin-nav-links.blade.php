@php
    $showAdmin = ($portalCap?->staffUserRead ?? false)
        || ($portalCap?->inductionPolicyManage ?? false)
        || (auth()->user()?->isStaffSuperUser() ?? false);
@endphp

@if ($showAdmin)
    @if ($portalCap?->staffUserRead ?? false)
        <a href="{{ route('admin.users.index') }}" class="{{ $class ?? 'text-foreground hover:text-primary' }}">Users</a>
    @endif
    @if ($portalCap?->inductionPolicyManage ?? false)
        <a href="{{ route('admin.induction.index') }}" class="{{ $class ?? 'text-foreground hover:text-primary' }}">Induction policies</a>
    @endif
    @if (auth()->user()?->isStaffSuperUser())
        <a href="{{ route('admin.role-templates.index') }}" class="{{ $class ?? 'text-foreground hover:text-primary' }}">Role templates</a>
    @endif
@endif
