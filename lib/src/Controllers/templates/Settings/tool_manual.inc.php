<?php
/**
 * Create an LTI 1.3 registration this course owns, without dynamic registration.
 *
 * Expected: $manual_url, $tools_url
 */
$postUrl = isset($manual_url) ? (string) $manual_url : '';
$cancelUrl = isset($tools_url) ? (string) $tools_url : '';
?>
<p><a href="<?= htmlspecialchars($cancelUrl) ?>"><?= __('Back to course tools') ?></a></p>
<h2 style="margin-top:0;"><?= __('Manual Add') ?> <span class="text-muted">(LTI 1.3)</span></h2>
<p><?= __('In general, LTI Dynamic Registration is preferred over any manual configuration of an LTI 1.3 tool. This form is for the rare tool that does not provide it. Creating the tool and linking both sides is often a multi-step process.') ?></p>
<p><?= __('A title is enough to start. Leave a URL blank until you have it. The tool stays a draft until the login URL, keyset URL, target link, and a redirect URL are filled in. Messages, scopes, and placements are filled in on the next page.') ?></p>
<form method="post" action="<?= htmlspecialchars($postUrl) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <div class="form-group">
        <label for="lti13_manual_title"><?= __('Tool title') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_manual_title" name="title" type="text" maxlength="512" required>
    </div>
    <div class="form-group">
        <label for="lti13_manual_login"><?= __('Login URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_manual_login" name="oidc_login_url" type="url">
    </div>
    <div class="form-group">
        <label for="lti13_manual_jwks"><?= __('Keyset URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_manual_jwks" name="jwks_url" type="url">
    </div>
    <div class="form-group">
        <label for="lti13_manual_launch"><?= __('Target link') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_manual_launch" name="launch_url" type="url">
    </div>
    <div class="form-group">
        <label for="lti13_manual_redirect"><?= __('Redirect URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_manual_redirect" name="redirect_uri" type="url">
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Add tool') ?></button>
        <a class="btn btn-default" href="<?= htmlspecialchars($cancelUrl) ?>"><?= __('Cancel') ?></a>
    </p>
</form>
<?php include __DIR__.'/platform_urls.inc.php'; ?>
