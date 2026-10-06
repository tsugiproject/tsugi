<?php
/** @var array<int, \stdClass> $clusterServers */

if ( count($clusterServers) < 1 ) {
    echo "<p>No cluster information found</p>\n";
    return;
}
?>
<ul id="cluster_ul">
<?php foreach ( $clusterServers as $server ) {
    $ipId = isset($server->ipaddrid) ? (string) $server->ipaddrid : '';
    $tools = (isset($server->tools) && is_array($server->tools)) ? $server->tools : array();
    $pending = (isset($server->install) && is_array($server->install)) ? $server->install : array();
?>
    <li><?= htmlentities((string) $server->ipaddr) ?>
    <?php if ( ! empty($server->local) ) { ?>
        (this server)
    <?php } ?>
    <a href="#" onclick="$('#tools_<?= htmlentities($ipId) ?>').toggle(); return false;">(toggle)</a>
    <div id="tools_<?= htmlentities($ipId) ?>" style="display:none;">
    <?php if ( count($pending) > 0 ) { ?>
        <ul>
        <?php foreach ( $pending as $job ) { ?>
            <li><p>Scheduled for install: <?= htmlentities((string) ($job['clone_url'] ?? '')) ?> (<?= htmlentities((string) ($job['created_at'] ?? '')) ?>)</p></li>
        <?php } ?>
        </ul>
    <?php } ?>
    <?php if ( count($tools) > 0 ) { ?>
        <ul>
        <?php foreach ( $tools as $i => $tool ) {
            $detailId = 'commit_'.$ipId.'_'.(int) $i;
            $commit = (string) ($tool['commit'] ?? '');
        ?>
            <li><p><?= htmlentities((string) ($tool['name'] ?? '')) ?>
            <br/>Updated at: <?= htmlentities((string) ($tool['updated_at'] ?? '')) ?>
            <br/>Branch: <?= htmlentities((string) ($tool['gitversion'] ?? '')) ?>
            <br/>Commit:
            <a href="#" onclick="showModal('Commit Detail','<?= htmlentities($detailId) ?>'); return false;">
            <?= htmlentities($commit) ?></a>
            <pre id="<?= htmlentities($detailId) ?>" style="display:none;">
            <?= htmlentities((string) ($tool['status_note'] ?? '')) ?>
            <?= htmlentities((string) ($tool['commit_log'] ?? '')) ?>
            </pre>
            </p></li>
        <?php } ?>
        </ul>
    <?php } else { ?>
        <p>No tools found</p>
    <?php } ?>
    </div>
    </li>
<?php } ?>
</ul>
