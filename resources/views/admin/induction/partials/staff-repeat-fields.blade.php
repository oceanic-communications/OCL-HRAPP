@php
    $staffOld = old('staff_must_repeat_induction');
@endphp
<div class="space-y-3 text-sm text-foreground">
    <label class="flex cursor-pointer gap-2">
        <input type="radio" name="dialog_staff_effect" value="1" class="mt-1 h-4 w-4 border-border" @checked($staffOld === '1')>
        <span><span class="font-semibold">Yes</span> — staff must complete this published induction again: progress for this version is reset and every active account receives a dashboard notification and email.</span>
    </label>
    <label class="flex cursor-pointer gap-2">
        <input type="radio" name="dialog_staff_effect" value="0" class="mt-1 h-4 w-4 border-border" @checked($staffOld === '0')>
        <span><span class="font-semibold">No</span> — administrative update only. No dashboard notification and no reset of completed sections (still fully audited).</span>
    </label>
</div>
<p class="hidden text-sm text-destructive" data-induction-staff-error>Please choose how this change should affect staff.</p>
