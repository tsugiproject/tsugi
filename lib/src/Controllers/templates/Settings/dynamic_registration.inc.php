<?php
/**
 * Tool registration iframe. The parent only listens for the IMS close message.
 *
 * Expected: $forward_url, $status_url, $tools_url
 */
if ( ! isset($forward_url) || ! is_string($forward_url) ) {
    $forward_url = '';
}
if ( ! isset($status_url) || ! is_string($status_url) ) {
    $status_url = '';
}
if ( ! isset($tools_url) || ! is_string($tools_url) ) {
    $tools_url = '';
}
?>
<h3><?= __('LTI Dynamic Registration') ?></h3>
<p><?= __('When the tool finishes, this page returns to the course tools. If it stays here, open the tools list. The tool is saved once registration completes.') ?></p>
<p><a href="<?= htmlspecialchars($tools_url) ?>"><?= __('Back to course tools') ?></a></p>
<iframe id="tsugi-dynamic-registration" title="<?= htmlspecialchars(__('LTI Dynamic Registration')) ?>" src="<?= htmlspecialchars($forward_url) ?>" style="width:100%;height:70vh;border:1px solid #ccc;"></iframe>
<script>
(function () {
    var statusUrl = <?= json_encode($status_url) ?>;
    var started = false;
    function finish() {
        if (started) {
            return;
        }
        started = true;
        var tries = 0;
        function poll() {
            fetch(statusUrl, {credentials: 'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data && data.ready && data.done_url) {
                        window.location = data.done_url;
                        return;
                    }
                    tries += 1;
                    if (tries < 8) {
                        setTimeout(poll, 400);
                    } else {
                        started = false;
                    }
                })
                .catch(function () {
                    started = false;
                });
        }
        poll();
    }
    window.addEventListener('message', function (event) {
        var message = event.data;
        if (typeof message === 'string') {
            try {
                message = JSON.parse(message);
            } catch (err) {
                return;
            }
        }
        if (!message || message.subject !== 'org.imsglobal.lti.close') {
            return;
        }
        finish();
    });
})();
</script>
