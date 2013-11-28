<?php
# Logging in with Google accounts requires setting special identity, so this example shows how to do it.
require_once '../../config.php';
require_once $CFG->dirroot."/lib/lightopenid/openid.php";

try {
    # Change 'localhost' to your domain name.
    $openid = new LightOpenID($CFG->wwwroot);
    if(!$openid->mode) {
        if(isset($_GET['login'])) {
            $openid->identity = 'https://www.google.com/accounts/o8/id';
            $openid->required = array('contact/email', 'namePerson/first', 'namePerson/last');
            $openid->optional = array('namePerson/friendly');
            header('Location: ' . $openid->authUrl());
        }
?>
<form action="?login" method="post">
    <button>Login with Google</button>
</form>
<?php
    } elseif($openid->mode == 'cancel') {
        echo 'User has canceled authentication!';
    } else if ( ! $openid->validate() ) {
        echo 'You were not logged in by Google.  It may be due to a technical problem.';
    } else {
        $identity = $openid->identity;
        $userAttributes = $openid->getAttributes();
        echo("\n<pre>\nAttributes:\n");
		print_r($userAttributes);
		echo("\n</pre>\n");
        $firstName = isset($userAttributes['namePerson/first']) ? $userAttributes['namePerson/first'] : false;
        $lastName = isset($userAttributes['namePerson/last']) ? $userAttributes['namePerson/last'] : false;
        $userEmail = isset($userAttributes['contact/email']) ? $userAttributes['contact/email'] : false;
		// Off we go...
    }
} catch(ErrorException $e) {
    echo $e->getMessage();
}
