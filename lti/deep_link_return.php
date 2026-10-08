<?php

use Tsugi\Services\Outbound\Lti13TestLaunch;

require_once __DIR__ . '/../config.php';

$jwt = '';
if ( isset($_POST['JWT']) && is_string($_POST['JWT']) ) {
    $jwt = $_POST['JWT'];
} else if ( isset($_POST['jwt']) && is_string($_POST['jwt']) ) {
    $jwt = $_POST['jwt'];
}

$error = '';
if ( $jwt === '' ) {
    $error = 'The deep link return is missing JWT.';
} else {
    try {
        Lti13TestLaunch::acceptReturn($jwt);
    } catch ( \InvalidArgumentException $e ) {
        $error = $e->getMessage();
    } catch ( \Throwable $e ) {
        error_log('deep_link_return '.$e->getMessage());
        $error = 'The deep link return could not be checked.';
    }
}

header('Content-Type: text/html; charset=utf-8');
$subject = $error === '' ? 'org.tsugi.lti.deep_linking_response' : 'org.tsugi.lti.deep_linking_error';
$payload = array(
    'subject' => $subject,
);
if ( $error === '' ) {
    $payload['jwt'] = $jwt;
} else {
    $payload['message'] = $error;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Deep link return</title>
</head>
<body>
<p><?= $error === '' ? 'Sending the deep link return back to the test.' : htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<script>
(function () {
    var payload = <?= json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    if (window.parent && window.parent !== window) {
        window.parent.postMessage(payload, window.location.origin);
    }
})();
</script>
</body>
</html>
