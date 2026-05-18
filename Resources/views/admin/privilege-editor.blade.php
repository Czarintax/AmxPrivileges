@php
    $flagsList = [];
    foreach (range('a', 'v') as $letter) {
        $flagsList[$letter] = __('amxprivileges.flag_descriptions.' . $letter);
    }
    $flagsList['y'] = __('amxprivileges.flag_descriptions.y');
    $flagsList['z'] = __('amxprivileges.flag_descriptions.z');

    $initialRules = [];
    foreach (preg_split('/\r?\n/', (string) $privilegeNames) as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '=')) continue;
        [$flagPart, $namePart] = explode('=', $line, 2);
        $flagPart = preg_replace('/[^a-z]/i', '', trim($flagPart));
        $namePart = trim($namePart);
        if ($flagPart === '' || $namePart === '') continue;
        $initialRules[] = ['flags' => strtolower($flagPart), 'name' => $namePart];
    }

    if (empty($initialRules)) {
        $initialRules[] = ['flags' => '', 'name' => ''];
    }

    $emptyRule = ['flags' => '', 'name' => ''];
@endphp

<input type="hidden" name="privilege_names" id="amxpriv-rules-raw" value="{{ $privilegeNames }}">

<div class="amxpriv-editor"
    data-amxpriv-editor
    data-raw-id="amxpriv-rules-raw"
    data-presets='@json(collect($presets)->all())'>
    <div class="amxpriv-editor__toolbar">
        <div class="amxpriv-editor__presets">
            <span class="amxpriv-editor__presets-label">{{ __('amxprivileges.admin.labels.privilege_preset') }}:</span>
            @foreach ($presets as $key => $_)
                <x-button type="outline-accent" size="tiny" data-preset="{{ $key }}">
                    {{ __('amxprivileges.admin.presets.' . $key) }}
                </x-button>
            @endforeach
        </div>
        <x-button type="outline-accent" size="small" icon="ph.bold.plus-bold" data-amxpriv-add>
            {{ __('amxprivileges.admin.editor.add_rule') }}
        </x-button>
    </div>

    <div class="amxpriv-editor__rules" data-amxpriv-rules>
        @foreach ($initialRules as $rule)
            @include('amxprivileges::admin.partials.privilege-rule', ['rule' => $rule, 'flagsList' => $flagsList])
        @endforeach
    </div>

    <template data-amxpriv-template>
        @include('amxprivileges::admin.partials.privilege-rule', ['rule' => $emptyRule, 'flagsList' => $flagsList])
    </template>

    <p class="amxpriv-editor__hint">{{ __('amxprivileges.admin.smalls.privilege_names') }}</p>
</div>
