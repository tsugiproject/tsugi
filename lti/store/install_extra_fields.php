<?php
// Shared install form fields used in store index/details modals.
// Required inputs from including scope:
// $id_suffix, $grade_launch, $accept_lineitem, $deeplink, $accept_available, $accept_submission
// Optional: $tool — when $tool['custom'] is set, render a dropdown per custom key
$id_attr = htmlspecialchars((string) $id_suffix);
$id_js = json_encode((string) $id_suffix);

// Optional custom key/value choices from register.php, e.g.:
//   "custom" => array(
//       "exercise" => array(
//           "HeadingsAndParagraphs" => "HTML: Headings and Paragraphs",
//           "HelloWorld" => "JavaScript: Hello World",
//       ),
//   ),
$tool_custom = (isset($tool) && is_array($tool) && isset($tool['custom']) && is_array($tool['custom']))
    ? $tool['custom'] : false;
if ( $tool_custom ) {
    foreach ( $tool_custom as $custom_key => $custom_values ) {
        if ( ! is_string($custom_key) || $custom_key === '' || ! is_array($custom_values) || count($custom_values) < 1 ) {
            continue;
        }
        // Skip non value=>label maps (e.g. nested metadata)
        $options = array();
        foreach ( $custom_values as $value => $label ) {
            if ( ! is_scalar($value) || ! is_scalar($label) ) continue;
            $options[(string) $value] = (string) $label;
        }
        if ( count($options) < 1 ) continue;
        $field_id = 'custom_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $custom_key) . '_' . $id_attr;
        $field_name = 'custom_' . $custom_key;
        $field_label = ucwords(str_replace('_', ' ', $custom_key));
?>
<div class="form-group">
    <label for="<?= htmlspecialchars($field_id) ?>"><?= htmlspecialchars($field_label) ?></label>
    <select class="form-control" name="<?= htmlspecialchars($field_name) ?>" id="<?= htmlspecialchars($field_id) ?>">
        <option value="">— Select <?= htmlspecialchars($field_label) ?> (optional) —</option>
<?php foreach ( $options as $value => $label ) { ?>
        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
<?php } ?>
    </select>
</div>
<?php
    }
}
?>
<?php if ( $grade_launch && $accept_lineitem ) { ?>
<div class="form-group">
    <label for="lineitem_<?= $id_attr ?>">Configure LineItem</label> (Not all LMS placements support all features)
    <select name="lineitem" id="lineitem_<?= $id_attr ?>" onchange="toggleLineItem(this, <?= $id_js ?>);">
        <option value="none">No LineItem</option>
        <option value="send">Send LineItem</option>
    </select>
</div>
<div class="lineitem-fields" id="lineitem-fields_<?= $id_attr ?>" style="display:none;">
    <div class="form-group">
        <label for="scoreMaximum_<?= $id_attr ?>">Maximum possible score for an activity.</label>
        <input type="number" class="form-control" id="scoreMaximum_<?= $id_attr ?>" name="scoreMaximum">
    </div>
    <div class="form-group" for="resourceId_<?= $id_attr ?>">
        <label>Tool provided ID for the resource. (optional) This is opaque to the LMS.</label>
        <input type="text" class="form-control" id="resourceId_<?= $id_attr ?>" name="resourceId">
    </div>
    <div class="form-group">
        <label for="tag_<?= $id_attr ?>">A tag used to mark this item. (optional) This is opaque to the LMS</label>
        <input type="text" class="form-control" id="tag_<?= $id_attr ?>" name="tag">
    </div>
</div>
<?php } ?>
<?php /* ResourceLink.available.* / ResourceLink.submission.* on ltiResourceLink (LTI DL 2.0), independent of lineItem */ ?>
<?php if ( $deeplink && ( $accept_available || $accept_submission ) ) { ?>
<?php if ( $accept_available ) { ?>
<div class="form-group">
    <label for="availableStart_<?= $id_attr ?>">Available window (optional)</label>
    <div>
        <input type="date" id="availableStart_<?= $id_attr ?>" name="availableStartLocal" data-utc-target="availableStart" data-default-time="00:00" data-lineitem-target="lineitem_<?= $id_attr ?>" title="ResourceLink.available.startDateTime"> —
        <input type="date" id="availableEnd_<?= $id_attr ?>" name="availableEndLocal" data-utc-target="availableEnd" data-default-time="23:59" data-lineitem-target="lineitem_<?= $id_attr ?>" title="ResourceLink.available.endDateTime">
        <input type="hidden" name="availableStart">
        <input type="hidden" name="availableEnd">
    </div>
    <p class="help-block"><small>Dates use your browser's local timezone defaults: start at 00:00, end at 23:59, then convert to UTC.</small></p>
</div>
<?php } ?>
<?php if ( $accept_submission ) { ?>
<div class="form-group">
    <label for="submissionStart_<?= $id_attr ?>">Submission window (optional)</label>
    <div>
        <input type="date" id="submissionStart_<?= $id_attr ?>" name="submissionStartLocal" data-utc-target="submissionStart" data-default-time="00:00" data-lineitem-target="lineitem_<?= $id_attr ?>" title="ResourceLink.submission.startDateTime"> —
        <input type="date" id="submissionEnd_<?= $id_attr ?>" name="submissionEndLocal" data-utc-target="submissionEnd" data-default-time="23:59" data-lineitem-target="lineitem_<?= $id_attr ?>" title="ResourceLink.submission.endDateTime">
        <input type="hidden" name="submissionStart">
        <input type="hidden" name="submissionEnd">
    </div>
    <p class="help-block"><small>Dates use your browser's local timezone defaults: start at 00:00, end at 23:59, then convert to UTC.</small></p>
</div>
<?php } ?>
<?php } ?>
