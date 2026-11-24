<script>
(function() {
    var open = document.getElementById(<?= json_encode($delete_form_id.'-open') ?>);
    var panel = document.getElementById(<?= json_encode($delete_form_id.'-panel') ?>);
    var form = document.getElementById(<?= json_encode($delete_form_id) ?>);
    var cancel = document.getElementById(<?= json_encode($delete_form_id.'-cancel') ?>);
    if ( open && panel ) {
        open.addEventListener('click', function() {
            panel.style.display = '';
            open.style.display = 'none';
            var first = panel.querySelector('input[type="text"]');
            if ( first ) first.focus();
        });
    }
    if ( cancel && open && panel ) {
        cancel.addEventListener('click', function() {
            var inputs = panel.querySelectorAll('input[type="text"]');
            for ( var i = 0; i < inputs.length; i++ ) inputs[i].value = '';
            var go = document.getElementById(<?= json_encode($delete_form_id.'-go') ?>);
            if ( go ) go.disabled = true;
            panel.style.display = 'none';
            open.style.display = '';
        });
    }
    if ( ! form || ! panel ) return;
    var spec = <?= json_encode($delete_spec, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
    var mismatch = <?= json_encode($delete_mismatch, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
    var confirmText = <?= json_encode($delete_confirm_text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;
    var button = document.getElementById(<?= json_encode($delete_form_id.'-go') ?>);
    var sent = false;
    function typedDomain(raw) {
        raw = String(raw || '').trim();
        raw = raw.replace(/^[a-z][a-z0-9+.-]*:\/\//i, '');
        raw = raw.split('/')[0];
        raw = raw.split(':')[0];
        return raw.toLowerCase();
    }
    function typedUrl(raw) {
        return String(raw || '').trim().replace(/\/+$/, '').toLowerCase();
    }
    function typedCount(raw) {
        raw = String(raw || '').trim().replace(/[,\s]/g, '');
        if ( ! /^\d+$/.test(raw) ) return null;
        return parseInt(raw, 10);
    }
    function fieldMatches(field) {
        var input = panel.querySelector('[name="'+field.name+'"]');
        var raw = input ? input.value : '';
        if ( field.kind === 'domain' ) {
            return field.expected !== '' && typedDomain(raw) === String(field.expected).toLowerCase();
        }
        if ( field.kind === 'url' ) {
            var expected = String(field.expected || '').replace(/\/+$/, '').toLowerCase();
            return expected !== '' && typedUrl(raw) === expected;
        }
        if ( field.kind === 'count' ) {
            return typedCount(raw) === field.expected;
        }
        return false;
    }
    function matches() {
        if ( ! spec.length ) return false;
        for ( var i = 0; i < spec.length; i++ ) {
            if ( ! fieldMatches(spec[i]) ) return false;
        }
        return true;
    }
    function sync() {
        if ( button && ! sent ) button.disabled = ! matches();
    }
    panel.addEventListener('input', sync);
    form.addEventListener('submit', function(event) {
        if ( sent ) {
            event.preventDefault();
            return;
        }
        sync();
        if ( ! matches() ) {
            event.preventDefault();
            window.alert(mismatch);
            return;
        }
        if ( ! window.confirm(confirmText) ) {
            event.preventDefault();
            return;
        }
        sent = true;
    });
    sync();
})();
</script>
