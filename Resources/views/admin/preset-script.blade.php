<script>
    (function() {
        const presets = @json($presets);
        const select = document.querySelector('[name="privilege_preset"]');
        if (!select) return;

        const container = select.closest('.fs-container') || select;

        function onPresetChange(value) {
            if (!value || !presets[value]) return;

            const textarea = document.querySelector('[name="privilege_names"]');
            if (!textarea) return;

            textarea.value = presets[value];
            textarea.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        container.addEventListener('fs:change', function(e) {
            onPresetChange(e.detail?.value || select.value);
        });

        select.addEventListener('change', function() {
            onPresetChange(this.value);
        });
    })();
</script>
