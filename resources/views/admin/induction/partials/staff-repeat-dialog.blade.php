@props([
    'dialogId' => 'induction-staff-effect-dialog',
    'formId' => 'induction-section-edit-form',
])

<dialog
    id="{{ $dialogId }}"
    data-induction-staff-dialog
    data-induction-staff-form="{{ $formId }}"
    aria-labelledby="{{ $dialogId }}-title"
    class="fixed inset-0 z-[100] m-0 hidden w-full max-w-none border-0 bg-transparent p-4 backdrop:bg-black/50 open:flex open:items-center open:justify-center"
>
    <div class="portal-card w-full max-w-lg rounded-xl border border-border bg-background p-0 text-foreground shadow-xl">
        <div class="border-b border-border px-6 py-4">
            <h2 id="{{ $dialogId }}-title" class="text-lg font-semibold text-foreground">Effect on staff</h2>
            <p class="mt-1 text-sm text-muted-foreground">Choose what should happen after this change is saved. This choice is recorded in the audit log.</p>
        </div>
        <div class="space-y-4 px-6 py-5">
            @include('admin.induction.partials.staff-repeat-fields')
        </div>
        <div class="flex flex-col-reverse gap-2 border-t border-border px-6 py-4 sm:flex-row sm:justify-end sm:gap-3">
            <button type="button" class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted" data-induction-staff-cancel>
                Cancel
            </button>
            <button type="button" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90" data-induction-staff-confirm>
                Save changes
            </button>
        </div>
    </div>
</dialog>
