<?php

use Tsugi\Services\Lti\PlatformObservation;

$platform_links_ok = isset($platform_for, $platform_for_id, $CFG, $PDOX);
if ( $platform_links_ok ) {
    $platform_for_id = (int) $platform_for_id;
    $platform_links_ok = ($platform_for === 'key' || $platform_for === 'client')
        && $platform_for_id > 0
        && PlatformObservation::ready($CFG->dbprefix);
}
if ( $platform_links_ok ) {

$p = $CFG->dbprefix;
$key_admin = $CFG->wwwroot.'/admin/key/';
if ( $platform_for === 'key' ) {
    $platform_pointer = $PDOX->rowDie(
        "SELECT platform_id, registration_platform_id
            FROM {$p}lti_key WHERE key_id = :id",
        array(':id' => $platform_for_id)
    );
    $platform_rows = $PDOX->allRowsDie(
        "SELECT platform_id, source, platform_name, platform_family, platform_version,
                platform_guid, created_at
            FROM {$p}lti_platform
            WHERE key_id = :owner
               OR platform_id = :current
               OR platform_id = :registration
            ORDER BY platform_id DESC",
        array(
            ':owner' => $platform_for_id,
            ':current' => (int) (is_array($platform_pointer) ? $platform_pointer['platform_id'] : 0),
            ':registration' => (int) (is_array($platform_pointer) ? $platform_pointer['registration_platform_id'] : 0),
        )
    );
} else if ( ! $PDOX->columnExists('platform_id', $p.'lti_client')
    || ! $PDOX->columnExists('registration_platform_id', $p.'lti_client') ) {
    $platform_pointer = false;
    $platform_rows = array();
} else {
    $platform_pointer = $PDOX->rowDie(
        "SELECT platform_id, registration_platform_id
            FROM {$p}lti_client WHERE lti_client_id = :id",
        array(':id' => $platform_for_id)
    );
    $platform_rows = $PDOX->allRowsDie(
        "SELECT platform_id, source, platform_name, platform_family, platform_version,
                platform_guid, created_at
            FROM {$p}lti_platform
            WHERE lti_client_id = :owner
               OR platform_id = :current
               OR platform_id = :registration
            ORDER BY platform_id DESC",
        array(
            ':owner' => $platform_for_id,
            ':current' => (int) (is_array($platform_pointer) ? $platform_pointer['platform_id'] : 0),
            ':registration' => (int) (is_array($platform_pointer) ? $platform_pointer['registration_platform_id'] : 0),
        )
    );
}
$platform_current = (int) (is_array($platform_pointer) ? $platform_pointer['platform_id'] : 0);
$platform_registration = (int) (is_array($platform_pointer) ? $platform_pointer['registration_platform_id'] : 0);
$platform_whose = $platform_for === 'client' ? 'client' : 'key';
?>
<h2>Platform snapshots</h2>
<p>Recorded from a registration or a validated launch. This page does not edit them.</p>
<?php if ( count($platform_rows) < 1 ) { ?>
<p>No platform snapshots for this <?= $platform_whose ?>.</p>
<?php } else { ?>
<ul>
<?php foreach ( $platform_rows as $platform_row ) {
    $platform_id = (int) $platform_row['platform_id'];
    $platform_bits = array((string) $platform_id, (string) $platform_row['source']);
    $platform_name = trim((string) ($platform_row['platform_name'] ?? ''));
    if ( $platform_name === '' ) $platform_name = trim((string) ($platform_row['platform_family'] ?? ''));
    if ( $platform_name !== '' ) $platform_bits[] = $platform_name;
    $platform_version = trim((string) ($platform_row['platform_version'] ?? ''));
    if ( $platform_version !== '' ) $platform_bits[] = $platform_version;
    $platform_when = trim((string) ($platform_row['created_at'] ?? ''));
    if ( $platform_when !== '' ) $platform_bits[] = $platform_when;
    $platform_roles = array();
    if ( $platform_id === $platform_current ) $platform_roles[] = 'current';
    if ( $platform_id === $platform_registration ) $platform_roles[] = 'original registration';
    $platform_label = implode(' ', $platform_bits);
    if ( count($platform_roles) > 0 ) $platform_label .= ' ('.implode(', ', $platform_roles).')';
?>
<li><a href="<?= $key_admin ?>platform-detail?platform_id=<?= $platform_id ?>"><?= htmlspecialchars($platform_label, ENT_QUOTES, 'UTF-8') ?></a></li>
<?php } ?>
</ul>
<?php } ?>
<?php } ?>
