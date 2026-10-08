<?php
/**
 * Add or edit one LTI 1.1 tool.
 *
 * Expected: $save_url (form post), $tools_url (cancel), $form_values or null,
 * $launch_url_count_url
 */
if ( ! isset($form_values) || ! is_array($form_values) ) {
    $form_values = null;
}
$editing = is_array($form_values);
$sections = \Tsugi\Services\Outbound\Lti11CourseTool::formSections();
$cancelUrl = (isset($tools_url) && is_string($tools_url) && $tools_url !== '') ? $tools_url : $save_url;
?>
<p><?= $editing
    ? __('Edit this LTI 1.1 tool. It stays on this course.')
    : __('Add an LTI 1.1 tool to this course. It is not shared with other courses.') ?></p>
<form method="post" action="<?= htmlspecialchars($save_url) ?>" id="lti11-course-tool"<?php if ( isset($launch_url_count_url) && is_string($launch_url_count_url) && $launch_url_count_url !== '' ) { ?> data-launch-count-url="<?= htmlspecialchars($launch_url_count_url, ENT_QUOTES, 'UTF-8') ?>"<?php } ?>>
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <?php if ( $editing ) { ?>
    <input type="hidden" name="registration_id" value="<?= (int) $form_values['registration_id'] ?>">
    <?php } ?>
    <?php foreach ( $sections as $section ) {
        if ( ($section['kind'] ?? '') === 'fields' ) {
            foreach ( $section['fields'] as $field ) {
                $name = (string) $field['name'];
                $id = 'lti11_'.preg_replace('/[^a-z0-9_]+/i', '_', $name);
                $type = (string) $field['type'];
                $max = isset($field['maxlength']) ? (int) $field['maxlength'] : 0;
                $value = $editing && isset($form_values[$name]) ? (string) $form_values[$name] : '';
                $launchNote = '';
                if ( $name === 'lti11_url' && $editing ) {
                    $launchNote = \Tsugi\Services\Outbound\Lti11CourseTool::launchUrlNote($form_values['other_launch_urls'] ?? 0);
                }
                ?>
    <div class="form-group">
        <label for="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars(__($field['label'])) ?></label>
        <input class="form-control" style="max-width: 40em;" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($name) ?>" type="<?= htmlspecialchars($type) ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" required<?= $max > 0 ? ' maxlength="'.$max.'"' : '' ?><?= $type === 'password' ? ' autocomplete="off"' : '' ?>>
        <?php if ( $name === 'lti11_url' ) { ?>
        <p id="lti11-launch-url-note" class="help-block"<?= $launchNote === '' ? ' hidden' : '' ?>><?= htmlspecialchars($launchNote) ?></p>
        <?php } ?>
    </div>
                <?php
            }
            continue;
        }
        ?>
    <fieldset style="margin: 1.2em 0;">
        <legend><?= htmlspecialchars(__($section['legend'])) ?></legend>
        <?php if ( ! empty($section['note']) ) { ?>
        <p><?= htmlspecialchars(__($section['note'])) ?></p>
        <?php } ?>
        <?php foreach ( $section['boxes'] as $box ) {
            $boxId = htmlspecialchars((string) $section['name'].'_'.$box['value'], ENT_QUOTES, 'UTF-8');
            $sectionName = (string) $section['name'];
            $checked = $editing
                && isset($form_values[$sectionName])
                && is_array($form_values[$sectionName])
                && in_array((string) $box['value'], $form_values[$sectionName], true);
            ?>
        <div class="checkbox">
            <label for="<?= $boxId ?>">
                <input type="checkbox" id="<?= $boxId ?>" name="<?= htmlspecialchars($sectionName) ?>[]" value="<?= htmlspecialchars((string) $box['value']) ?>"<?= $checked ? ' checked' : '' ?><?= ! empty($box['requires']) && is_array($box['requires']) ? ' data-requires="'.htmlspecialchars(implode(' ', $box['requires'])).'"' : '' ?>>
                <?= htmlspecialchars(__($box['label'])) ?>
            </label>
        </div>
        <?php } ?>
    </fieldset>
    <?php } ?>
    <p id="lti11-course-tool-error" class="alert alert-danger" role="alert" tabindex="-1" hidden></p>
    <p>
        <button type="submit" class="btn btn-primary"><?= $editing ? __('Save tool') : __('Add tool to this course') ?></button>
        <a class="btn btn-default" href="<?= htmlspecialchars($cancelUrl) ?>"><?= __('Cancel') ?></a>
    </p>
</form>
<script>
(function () {
    var form = document.getElementById('lti11-course-tool');
    var error = document.getElementById('lti11-course-tool-error');
    if (!form || !error) {
        return;
    }
    var text = <?= json_encode(array(
        'needLaunch' => __('An LTI 1.1 registration needs a resource link launch or a content item launch.'),
        'needResource' => __('That placement requires a resource link launch.'),
        'needContent' => __('That placement requires a content item launch.'),
        'launchOne' => \Tsugi\Services\Outbound\Lti11CourseTool::launchUrlNote(1),
        'launchMany' => __('There are %d other tools using this launch URL.'),
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var launchNote = document.getElementById('lti11-launch-url-note');
    var launchUrl = document.getElementById('lti11_lti11_url');
    var launchCountUrl = form.getAttribute('data-launch-count-url') || '';
    var launchExcept = form.querySelector('input[name="registration_id"]');
    var launchTimer = null;
    function showLaunchNote(count) {
        if (!launchNote) {
            return;
        }
        count = parseInt(count, 10) || 0;
        if (count < 1) {
            launchNote.hidden = true;
            launchNote.textContent = '';
            return;
        }
        launchNote.hidden = false;
        launchNote.textContent = count === 1 ? text.launchOne : text.launchMany.replace('%d', String(count));
    }
    function lookupLaunchUrl() {
        if (!launchUrl || launchCountUrl === '') {
            return;
        }
        var value = launchUrl.value.replace(/^\s+|\s+$/g, '');
        if (!/^https?:\/\//i.test(value)) {
            showLaunchNote(0);
            return;
        }
        var sep = launchCountUrl.indexOf('?') === -1 ? '?' : '&';
        var target = launchCountUrl + sep + 'lti11_url=' + encodeURIComponent(value);
        if (launchExcept && launchExcept.value) {
            target += '&registration_id=' + encodeURIComponent(launchExcept.value);
        }
        fetch(target, {credentials: 'same-origin', headers: {Accept: 'application/json'}})
            .then(function (response) { return response.json(); })
            .then(function (data) { showLaunchNote(data && data.count); })
            .catch(function () {});
    }
    if (launchUrl && launchCountUrl !== '') {
        launchUrl.addEventListener('input', function () {
            if (launchTimer) {
                clearTimeout(launchTimer);
            }
            launchTimer = setTimeout(lookupLaunchUrl, 300);
        });
        if (launchUrl.value.replace(/^\s+|\s+$/g, '') !== '') {
            lookupLaunchUrl();
        }
    }
    function launchBox(type) {
        return form.querySelector('input[name="messages[]"][value="' + type + '"]');
    }
    function launchChecked(type) {
        var box = launchBox(type);
        return !!(box && box.checked);
    }
    function show(message) {
        error.textContent = message;
        error.hidden = false;
    }
    function clear() {
        error.hidden = true;
        error.textContent = '';
    }
    function checkCombos() {
        var shown = '';
        var placements = form.querySelectorAll('input[name="placements[]"]');
        for (var i = 0; i < placements.length; i++) {
            if (!placements[i].checked) {
                continue;
            }
            var needs = (placements[i].getAttribute('data-requires') || '').split(/\s+/);
            var ok = false;
            for (var j = 0; j < needs.length; j++) {
                if (needs[j] !== '' && launchChecked(needs[j])) {
                    ok = true;
                }
            }
            if (ok || needs.length !== 1) {
                continue;
            }
            if (needs[0] === 'LtiResourceLinkRequest') {
                var resource = launchBox('LtiResourceLinkRequest');
                if (resource) {
                    resource.checked = true;
                }
                if (shown === '') {
                    shown = text.needResource;
                }
            } else if (needs[0] === 'LtiDeepLinkingRequest') {
                placements[i].checked = false;
                if (shown === '') {
                    shown = text.needContent;
                }
            }
        }
        if (!launchChecked('LtiResourceLinkRequest') && !launchChecked('LtiDeepLinkingRequest')) {
            var fallback = launchBox('LtiResourceLinkRequest');
            if (fallback) {
                fallback.checked = true;
            }
            if (shown === '') {
                shown = text.needLaunch;
            }
        }
        if (shown === '') {
            clear();
            return false;
        }
        show(shown);
        return true;
    }
    form.addEventListener('change', function (event) {
        if (event.target && event.target.type === 'checkbox') {
            checkCombos();
        }
    });
    form.addEventListener('submit', function (event) {
        if (checkCombos()) {
            event.preventDefault();
            error.focus();
        }
    });
})();
</script>
