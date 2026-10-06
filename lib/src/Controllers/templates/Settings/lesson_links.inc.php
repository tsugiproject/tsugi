<?php
/**
 * External links: one line per placement, with the launch form in an expando.
 *
 * Expected: $external_links, $lesson_document, $lti_tools, $can_author,
 * $lessons_author_url, $add_url
 */
if ( ! isset($external_links) || ! is_array($external_links) ) {
    $external_links = array();
}
if ( ! isset($lti_tools) || ! is_array($lti_tools) ) {
    $lti_tools = array();
}
if ( ! isset($can_author) ) {
    $can_author = false;
}
$toolsById = array();
foreach ( $lti_tools as $tool ) {
    if ( is_array($tool) && isset($tool['id']) ) {
        $toolsById[(int) $tool['id']] = $tool;
    }
}
$kindNames = array(
    'lessons' => __('Lessons'),
    'pages' => __('Pages'),
    'assignments' => __('Assignments'),
);
$statusNames = array(
    'proto' => __('Not provisioned'),
    'provisioned' => __('Provisioned'),
    'gradable' => __('Gradable'),
    'missing' => __('Not found'),
);
$needsDeployment = false;
foreach ( $external_links as $link ) {
    $places = isset($link['placements']) && is_array($link['placements']) ? $link['placements'] : array();
    foreach ( $places as $place ) {
        if ( is_array($place) && isset($place['kind']) && $place['kind'] === 'lessons' ) {
            $needsDeployment = true;
            break 2;
        }
    }
}
?>
<style>
#external-links { margin-bottom: 0; }
#external-links thead th {
    position: sticky;
    top: 50px;
    background: #fff;
    z-index: 1;
}
#external-links .link-row { cursor: pointer; }
#external-links .link-row.is-open { background: #f7f7f7; }
#external-links .js-sort { padding: 0; font-weight: bold; color: inherit; }
#external-links .link-detail td { background: #fafafa; }
</style>
<p><?= __('One line per placement. Open a row for the launch.') ?></p>
<?php if ( ! $can_author ) { ?>
<p><?= __('Lesson authoring is not enabled for this course.') ?></p>
<?php } else if ( $needsDeployment && count($lti_tools) === 0 ) { ?>
<p><?= __('No deployments with a resource link in this course yet.') ?>
<?php if ( isset($add_url) && is_string($add_url) && $add_url !== '' ) { ?>
 <a href="<?= htmlspecialchars($add_url) ?>"><?= __('Add a tool') ?></a><?= __(', then come back and choose it here.') ?>
<?php } ?>
</p>
<?php } ?>
<?php if ( count($external_links) === 0 ) { ?>
<p><?= __('No external links yet.') ?></p>
<?php } else { ?>
<div class="form-inline" style="margin-bottom:10px;">
    <input type="search" class="form-control input-sm" id="link-search" placeholder="<?= htmlspecialchars(__('Filter'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('Filter'), ENT_QUOTES, 'UTF-8') ?>">
    <button type="button" class="btn btn-default btn-sm" id="link-clear"><?= __('Clear') ?></button>
    <select class="form-control input-sm" id="link-kind" aria-label="<?= htmlspecialchars(__('Placed'), ENT_QUOTES, 'UTF-8') ?>">
        <option value=""><?= __('All placements') ?></option>
        <option value="lessons"><?= __('Lessons') ?></option>
        <option value="pages"><?= __('Pages') ?></option>
        <option value="assignments"><?= __('Assignments') ?></option>
        <option value="none"><?= __('Not placed') ?></option>
    </select>
    <select class="form-control input-sm" id="link-status" aria-label="<?= htmlspecialchars(__('Status'), ENT_QUOTES, 'UTF-8') ?>">
        <option value=""><?= __('All statuses') ?></option>
        <option value="proto"><?= $statusNames['proto'] ?></option>
        <option value="provisioned"><?= $statusNames['provisioned'] ?></option>
        <option value="gradable"><?= $statusNames['gradable'] ?></option>
    </select>
    <span id="link-count" class="text-muted" style="margin-left:8px;"></span>
</div>
<table class="table table-condensed table-hover" id="external-links">
    <thead>
        <tr>
            <th><button type="button" class="btn btn-link js-sort" data-sort="placed"><?= __('Placed') ?></button></th>
            <th><button type="button" class="btn btn-link js-sort" data-sort="title"><?= __('Link') ?></button></th>
            <th><button type="button" class="btn btn-link js-sort" data-sort="tool"><?= __('Tool') ?></button></th>
            <th><button type="button" class="btn btn-link js-sort" data-sort="status"><?= __('Status') ?></button></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $external_links as $rowIndex => $link ) {
        $state = isset($link['state']) ? (string) $link['state'] : 'proto';
        $content = (isset($link['content']) && is_array($link['content'])) ? $link['content'] : null;
        $placements = isset($link['placements']) && is_array($link['placements']) ? $link['placements'] : array();
        $title = isset($link['title']) ? (string) $link['title'] : '';
        $selected = $content !== null && isset($content['tool_deployment_id']) ? (int) $content['tool_deployment_id'] : 0;
        $found = isset($toolsById[$selected]) ? $toolsById[$selected] : null;
        $place = (isset($placements[0]) && is_array($placements[0])) ? $placements[0] : null;
        $kind = $place !== null && isset($place['kind']) ? (string) $place['kind'] : 'none';
        $placedText = isset($kindNames[$kind]) ? $kindNames[$kind] : ($kind === 'none' ? __('Not placed') : $kind);
        $placeLabel = $place !== null && isset($place['label']) ? trim((string) $place['label']) : '';
        if ( $placeLabel !== '' ) {
            $placedText .= ' — '.$placeLabel;
        }
        $writable = array();
        if ( $place !== null && $kind === 'lessons' && isset($place['module_index'], $place['item_index']) ) {
            $writable[] = array(
                'module_index' => (int) $place['module_index'],
                'item_index' => (int) $place['item_index'],
            );
        }
        $gradable = $content !== null && (! empty($content['send_grade']) || ! empty($content['link_id']));
        if ( $state === 'proto' ) {
            $status = 'proto';
        } else if ( $content === null ) {
            $status = 'missing';
        } else if ( $gradable ) {
            $status = 'gradable';
        } else {
            $status = 'provisioned';
        }
        $toolTitle = $found !== null && isset($found['title']) ? (string) $found['title'] : '';
        $canPlace = $can_author && count($writable) > 0;
        $ready = $content !== null && $can_author;
        $target = $content !== null && isset($content['target']) ? (string) $content['target'] : 'window';
        $namesOn = $content !== null && ! empty($content['send_name']);
        $emailOn = $content !== null && ! empty($content['send_email']);
        $gradeOn = $content !== null && ! empty($content['send_grade']);
        $namesDisabled = ! $ready || ! ($found && ! empty($found['send_name']));
        $emailDisabled = ! $ready || ! ($found && ! empty($found['send_email']));
        $gradeDisabled = ! $ready || ! ($found && ! empty($found['send_grade']));
        $contentId = $content !== null ? (int) $content['id'] : 0;
        $heading = $title !== '' ? $title : __('Tool link');
        $hasLink = $content !== null && ! empty($content['link_id']) ? '1' : '0';
        $placementJson = json_encode($writable, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>
        <tr class="link-row"
            data-order="<?= (int) $rowIndex ?>"
            data-kind="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>"
            data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
            data-content-id="<?= $contentId ?>"
            data-has-link="<?= $hasLink ?>"
            data-placed="<?= htmlspecialchars(mb_strtolower($placedText, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
            data-title="<?= htmlspecialchars(mb_strtolower($heading, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
            data-tool="<?= htmlspecialchars(mb_strtolower($toolTitle, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
            <td><?= htmlspecialchars($placedText) ?></td>
            <td><?= htmlspecialchars($heading) ?></td>
            <td><?= htmlspecialchars($toolTitle) ?></td>
            <td class="js-status"><?= htmlspecialchars($statusNames[$status]) ?></td>
            <td><button type="button" class="btn btn-default btn-xs js-expand"><?= __('Details') ?></button></td>
        </tr>
        <tr class="link-detail" style="display:none;">
            <td colspan="5">
                <div class="link-detail-body" data-content-id="<?= $contentId ?>" data-deployment="<?= $selected ?>" data-has-link="<?= $hasLink ?>" data-placements="<?= htmlspecialchars((string) $placementJson, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label><?= __('Deployment') ?></label>
                        <?php if ( count($lti_tools) === 0 && $selected < 1 ) { ?>
                        <p class="help-block"><?= $statusNames['proto'] ?></p>
                        <?php } else { ?>
                        <select class="form-control js-provision" <?= $canPlace ? '' : 'disabled' ?>>
                            <option value=""><?= __('Choose a deployment…') ?></option>
                            <?php if ( $selected > 0 && $found === null ) { ?>
                            <option value="<?= $selected ?>" selected><?= htmlspecialchars($heading) ?> — <?= __('missing from this course') ?></option>
                            <?php } ?>
                            <?php foreach ( $lti_tools as $tool ) {
                                $toolId = isset($tool['id']) ? (int) $tool['id'] : 0;
                                $version = isset($tool['lti_version']) ? (string) $tool['lti_version'] : '';
                                $label = isset($tool['title']) ? (string) $tool['title'] : (__('Deployment').' '.$toolId);
                                if ( $version === '1.3' || $version === '1.1' ) {
                                    $label .= ' (LTI '.$version.')';
                                }
                                ?>
                            <option value="<?= $toolId ?>" <?= ($toolId === $selected && $found !== null) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php } ?>
                        </select>
                        <?php } ?>
                        <?php if ( $state === 'proto' ) { ?>
                        <p class="help-block"><?= __('Not provisioned. Pick a deployment to enable launching.') ?></p>
                        <?php if ( isset($link['launch']) && (string) $link['launch'] !== '' ) { ?>
                        <p class="help-block"><?= __('Launch URL on the lesson:') ?> <?= htmlspecialchars((string) $link['launch']) ?></p>
                        <?php } ?>
                        <?php } else if ( $content === null ) { ?>
                        <p class="help-block"><?= $statusNames['missing'] ?></p>
                        <?php } ?>
                    </div>
                    <?php if ( $content !== null ) { ?>
                    <div class="form-group">
                        <label><?= __('Launch URL') ?></label>
                        <input type="text" class="form-control js-launch" <?= $ready ? '' : 'disabled' ?> value="<?= htmlspecialchars((string) $content['launch_url']) ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('Privacy') ?></label>
                        <div class="checkbox">
                            <label><input type="checkbox" class="js-send-name" <?= $namesOn ? 'checked' : '' ?> <?= $namesDisabled ? 'disabled' : '' ?>> <?= __('Send user names to the external tool') ?></label>
                        </div>
                        <div class="checkbox">
                            <label><input type="checkbox" class="js-send-email" <?= $emailOn ? 'checked' : '' ?> <?= $emailDisabled ? 'disabled' : '' ?>> <?= __('Send email addresses to the external tool') ?></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= __('Grades') ?></label>
                        <div class="checkbox">
                            <label><input type="checkbox" class="js-send-grade" <?= $gradeOn ? 'checked' : '' ?> <?= $gradeDisabled ? 'disabled' : '' ?>> <?= __('Allow this tool to return a grade') ?></label>
                        </div>
                        <?php if ( $found && empty($found['send_grade']) ) { ?>
                        <p class="help-block"><?= __('This deployment is not allowed to return grades.') ?></p>
                        <?php } ?>
                    </div>
                    <div class="form-group">
                        <label><?= __('Open') ?></label>
                        <div class="form-group-radios">
                            <label><input type="radio" class="js-target" name="open-<?= (int) $rowIndex ?>" value="window" <?= $target === 'window' ? 'checked' : '' ?> <?= $ready ? '' : 'disabled' ?>> <?= __('New window') ?></label>
                            <label><input type="radio" class="js-target" name="open-<?= (int) $rowIndex ?>" value="iframe" <?= $target === 'iframe' ? 'checked' : '' ?> <?= $ready ? '' : 'disabled' ?>> <?= __('Modal') ?></label>
                            <label><input type="radio" class="js-target" name="open-<?= (int) $rowIndex ?>" value="inline" <?= $target === 'inline' ? 'checked' : '' ?> <?= $ready ? '' : 'disabled' ?>> <?= __('Embedded inline') ?></label>
                        </div>
                    </div>
                    <p class="help-block"><?= __('Resource link:') ?> <?= htmlspecialchars((string) ($content['resource_link_id'] ?? '')) ?></p>
                    <?php } ?>
                </div>
            </td>
        </tr>
    <?php } ?>
        <tr id="link-empty" style="display:none;"><td colspan="5"><?= __('No links match.') ?></td></tr>
    </tbody>
</table>
<?php
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<script>
var linkStatusLabels = <?= json_encode($statusNames, $jsonFlags) ?>;
var linkSortKey = '';
var linkSortDir = 1;

function linkPairs() {
    var pairs = [];
    document.querySelectorAll('#external-links .link-row').forEach(function(row) {
        pairs.push([row, row.nextElementSibling]);
    });
    return pairs;
}

function linkStatusRank(status) {
    if (status === 'proto' || status === 'missing') return 0;
    if (status === 'provisioned') return 1;
    if (status === 'gradable') return 2;
    return 3;
}

function linkApplySort() {
    var tbody = document.querySelector('#external-links tbody');
    var empty = document.getElementById('link-empty');
    var pairs = linkPairs();
    if (linkSortKey) {
        pairs.sort(function(a, b) {
            var av = a[0].getAttribute('data-' + linkSortKey) || '';
            var bv = b[0].getAttribute('data-' + linkSortKey) || '';
            if (linkSortKey === 'status') {
                av = linkStatusRank(a[0].getAttribute('data-status'));
                bv = linkStatusRank(b[0].getAttribute('data-status'));
            }
            if (av < bv) return -1 * linkSortDir;
            if (av > bv) return 1 * linkSortDir;
            var ao = parseInt(a[0].getAttribute('data-order'), 10) || 0;
            var bo = parseInt(b[0].getAttribute('data-order'), 10) || 0;
            return ao - bo;
        });
    }
    pairs.forEach(function(pair) {
        tbody.appendChild(pair[0]);
        if (pair[1]) tbody.appendChild(pair[1]);
    });
    if (empty) tbody.appendChild(empty);
}

function linkApplyFilter() {
    var q = (document.getElementById('link-search').value || '').trim().toLowerCase();
    var kind = document.getElementById('link-kind').value;
    var status = document.getElementById('link-status').value;
    var shown = 0;
    var total = 0;
    linkPairs().forEach(function(pair) {
        var row = pair[0];
        var detail = pair[1];
        total++;
        var text = (row.getAttribute('data-placed') || '') + ' ' + (row.getAttribute('data-title') || '') + ' ' + (row.getAttribute('data-tool') || '');
        var rowStatus = row.getAttribute('data-status') || '';
        var ok = true;
        if (q && text.indexOf(q) === -1) ok = false;
        if (kind && row.getAttribute('data-kind') !== kind) ok = false;
        if (status === 'proto' && rowStatus !== 'proto' && rowStatus !== 'missing') ok = false;
        if (status === 'provisioned' && rowStatus !== 'provisioned' && rowStatus !== 'gradable') ok = false;
        if (status === 'gradable' && rowStatus !== 'gradable') ok = false;
        row.style.display = ok ? '' : 'none';
        if (!ok && detail) {
            detail.style.display = 'none';
            row.classList.remove('is-open');
            var button = row.querySelector('.js-expand');
            if (button) button.textContent = button.getAttribute('data-open-label') || button.textContent;
        }
        if (ok) shown++;
    });
    var count = document.getElementById('link-count');
    if (count) count.textContent = shown + ' / ' + total;
    var empty = document.getElementById('link-empty');
    if (empty) empty.style.display = shown === 0 ? '' : 'none';
}

function linkToggle(row) {
    var detail = row.nextElementSibling;
    if (!detail) return;
    var open = row.classList.contains('is-open');
    linkPairs().forEach(function(pair) {
        pair[0].classList.remove('is-open');
        if (pair[1]) pair[1].style.display = 'none';
        var button = pair[0].querySelector('.js-expand');
        if (button) button.textContent = button.getAttribute('data-open-label');
    });
    if (!open) {
        row.classList.add('is-open');
        detail.style.display = '';
        var button = row.querySelector('.js-expand');
        if (button) button.textContent = button.getAttribute('data-close-label');
    }
}

document.querySelectorAll('#external-links .js-expand').forEach(function(button) {
    button.setAttribute('data-open-label', button.textContent);
    button.setAttribute('data-close-label', <?= json_encode(__('Hide'), $jsonFlags) ?>);
});
document.querySelectorAll('#external-links .js-sort').forEach(function(button) {
    button.addEventListener('click', function() {
        var key = button.getAttribute('data-sort');
        if (linkSortKey === key) {
            linkSortDir = -linkSortDir;
        } else {
            linkSortKey = key;
            linkSortDir = 1;
        }
        linkApplySort();
    });
});
document.getElementById('link-search').addEventListener('input', linkApplyFilter);
document.getElementById('link-kind').addEventListener('change', linkApplyFilter);
document.getElementById('link-status').addEventListener('change', linkApplyFilter);
document.getElementById('link-clear').addEventListener('click', function() {
    document.getElementById('link-search').value = '';
    document.getElementById('link-kind').value = '';
    document.getElementById('link-status').value = '';
    linkApplyFilter();
    document.getElementById('link-search').focus();
});
document.querySelectorAll('#external-links .link-row').forEach(function(row) {
    row.addEventListener('click', function(ev) {
        if (ev.target.closest('button, a, input, select, label')) return;
        linkToggle(row);
    });
    var button = row.querySelector('.js-expand');
    if (button) {
        button.addEventListener('click', function(ev) {
            ev.preventDefault();
            linkToggle(row);
        });
    }
});
linkApplyFilter();
<?php if ( $can_author ) { ?>
var lessonsAuthorUrl = <?= json_encode(isset($lessons_author_url) ? $lessons_author_url : '', $jsonFlags) ?>;
var lessonsData = <?= json_encode(isset($lesson_document) && is_array($lesson_document) ? $lesson_document : new \stdClass(), $jsonFlags) ?>;
var ltiTools = <?= json_encode($lti_tools, $jsonFlags) ?>;

function ltiToolById(id) {
    id = parseInt(id, 10) || 0;
    for (var i = 0; i < ltiTools.length; i++) {
        if (parseInt(ltiTools[i].id, 10) === id) {
            return ltiTools[i];
        }
    }
    return null;
}

function writablePlacements(panel) {
    try {
        var parsed = JSON.parse(panel.getAttribute('data-placements') || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

function lessonItemAt(place) {
    var moduleIndex = parseInt(place.module_index, 10);
    var itemIndex = parseInt(place.item_index, 10);
    if (!lessonsData.modules || !lessonsData.modules[moduleIndex] || !lessonsData.modules[moduleIndex].items) {
        return null;
    }
    return lessonsData.modules[moduleIndex].items[itemIndex] || null;
}

function ajaxError(xhr, fallback) {
    try {
        var body = JSON.parse(xhr.responseText);
        if (body && body.error) {
            return body.error;
        }
    } catch (e) {}
    return fallback;
}

function attachContent(item, content) {
    item.type = 'lti';
    item.content_id = content.id;
    var title = (item.title || '').trim();
    item.title = title || content.title || item.title || '';
    if (item.subtype === 'discussion') {
        delete item.subtype;
    }
    delete item.registration_id;
    delete item.tool_deployment_id;
    delete item.launch;
    delete item.target;
    delete item.send_name;
    delete item.send_email;
    delete item.send_grade;
    delete item.resource_link_id;
    delete item.custom;
    delete item.published;
}

function saveLessons(done) {
    $.ajax({
        url: lessonsAuthorUrl,
        method: 'POST',
        headers: tsugiCsrfHeaders(),
        data: { action: 'save', data: JSON.stringify(lessonsData) },
        success: function(response) {
            var result = typeof response === 'string' ? JSON.parse(response) : response;
            if (!result.success) {
                done(false, result.error || 'Could not save that launch on the lesson.');
                return;
            }
            done(true);
        },
        error: function(xhr) {
            done(false, ajaxError(xhr, 'Could not save that launch on the lesson.'));
        }
    });
}

function provisionLink(panel, deploymentId, select) {
    var places = writablePlacements(panel);
    if (!places.length) return;
    var snapshots = [];
    var title = '';
    for (var i = 0; i < places.length; i++) {
        var item = lessonItemAt(places[i]);
        if (!item) {
            alert('That launch was not found in this course.');
            return;
        }
        if (!title) title = (item.title || '').trim();
        snapshots.push({ place: places[i], item: JSON.parse(JSON.stringify(item)) });
    }
    var revert = panel.getAttribute('data-deployment') || '';
    if (revert === '0') revert = '';
    select.disabled = true;
    $.ajax({
        url: lessonsAuthorUrl,
        method: 'POST',
        headers: tsugiCsrfHeaders(),
        data: {
            action: 'place-lti',
            tool_deployment_id: deploymentId,
            title: title
        },
        success: function(response) {
            var result = typeof response === 'string' ? JSON.parse(response) : response;
            if (!result.success || !result.content) {
                alert(result.error || 'Could not place that tool.');
                select.disabled = false;
                select.value = revert;
                return;
            }
            snapshots.forEach(function(snap) {
                var current = lessonItemAt(snap.place);
                if (current) attachContent(current, result.content);
            });
            saveLessons(function(ok, err) {
                if (!ok) {
                    snapshots.forEach(function(snap) {
                        lessonsData.modules[snap.place.module_index].items[snap.place.item_index] = snap.item;
                    });
                    alert(err || 'Could not save that launch on the lesson.');
                    select.disabled = false;
                    select.value = revert;
                    return;
                }
                window.location.reload();
            });
        },
        error: function(xhr) {
            alert(ajaxError(xhr, 'Could not place that tool.'));
            select.disabled = false;
            select.value = revert;
        }
    });
}

function markLaunchStatus(contentId, gradable, hasLink) {
    document.querySelectorAll('#external-links .link-row[data-content-id="' + contentId + '"]').forEach(function(row) {
        var status = gradable || hasLink ? 'gradable' : 'provisioned';
        row.setAttribute('data-status', status);
        row.setAttribute('data-has-link', hasLink ? '1' : '0');
        var cell = row.querySelector('.js-status');
        if (cell) cell.textContent = linkStatusLabels[status] || status;
    });
}

function patchLink(panel) {
    var contentId = parseInt(panel.getAttribute('data-content-id'), 10) || 0;
    if (!contentId) return;
    var launchEl = panel.querySelector('.js-launch');
    var launch = launchEl ? launchEl.value.trim() : '';
    if (!launch) {
        alert('Launch URL is required.');
        return;
    }
    var targetEl = panel.querySelector('.js-target:checked');
    var nameEl = panel.querySelector('.js-send-name');
    var emailEl = panel.querySelector('.js-send-email');
    var gradeEl = panel.querySelector('.js-send-grade');
    $.ajax({
        url: lessonsAuthorUrl,
        method: 'POST',
        headers: tsugiCsrfHeaders(),
        data: {
            action: 'patch-lti',
            content_id: contentId,
            launch_url: launch,
            target: targetEl ? targetEl.value : 'window',
            send_name: nameEl && nameEl.checked ? '1' : '0',
            send_email: emailEl && emailEl.checked ? '1' : '0',
            send_grade: gradeEl && gradeEl.checked ? '1' : '0'
        },
        success: function(response) {
            var result = typeof response === 'string' ? JSON.parse(response) : response;
            if (!result.success || !result.content) {
                alert((result && result.error) || 'Could not update that launch.');
                return;
            }
            var hasLink = !!(result.content.link_id);
            if (hasLink) panel.setAttribute('data-has-link', '1');
            markLaunchStatus(String(contentId), !!(gradeEl && gradeEl.checked) || hasLink, hasLink || panel.getAttribute('data-has-link') === '1');
        },
        error: function(xhr) {
            alert(ajaxError(xhr, 'Could not update that launch.'));
        }
    });
}

function applyToolLimits(panel, tool) {
    [['js-send-name', 'send_name'], ['js-send-email', 'send_email'], ['js-send-grade', 'send_grade']].forEach(function(pair) {
        var el = panel.querySelector('.' + pair[0]);
        if (!el) return;
        var allowed = !!(tool && tool[pair[1]]);
        el.disabled = !!(tool && !allowed);
        if (tool && !allowed) el.checked = false;
    });
}

document.querySelectorAll('.js-provision').forEach(function(select) {
    select.addEventListener('change', function() {
        var panel = select.closest('.link-detail-body');
        var next = parseInt(select.value, 10) || 0;
        var current = parseInt(panel.getAttribute('data-deployment') || '0', 10) || 0;
        if (!next || next === current) return;
        provisionLink(panel, next, select);
    });
});
document.querySelectorAll('.link-detail-body[data-content-id]').forEach(function(panel) {
    if (!(parseInt(panel.getAttribute('data-content-id'), 10) || 0)) return;
    var launch = panel.querySelector('.js-launch');
    if (launch) launch.addEventListener('change', function() { patchLink(panel); });
    panel.querySelectorAll('.js-send-name, .js-send-email, .js-send-grade, .js-target').forEach(function(el) {
        el.addEventListener('change', function() { patchLink(panel); });
    });
    applyToolLimits(panel, ltiToolById(panel.getAttribute('data-deployment')));
});
<?php } ?>
</script>
<?php } ?>
