<?php
/**
 * Typed confirmation before a destructive admin delete.
 *
 * $delete_box keys:
 *   title, paragraphs (list of strings), lines (list of strings),
 *   fields (name, label, kind=domain|url|count, expected),
 *   help, mismatch, confirm, button, hidden (name => value),
 *   lead (HTML shown in front of the Delete button)
 */
if ( ! isset($delete_box) || ! is_array($delete_box) ) return;

$delete_title = (string) ($delete_box['title'] ?? '');
$delete_paragraphs = isset($delete_box['paragraphs']) && is_array($delete_box['paragraphs']) ? $delete_box['paragraphs'] : array();
$delete_lines = isset($delete_box['lines']) && is_array($delete_box['lines']) ? $delete_box['lines'] : array();
$delete_fields = isset($delete_box['fields']) && is_array($delete_box['fields']) ? $delete_box['fields'] : array();
$delete_help = (string) ($delete_box['help'] ?? '');
$delete_mismatch = (string) ($delete_box['mismatch'] ?? '');
$delete_confirm_text = (string) ($delete_box['confirm'] ?? '');
$delete_hidden = isset($delete_box['hidden']) && is_array($delete_box['hidden']) ? $delete_box['hidden'] : array();
$delete_blocked = (string) ($delete_box['blocked'] ?? '');
$delete_spec = array();
foreach ( $delete_fields as $delete_field ) {
    if ( ! is_array($delete_field) ) continue;
    $delete_spec[] = array(
        'name' => (string) ($delete_field['name'] ?? ''),
        'kind' => (string) ($delete_field['kind'] ?? ''),
        'expected' => $delete_field['expected'] ?? '',
    );
}
$delete_form_id = 'tsugi-delete-'.substr(sha1($delete_title.'|'.$delete_blocked), 0, 8);
$delete_section = (string) ($delete_box['section'] ?? 'all');
if ( $delete_section === 'button' ) {
    $delete_lead = $delete_box['lead'] ?? '';
?>
<p style="margin-top:16px;">
<?php if ( is_string($delete_lead) && $delete_lead !== '' ) echo $delete_lead; ?>
<button type="button" class="btn btn-danger" id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>-open">Delete</button>
</p>
<?php return; }
if ( $delete_section === 'form' ) {
    if ( $delete_blocked === '' ) { ?>
<form id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>" method="post" autocomplete="off">
    <?= \Tsugi\Controllers\Tool::csrfField() ?>
    <input type="hidden" name="doDelete" value="Delete">
    <?php foreach ( $delete_hidden as $delete_name => $delete_value ) { ?>
    <input type="hidden" name="<?= htmlspecialchars((string) $delete_name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $delete_value, ENT_QUOTES, 'UTF-8') ?>">
    <?php } ?>
</form>
<?php }
    require __DIR__ . '/delete-confirm-script.inc.php';
    return;
}
?>
<div id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>-panel" style="display:none;">
<?php if ( $delete_blocked !== '' ) { ?>
<div class="alert alert-warning" role="alert">
    <p style="margin-bottom:0;"><?= htmlspecialchars($delete_blocked, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php } else { ?>
<div class="alert alert-danger" role="alert" style="margin-top:16px;padding:18px 20px;border-width:3px;">
    <p style="font-size:1.75em;font-weight:bold;margin:0 0 12px 0;line-height:1.2;"><?= htmlspecialchars($delete_title, ENT_QUOTES, 'UTF-8') ?></p>
    <?php foreach ( $delete_paragraphs as $delete_paragraph ) { ?>
    <p style="font-size:1.15em;margin-bottom:10px;"><?= htmlspecialchars((string) $delete_paragraph, ENT_QUOTES, 'UTF-8') ?></p>
    <?php } ?>
    <?php foreach ( $delete_lines as $delete_line ) { ?>
    <p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;"><?= htmlspecialchars((string) $delete_line, ENT_QUOTES, 'UTF-8') ?></p>
    <?php } ?>
        <?php foreach ( $delete_fields as $delete_index => $delete_field ) {
            if ( ! is_array($delete_field) ) continue;
            $delete_name = (string) ($delete_field['name'] ?? '');
            $delete_label = (string) ($delete_field['label'] ?? '');
            $delete_id = $delete_form_id.'-'.$delete_index;
            $delete_numeric = (($delete_field['kind'] ?? '') === 'count');
        ?>
        <p style="font-size:1.1em;margin-bottom:8px;">
            <label for="<?= htmlspecialchars($delete_id, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($delete_label, ENT_QUOTES, 'UTF-8') ?></label>
        </p>
        <p style="margin-bottom:12px;">
            <input type="text" id="<?= htmlspecialchars($delete_id, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($delete_name, ENT_QUOTES, 'UTF-8') ?>" form="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"<?= $delete_numeric ? ' inputmode="numeric"' : '' ?>>
        </p>
        <?php } ?>
        <p id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>-help" class="help-block" style="margin-top:8px;"><?= htmlspecialchars($delete_help, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin-bottom:0;">
            <button type="submit" class="btn btn-danger" id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>-go" form="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>" disabled>Actually Delete</button>
            <button type="button" class="btn btn-default" style="margin-left:6px;" id="<?= htmlspecialchars($delete_form_id, ENT_QUOTES, 'UTF-8') ?>-cancel">Cancel</button>
        </p>
</div>
<?php } ?>
</div>
