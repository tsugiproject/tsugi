<?php
/**
 * Send a test launch for one tool on this course.
 *
 * Expected: $tools_url, $test_url, $registrationId, $launch_choices, $launch, $launch_html
 */
if ( ! isset($launch_choices) || ! is_array($launch_choices) ) {
    $launch_choices = array();
}
if ( ! isset($launch) || ! is_array($launch) ) {
    $launch = null;
}
if ( ! isset($launch_html) || ! is_string($launch_html) ) {
    $launch_html = '';
}
$registrationId = isset($registrationId) ? (int) $registrationId : 0;
$testSep = (isset($test_url) && is_string($test_url) && strpos($test_url, '?') !== false) ? '&' : '?';
$launchRole = is_array($launch) && isset($launch['role']) ? (string) $launch['role'] : 'Instructor';
$launchMessage = is_array($launch) && isset($launch['message_type']) ? (string) $launch['message_type'] : '';
if ( ! isset($deep_return) || ! is_array($deep_return) ) {
    $deep_return = null;
}
$includeLineItems = ! isset($include_lineitems) || $include_lineitems;
$lineItemsQuery = $includeLineItems ? '' : '&lineitems=0';
$offerLineItems = ! empty($lti13)
    && $deep_return === null
    && is_array($launch)
    && ! empty($launch['ready'])
    && in_array($launchMessage, array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest'), true);
?>
<p>
    <a href="<?= htmlspecialchars((string) $tools_url) ?>"><?= __('Back to tools') ?></a>
</p>
<?php if ( $launch === null ) { ?>
<p><?= __('This tool has no launches to test.') ?></p>
<?php } else { ?>
<h2><?= htmlspecialchars($launch['title']) ?></h2>
<p><?= htmlspecialchars(sprintf(__('Test launch: %s'), $launch['label'])) ?></p>
<p>
<?php foreach ( array('Instructor', 'Learner') as $roleName ) {
    $href = $test_url.$testSep.'registration_id='.$registrationId.'&message='.rawurlencode($launchMessage).'&role='.rawurlencode($roleName).$lineItemsQuery;
    $current = $roleName === $launchRole;
    ?>
    <?php if ( $current ) { ?>
    <span class="btn btn-primary btn-sm"><?= htmlspecialchars(__($roleName)) ?></span>
    <?php } else { ?>
    <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars(__($roleName)) ?></a>
    <?php } ?>
<?php } ?>
</p>
<?php if ( count($launch_choices) > 1 ) { ?>
<p>
<?php foreach ( $launch_choices as $choice ) {
    $href = $test_url.$testSep.'registration_id='.$registrationId.'&message='.rawurlencode((string) $choice['type']).'&role='.rawurlencode($launchRole).$lineItemsQuery;
    $current = $choice['type'] === $launch['message_type'];
    ?>
    <?php if ( $current ) { ?>
    <span class="btn btn-primary btn-sm"><?= htmlspecialchars(__($choice['label'])) ?></span>
    <?php } else { ?>
    <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars(__($choice['label'])) ?></a>
    <?php } ?>
<?php } ?>
</p>
<?php } ?>
<?php if ( $offerLineItems ) { ?>
<form method="post" action="<?= htmlspecialchars((string) $test_url) ?>" style="margin:8px 0;">
    <?= \Tsugi\Controllers\Tool::csrfField() ?>
<?php if ( $deep_return !== null ) { ?>
    <input type="hidden" name="deep_link_jwt" value="<?= htmlspecialchars($deep_return['jwt']) ?>">
    <input type="hidden" name="item" value="<?= (int) $deep_return['index'] ?>">
<?php } ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="message" value="<?= htmlspecialchars($deep_return !== null ? 'LtiDeepLinkingRequest' : $launchMessage) ?>">
    <input type="hidden" name="role" value="<?= htmlspecialchars($launchRole) ?>">
    <input type="hidden" name="lineitems" value="0">
    <label>
        <input type="checkbox" name="lineitems" value="1"<?= $includeLineItems ? ' checked' : '' ?> onchange="this.form.submit()">
        <?= __('Include the line items URL') ?>
    </label>
</form>
<p><?= __('If the tool creates line items in this course, they stay in the course after the test. Delete them yourself.') ?></p>
<?php } ?>
<?php if ( $launch['ready'] ) { ?>
<?php if ( $deep_return !== null ) { ?>
<p><?= __('This is the deep link return. Nothing was saved. The form below is a resource link launch to the URL from that return. Look at the debug data, then Send.') ?></p>
<?php if ( ! empty($lti13) && ! empty($launch['ready']) ) { ?>
<form method="post" action="<?= htmlspecialchars((string) $test_url) ?>" style="margin:8px 0;">
    <?= \Tsugi\Controllers\Tool::csrfField() ?>
    <input type="hidden" name="deep_link_jwt" value="<?= htmlspecialchars($deep_return['jwt']) ?>">
    <input type="hidden" name="item" value="<?= (int) $deep_return['index'] ?>">
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="message" value="LtiDeepLinkingRequest">
    <input type="hidden" name="role" value="<?= htmlspecialchars($launchRole) ?>">
    <input type="hidden" name="lineitems" value="0">
    <label>
        <input type="checkbox" name="lineitems" value="1"<?= $includeLineItems ? ' checked' : '' ?> onchange="this.form.submit()">
        <?= __('Include the line items URL') ?>
    </label>
</form>
<p><?= htmlspecialchars(sprintf(__('This resource link launches %s. If the tool creates line items in this course, they stay in the course after the test. Delete them yourself.'), (string) $launch['endpoint'])) ?></p>
<?php } ?>
<?php if ( $deep_return['msg'] !== '' ) { ?>
<p><?= htmlspecialchars($deep_return['msg']) ?></p>
<?php } ?>
<?php if ( $deep_return['errormsg'] !== '' ) { ?>
<p><?= htmlspecialchars($deep_return['errormsg']) ?></p>
<?php } ?>
<?php foreach ( $deep_return['items'] as $index => $item ) {
    $selected = $index === (int) $deep_return['index'];
    ?>
<div style="margin:0 0 12px;padding:8px;border:1px solid <?= $selected ? '#337ab7' : '#ccc' ?>;">
    <p><b><?= htmlspecialchars($item['title'] !== '' ? $item['title'] : __('Untitled item')) ?></b>
    <?= htmlspecialchars($item['type']) ?></p>
    <?php if ( $item['url'] !== '' ) { ?>
    <p><?= htmlspecialchars($item['url']) ?></p>
    <?php } ?>
    <pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($item['json']) ?></pre>
    <?php if ( ! $selected && preg_match('#^https?://#i', $item['url']) ) { ?>
    <form method="post" action="<?= htmlspecialchars((string) $test_url) ?>">
        <?= \Tsugi\Controllers\Tool::csrfField() ?>
        <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
        <input type="hidden" name="message" value="LtiDeepLinkingRequest">
        <input type="hidden" name="role" value="<?= htmlspecialchars($launchRole) ?>">
        <input type="hidden" name="lineitems" value="<?= $includeLineItems ? '1' : '0' ?>">
        <input type="hidden" name="item" value="<?= (int) $index ?>">
        <input type="hidden" name="deep_link_jwt" value="<?= htmlspecialchars($deep_return['jwt']) ?>">
        <button type="submit" class="btn btn-default btn-sm"><?= __('Use this target') ?></button>
    </form>
    <?php } ?>
</div>
<?php } ?>
<?php if ( (int) $deep_return['index'] < 0 ) { ?>
<p><?= __('The deep link return had no target link.') ?></p>
<?php } ?>
<?php } else { ?>
<p><?= htmlspecialchars(sprintf(__('This sends a %s launch to %s. The other launch types will use this same test.'), $launch['label'], $launch['endpoint'])) ?></p>
<?php } ?>
<?php if ( ! empty($launch['new_window']) ) { ?>
<p><?= __('Send opens a new window so the tool can keep its login session.') ?></p>
<?php } ?>
<?php if ( ! empty($launch['modal']) ) { ?>
<p><?= __('Send opens the deep link in a panel on this page. A signed return stays here and is not saved.') ?></p>
<div id="tsugi-deep-link-launch">
<?= $launch_html ?>
</div>
<div id="tsugi-deep-link-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;">
    <div style="background:#fff;margin:4vh auto;width:min(960px, 92vw);height:88vh;padding:12px;box-sizing:border-box;">
        <p><button type="button" class="btn btn-default btn-sm" id="tsugi-deep-link-close"><?= __('Close') ?></button>
        <span id="tsugi-deep-link-note"></span></p>
        <iframe name="tsugi_deep_link" id="tsugi_deep_link" title="<?= htmlspecialchars(__('Deep link')) ?>" src="about:blank" style="width:100%;height:calc(100% - 48px);border:1px solid #ccc;"></iframe>
        <div id="tsugi-deep-link-return" style="display:none;height:calc(100% - 48px);overflow:auto;">
            <p><?= __('Deep link return. Nothing was saved.') ?></p>
            <pre id="tsugi-deep-link-json" style="white-space:pre-wrap;word-break:break-all;"></pre>
            <p>
                <label>
                    <input type="checkbox" id="tsugi-deep-link-lineitems"<?= $includeLineItems ? ' checked' : '' ?>>
                    <?= __('Include the line items URL') ?>
                </label>
            </p>
            <p><?= __('If the tool creates line items in this course, they stay in the course after the test. Delete them yourself.') ?></p>
            <p><button type="button" class="btn btn-primary" id="tsugi-deep-link-go"><?= __('Launch the resource link from this return') ?></button></p>
        </div>
    </div>
</div>
<form id="tsugi-deep-link-back" method="post" action="<?= htmlspecialchars((string) $test_url) ?>">
    <?= \Tsugi\Controllers\Tool::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="message" value="LtiDeepLinkingRequest">
    <input type="hidden" name="role" value="<?= htmlspecialchars($launchRole) ?>">
    <input type="hidden" name="lineitems" value="<?= $includeLineItems ? '1' : '0' ?>">
    <input type="hidden" name="item" value="0">
    <input type="hidden" name="deep_link_jwt" value="">
</form>
<script>
(function () {
    var launch = document.querySelector('#tsugi-deep-link-launch form');
    var modal = document.getElementById('tsugi-deep-link-modal');
    var note = document.getElementById('tsugi-deep-link-note');
    var back = document.getElementById('tsugi-deep-link-back');
    if (launch) {
        launch.addEventListener('submit', function () {
            modal.style.display = 'block';
        });
    }
    document.getElementById('tsugi-deep-link-close').addEventListener('click', function () {
        modal.style.display = 'none';
        var frame = document.getElementById('tsugi_deep_link');
        if (frame) {
            frame.src = 'about:blank';
        }
    });
    window.addEventListener('message', function (event) {
        var message = event.data;
        if (!message || typeof message !== 'object') {
            return;
        }
        if (message.subject === 'org.imsglobal.lti.close') {
            modal.style.display = 'none';
            return;
        }
        if (event.origin !== window.location.origin) {
            return;
        }
        if (message.subject === 'org.tsugi.lti.deep_linking_error') {
            note.textContent = message.message || '';
            return;
        }
        if (message.subject !== 'org.tsugi.lti.deep_linking_response' || !message.jwt) {
            return;
        }
        back.elements.deep_link_jwt.value = message.jwt;
        var frame = document.getElementById('tsugi_deep_link');
        var returned = document.getElementById('tsugi-deep-link-return');
        var pretty = document.getElementById('tsugi-deep-link-json');
        if (frame) {
            frame.style.display = 'none';
        }
        if (pretty) {
            pretty.textContent = jwtPayload(message.jwt);
        }
        if (returned) {
            returned.style.display = 'block';
        }
    });
    document.getElementById('tsugi-deep-link-go').addEventListener('click', function () {
        if (!back.elements.deep_link_jwt.value) {
            return;
        }
        var lineItems = document.getElementById('tsugi-deep-link-lineitems');
        if (lineItems && back.elements.lineitems) {
            back.elements.lineitems.value = lineItems.checked ? '1' : '0';
        }
        back.submit();
    });
    function jwtPayload(jwt) {
        try {
            var part = jwt.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
            while (part.length % 4) {
                part += '=';
            }
            return JSON.stringify(JSON.parse(atob(part)), null, 2);
        } catch (e) {
            return jwt;
        }
    }
})();
</script>
<?php } else { ?>
<?= $launch_html ?>
<?php } ?>
<?php } else if ( ! empty($launch['missing_resource_link']) ) {
    $blockedUrl = isset($launch['endpoint']) ? (string) $launch['endpoint'] : '';
    if ( $blockedUrl !== '' && ! empty($launch['content_item_url']) ) { ?>
<p><?= htmlspecialchars(sprintf(__('This registration has no resource link message. %s is a content item URL, so a resource link cannot be sent there.'), $blockedUrl)) ?></p>
<?php } else if ( $blockedUrl !== '' ) { ?>
<p><?= htmlspecialchars(sprintf(__('This registration has no resource link message. A resource link cannot be sent to %s.'), $blockedUrl)) ?></p>
<?php } else { ?>
<p><?= __('This registration has no resource link message, so a resource link cannot be sent.') ?></p>
<?php } ?>
<?php } else { ?>
<p><?= htmlspecialchars(sprintf(__('A %s test launch is not available yet.'), $launch['label'])) ?></p>
<?php } ?>
<?php } ?>
