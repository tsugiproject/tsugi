<?php

function addLinks() {
    global $oidc_login, $oidc_redirect, $oidc_redirect, $lti13_keyset, $deep_link;
    // Keep the blanks at the ned of the lines because we are in a pre tag
?>
LTI 1.3 OpenID Connect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_login ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_login ?> 

LTI 1.3 Tool Redirect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_redirect ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_redirect ?> 

LTI 1.3 Tool Keyset URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $lti13_keyset ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $lti13_keyset ?> 

LTI Content Item / Deep Link Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $deep_link ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $deep_link ?> 
<?php
}
