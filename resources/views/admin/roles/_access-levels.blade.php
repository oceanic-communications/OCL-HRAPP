@php
    $assigned = $assignedSlugs ?? [];
@endphp

@foreach ($accessLevels as $level)
    <div class="portal-card overflow-hidden">
        <div class="border-b border-border bg-muted/30 px-4 py-3">
            <h3 class="text-sm font-semibold text-foreground">
                {{ $level['label'] }}
                @if ($level['subtitle'])
                    <span class="font-normal text-muted-foreground">({{ $level['subtitle'] }})</span>
                @endif
            </h3>
        </div>
        <ul class="divide-y divide-border">
            @foreach ($level['capabilities'] as $capability)
                @php
                    $permissionId = $permissionIdsBySlug[$capability['slug']] ?? null;
                @endphp
                @if ($permissionId)
                    <li class="px-4 py-3">
                        <label class="flex cursor-pointer items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permissionId }}"
                                class="h-4 w-4 rounded border-border text-primary"
                                @checked(\App\Support\PortalPermissions::isGranted($capability['slug'], $assigned))
                                @disabled($readOnly ?? false)
                            />
                            <span class="font-medium text-foreground">{{ $capability['label'] }}</span>
                        </label>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
@endforeach
