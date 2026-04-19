@php
    $flags = $rule['flags'] ?? '';
    $name = $rule['name'] ?? '';
    $active = array_flip(str_split($flags));
@endphp

<div class="amxpriv-rule" data-amxpriv-rule>
    <div class="amxpriv-rule__main">
        <div class="amxpriv-rule__flags">
            @foreach ($flagsList as $letter => $desc)
                <span @class(['badge', 'amxpriv-rule__flag', 'accent' => isset($active[$letter])])
                    data-flag="{{ $letter }}"
                    data-tooltip="{{ $letter }} — {{ $desc }}"
                    role="button" tabindex="0">{{ $letter }}</span>
            @endforeach
        </div>
        <div class="input-wrapper amxpriv-rule__name-wrap">
            <div class="input__field-container">
                <input type="text" class="input__field amxpriv-rule__name"
                    value="{{ $name }}"
                    placeholder="{{ __('amxprivileges.admin.editor.name_placeholder') }}">
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-outline-error btn-tiny amxpriv-rule__remove"
        data-amxpriv-remove
        data-tooltip="{{ __('amxprivileges.admin.editor.remove') }}">
        <x-icon class="me-1" path="ph.bold.trash-bold" />
        <span class="btn-label visually-hidden">{{ __('amxprivileges.admin.editor.remove') }}</span>
    </button>
</div>
