<span {{ $attributes->merge(['class' => 'inline-flex rounded-md bg-[#2F8F6B]/15 px-2 py-0.5 text-xs font-medium text-[#2F8F6B]']) }}>
    {{ $slot->isEmpty() ? 'Active' : $slot }}
</span>
