(function () {
    function init(root) {
        if (!root || root.dataset.amxprivInit) return;
        root.dataset.amxprivInit = '1';

        const rulesBox = root.querySelector('[data-amxpriv-rules]');
        const tpl = root.querySelector('[data-amxpriv-template]');
        const raw = document.getElementById(root.dataset.rawId || 'amxpriv-rules-raw');
        let presets = {};
        try { presets = JSON.parse(root.dataset.presets || '{}'); } catch (e) { presets = {}; }

        function serialize() {
            const lines = [];
            rulesBox.querySelectorAll('[data-amxpriv-rule]').forEach(rule => {
                const active = Array.from(rule.querySelectorAll('.amxpriv-rule__flag.accent'))
                    .map(b => b.dataset.flag).join('');
                const name = rule.querySelector('.amxpriv-rule__name').value.trim();
                if (active && name) lines.push(active + ' = ' + name);
            });
            raw.value = lines.join('\n');
        }

        function toggleFlag(btn) {
            btn.classList.toggle('accent');
            serialize();
        }

        function bindRule(node) {
            if (node.dataset.bound) return;
            node.dataset.bound = '1';
            node.querySelectorAll('.amxpriv-rule__flag').forEach(btn => {
                btn.addEventListener('click', () => toggleFlag(btn));
                btn.addEventListener('keydown', e => {
                    if (e.key === ' ' || e.key === 'Enter') {
                        e.preventDefault();
                        toggleFlag(btn);
                    }
                });
            });
            node.querySelector('.amxpriv-rule__name').addEventListener('input', serialize);
            node.querySelector('[data-amxpriv-remove]').addEventListener('click', () => {
                node.remove();
                serialize();
            });
        }

        function addRule(data) {
            const clone = tpl.content.firstElementChild.cloneNode(true);
            if (data && data.flags) {
                const active = new Set(data.flags.toLowerCase().split(''));
                clone.querySelectorAll('.amxpriv-rule__flag').forEach(btn => {
                    btn.classList.toggle('accent', active.has(btn.dataset.flag));
                });
            }
            if (data && data.name) {
                clone.querySelector('.amxpriv-rule__name').value = data.name;
            }
            rulesBox.appendChild(clone);
            bindRule(clone);
            serialize();
        }

        function clearRules() {
            while (rulesBox.firstChild) rulesBox.removeChild(rulesBox.firstChild);
        }

        function loadPreset(key) {
            const value = presets[key];
            if (!value) return;
            clearRules();
            value.split(/\r?\n/).forEach(line => {
                line = line.trim();
                if (!line || !line.includes('=')) return;
                const parts = line.split('=');
                const flags = parts[0].trim().replace(/[^a-z]/gi, '');
                const name = parts.slice(1).join('=').trim();
                addRule({ flags: flags, name: name });
            });
        }

        root.querySelectorAll('[data-preset]').forEach(btn => {
            btn.addEventListener('click', () => loadPreset(btn.dataset.preset));
        });
        root.querySelector('[data-amxpriv-add]').addEventListener('click', () => addRule());

        rulesBox.querySelectorAll('[data-amxpriv-rule]').forEach(bindRule);
        serialize();
    }

    function initAll() {
        document.querySelectorAll('[data-amxpriv-editor]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    document.addEventListener('htmx:afterSwap', initAll);
    document.addEventListener('yoyo:afterSwap', initAll);
})();
