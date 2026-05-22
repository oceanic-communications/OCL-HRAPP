<div class="hidden">
    <input type="hidden" name="scheme[section][style]" value="{{ $section['style'] ?? 'roman' }}">
    <input type="hidden" name="scheme[section][separator]" value="{{ $section['separator'] ?? '.' }}">
    <input type="hidden" name="scheme[section][start]" value="{{ $section['start'] ?? 'I' }}">
    <input type="hidden" name="scheme[clause][style]" value="{{ $clause['style'] ?? 'alpha_upper' }}">
    <input type="hidden" name="scheme[clause][separator]" value="{{ $clause['separator'] ?? '.' }}">
    <input type="hidden" name="scheme[clause][start]" value="{{ $clause['start'] ?? 'A' }}">
    <input type="hidden" name="scheme[clause][inherit_preview]" value="{{ $clause['inherit_preview'] ?? 'II.A' }}">
    <input type="hidden" name="scheme[sub_clause][style]" value="{{ $sub['style'] ?? 'decimal' }}">
    <input type="hidden" name="scheme[sub_clause][separator]" value="{{ $sub['separator'] ?? '.' }}">
    <input type="hidden" name="scheme[sub_clause][prefix]" value="{{ $sub['prefix'] ?? '' }}">
    <input type="hidden" name="scheme[sub_clause][start]" value="{{ $sub['start'] ?? '1' }}">
</div>
