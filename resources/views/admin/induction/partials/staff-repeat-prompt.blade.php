@php
    $staffOld = old('staff_must_repeat_induction');
@endphp
<fieldset class="mt-4 rounded-lg border border-border bg-muted/20 p-4">
    <legend class="px-1 text-sm font-semibold text-foreground">Effect on staff</legend>
    <p class="mb-3 text-xs text-muted-foreground">Choose what should happen after this change is saved. This choice is recorded in the audit log.</p>
    <div class="space-y-3 text-sm text-foreground">
        <label class="flex cursor-pointer gap-2">
            <input type="radio" name="staff_must_repeat_induction" value="1" class="mt-1 h-4 w-4 border-border" @checked($staffOld === '1') required>
            <span><span class="font-semibold">Yes</span> — staff must complete this published induction again where it applies: progress for this version is reset and every active account receives a dashboard notification.</span>
        </label>
        <label class="flex cursor-pointer gap-2">
            <input type="radio" name="staff_must_repeat_induction" value="0" class="mt-1 h-4 w-4 border-border" @checked($staffOld === '0') required>
            <span><span class="font-semibold">No</span> — administrative update only. No dashboard notification and no reset of completed sections (still fully audited).</span>
        </label>
    </div>
    @error('staff_must_repeat_induction')
        <p class="mt-2 text-xs text-destructive">{{ $message }}</p>
    @enderror
</fieldset>
