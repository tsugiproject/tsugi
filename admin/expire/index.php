<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_key");
$tenant_count = $row ? $row['count'] : 0;
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_context");
$context_count = $row ? $row['count'] : 0;
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user");
$user_count = $row ? $row['count'] : 0;

$tenant_days = U::get($_GET,'tenant_days',1000);
$context_days = U::get($_GET,'context_days',500);
$user_days = U::get($_GET,'user_days',500);

$row = $PDOX->rowDie("
    SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user
    WHERE created_at <= CURRENT_DATE() - INTERVAL :DAY DAY
    AND (login_at IS NULL OR login_at <= CURRENT_DATE() - INTERVAL :DAY DAY)",
    array(":DAY" => $user_days )
);
$user_expire = $row ? $row['count'] : 0;

$row = $PDOX->rowDie("
    SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_context
    WHERE created_at <= CURRENT_DATE() - INTERVAL :DAY DAY
    AND (login_at IS NULL OR login_at <= CURRENT_DATE() - INTERVAL :DAY DAY)",
    array(":DAY" => $context_days )
);
$context_expire = $row ? $row['count'] : 0;

$row = $PDOX->rowDie("
    SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_key
    WHERE created_at <= CURRENT_DATE() - INTERVAL :DAY DAY
    AND (login_at IS NULL OR login_at <= CURRENT_DATE() - INTERVAL :DAY DAY)",
    array(":DAY" => $tenant_days )
);
$tenant_expire = $row ? $row['count'] : 0;


$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Manage Data Expiry</h1>
<form>
<ul>
<li>User count: <?= $user_count ?>  <br/>
Users with no activity in 
<input type="text" name="user_days" size=5 value="<?= $user_days ?>"> days:
<?= $user_expire ?> 
</li>
<li>Context count: <?= $context_count ?>  <br/>
Contexts with no activity in 
<input type="text" name="context_days" size=5 value="<?= $context_days ?>"> days:
<?= $context_expire ?> 
</li>
<li>Tenant count: <?= $tenant_count ?>  <br/>
Tenants with no activity in 
<input type="text" name="tenant_days" size=5 value="<?= $tenant_days ?>"> days:
<?= $tenant_expire ?> 
</li>
</ul>
<input type="submit" value="Update">
</form>
<?php
$OUTPUT->footer();

