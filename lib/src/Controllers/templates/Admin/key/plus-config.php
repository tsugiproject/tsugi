<?php

$plusOn = isset($row) && is_array($row) && (int) ($row['plus'] ?? 0) === 1;
$plusLaunchUrl = $CFG->wwwroot . '/plus';
?>
<div class="tsugi-plus-config"<?php if ( ! $plusOn ) { ?> style="display:none"<?php } ?>>
<h2>Tsugi Plus</h2>
<p>
Dynamic registration includes a resource link titled Tsugi Plus. Place that message.
An LMS can also place a Plus launch using a deep link request.
If the LMS does not place that message, use this target link URI.
</p>
<p>
<b>Target link URI:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($plusLaunchUrl) ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($plusLaunchUrl) ?>
</p>
</div>
<?php
if ( empty($plusConfigScriptSent) ) {
    $plusConfigScriptSent = true;
?>
<script>
(function () {
    var box = document.getElementById('plus');
    if ( ! box || box.type !== 'checkbox' ) return;
    function applyPlusConfig() {
        var nodes = document.querySelectorAll('.tsugi-plus-config');
        for ( var i = 0; i < nodes.length; i++ ) {
            nodes[i].style.display = box.checked ? '' : 'none';
        }
    }
    box.addEventListener('change', applyPlusConfig);
    applyPlusConfig();
})();
</script>
<?php
}
