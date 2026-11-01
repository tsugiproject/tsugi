<?php
/**
 * Edit one LTI 1.3 registration this course owns.
 *
 * Scopes, claims, and placements can be turned off without being deleted.
 *
 * Expected: $tool_edit, $edit_url, $tools_url, $view_url, $test_url, $save_url
 */
if ( ! isset($tool_edit) || ! is_array($tool_edit) ) {
    $tool_edit = array();
}
$registrationId = isset($tool_edit['registration_id']) ? (int) $tool_edit['registration_id'] : 0;
$title = isset($tool_edit['title']) ? (string) $tool_edit['title'] : '';
$deployments = isset($tool_edit['deployments']) && is_array($tool_edit['deployments']) ? $tool_edit['deployments'] : array();
$manyDeployments = count($deployments) > 1;
$editUrl = isset($edit_url) ? (string) $edit_url : '';

$button = function ($action, $hidden, $label, $class, $confirm = '', $live = '') use ($editUrl, $registrationId) {
    $short = '';
    if ( isset($hidden['deployment_short']) ) {
        $short = (string) $hidden['deployment_short'];
        unset($hidden['deployment_short']);
    }
    $hidden['edit_action'] = $action;
    $hidden['registration_id'] = (string) $registrationId;
    $html = '<form method="post" action="'.htmlspecialchars($editUrl).'" style="display:inline;"';
    if ( $live === 'toggle' || $live === 'delete' ) {
        $html .= ' class="lti13-live" data-lti13-live="'.$live.'"';
        if ( $live === 'toggle' ) {
            $on = strlen($action) >= 4 && substr($action, -4) === '_off';
            $html .= ' data-on="'.($on ? '1' : '0').'"';
        }
        if ( $short !== '' ) {
            $html .= ' data-short="'.htmlspecialchars($short).'"';
        }
    }
    if ( $confirm !== '' ) {
        $html .= ' data-confirm="'.htmlspecialchars($confirm).'" onsubmit="return confirm(this.getAttribute(\'data-confirm\'));"';
    }
    $html .= '>';
    $html .= \Tsugi\Controllers\Settings::csrfField();
    foreach ( $hidden as $name => $value ) {
        $html .= '<input type="hidden" name="'.htmlspecialchars((string) $name).'" value="'.htmlspecialchars((string) $value).'">';
    }
    $html .= '<button type="submit" class="'.htmlspecialchars($class).'">'.htmlspecialchars($label).'</button>';
    $html .= '</form>';
    return $html;
};

$switches = function ($onAction, $offAction, $hiddenKey, $hiddenValue, $grants, $extra = array()) use ($button, $manyDeployments) {
    if ( ! is_array($grants) || count($grants) === 0 ) {
        return '<span class="text-muted">'.htmlspecialchars(__('Off')).'</span>';
    }
    $html = '';
    $onCount = 0;
    foreach ( $grants as $grant ) {
        if ( ! empty($grant['on']) ) {
            $onCount++;
        }
    }
    if ( ! $manyDeployments ) {
        $grant = $grants[0];
        $html .= ! empty($grant['on'])
            ? '<span data-lti13-state>'.htmlspecialchars(__('On')).'</span> '
            : '<span class="text-muted" data-lti13-state>'.htmlspecialchars(__('Off')).'</span> ';
        $action = ! empty($grant['on']) ? $offAction : $onAction;
        $label = ! empty($grant['on']) ? __('Turn off') : __('Turn on');
        $class = ! empty($grant['on']) ? 'btn btn-default btn-sm' : 'btn btn-primary btn-sm';
        $fields = array(
            $hiddenKey => $hiddenValue,
            'tool_deployment_id' => (int) $grant['tool_deployment_id'],
        );
        foreach ( $extra as $name => $value ) {
            $fields[$name] = $value;
        }
        $html .= $button($action, $fields, $label, $class, '', 'toggle');
        return $html;
    }
    foreach ( $grants as $grant ) {
        $id = isset($grant['deployment_id']) ? (string) $grant['deployment_id'] : '';
        $short = $id === '' ? '' : substr($id, 0, 8);
        $action = ! empty($grant['on']) ? $offAction : $onAction;
        $label = ! empty($grant['on']) ? __('Turn off') : __('Turn on');
        if ( $short !== '' ) {
            $label .= ' '.$short;
        }
        $class = ! empty($grant['on']) ? 'btn btn-default btn-sm' : 'btn btn-primary btn-sm';
        $fields = array(
            $hiddenKey => $hiddenValue,
            'tool_deployment_id' => (int) $grant['tool_deployment_id'],
        );
        if ( $short !== '' ) {
            $fields['deployment_short'] = $short;
        }
        foreach ( $extra as $name => $value ) {
            $fields[$name] = $value;
        }
        $html .= $button($action, $fields, $label, $class, '', 'toggle').' ';
    }
    if ( $onCount === 0 ) {
        $html = '<span class="text-muted" data-lti13-state>'.htmlspecialchars(__('Off')).'</span> '.$html;
    } else {
        $html = '<span data-lti13-state hidden></span>'.$html;
    }
    return $html;
};

$rowOpen = function ($off, $attrs = array()) {
    $style = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 10px;padding:8px;border:1px solid #e5e5e5;';
    if ( $off ) {
        $style .= 'background:#f7f7f7;';
    }
    $html = '<div data-lti13-row="1" style="'.htmlspecialchars($style).'"';
    foreach ( $attrs as $name => $value ) {
        $html .= ' '.htmlspecialchars((string) $name).'="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"';
    }
    $html .= '>';
    return $html;
};

$customList = function ($items, $messageId = null) use ($button, $rowOpen) {
    if ( ! is_array($items) || count($items) === 0 ) {
        echo '<p class="text-muted">'.htmlspecialchars(__('None')).'</p>';
        return;
    }
    foreach ( $items as $name => $value ) {
        $fields = array('custom_name' => (string) $name);
        if ( $messageId !== null ) {
            $fields['message_id'] = (string) $messageId;
        }
        echo $rowOpen(false);
        echo '<span style="flex:1;min-width:16em;word-break:break-all;"><code>'.htmlspecialchars((string) $name).'</code> = '.htmlspecialchars((string) $value).'</span>';
        echo $button('delete_custom', $fields, __('Delete'), 'btn btn-danger btn-sm', __('Remove this custom parameter?'));
        echo '</div>';
    }
};
?>
<div id="lti13-editor" data-edit-url="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>">
<p><a href="<?= htmlspecialchars(isset($tools_url) ? (string) $tools_url : '') ?>"><?= __('Back to course tools') ?></a></p>
<h2 style="margin-top:0;"><?= htmlspecialchars($title) ?> <span class="text-muted">(LTI 1.3)</span></h2>
<?php
$missing = isset($tool_edit['missing']) && is_array($tool_edit['missing']) ? $tool_edit['missing'] : array();
if ( count($missing) > 0 ) { ?>
<p class="text-muted"><?= htmlspecialchars(__('Draft')) ?>. <?= htmlspecialchars(sprintf(__('Still needs %s.'), implode(', ', $missing))) ?></p>
<?php } ?>
<p><?= __('Turn off leaves an item on the tool and stops sending it. Delete removes it. Add puts a known value on the tool and turns it on. Turn off and delete stay on this page. Add reloads it.') ?></p>
<p id="lti13-live-status" role="status" aria-live="polite" hidden></p>

<h3><?= __('Tool') ?></h3>
<form method="post" action="<?= htmlspecialchars($editUrl) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="edit_action" value="save_profile">
    <div class="form-group">
        <label for="lti13_title"><?= __('Tool title') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_title" name="title" type="text" maxlength="512" required value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="lti13_login"><?= __('Login URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_login" name="oidc_login_url" type="url" value="<?= htmlspecialchars(isset($tool_edit['oidc_login_url']) ? (string) $tool_edit['oidc_login_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="lti13_jwks"><?= __('Keyset URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_jwks" name="jwks_url" type="url" value="<?= htmlspecialchars(isset($tool_edit['jwks_url']) ? (string) $tool_edit['jwks_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="lti13_launch"><?= __('Target link') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_launch" name="launch_url" type="url" value="<?= htmlspecialchars(isset($tool_edit['launch_url']) ? (string) $tool_edit['launch_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <p><button type="submit" class="btn btn-primary"><?= __('Save tool') ?></button></p>
</form>
<p class="text-muted" style="word-break:break-all;"><?= __('Client ID') ?>: <?= htmlspecialchars(isset($tool_edit['client_id']) ? (string) $tool_edit['client_id'] : '') ?></p>

<h3><?= __('Redirect URLs') ?></h3>
<div data-lti13-section="redirects">
<?php
$redirects = isset($tool_edit['redirect_uris']) && is_array($tool_edit['redirect_uris']) ? $tool_edit['redirect_uris'] : array();
foreach ( $redirects as $uri ) {
    echo $rowOpen(false, array(
        'data-kind' => 'redirect',
        'data-value' => (string) $uri,
    ));
    echo '<span style="word-break:break-all;flex:1;">'.htmlspecialchars((string) $uri).'</span>';
    echo $button('delete_redirect', array('redirect_uri' => (string) $uri), __('Delete'), 'btn btn-danger btn-sm', __('Remove this redirect URL?'), 'delete');
    echo '</div>';
}
?>
</div>
<form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="edit_action" value="add_redirect">
    <label for="lti13_redirect"><?= __('Add a redirect URL') ?></label>
    <div>
        <input class="form-control" style="max-width:36em;display:inline-block;" id="lti13_redirect" name="redirect_uri" type="url" required>
        <button type="submit" class="btn btn-default"><?= __('Add') ?></button>
    </div>
</form>

<h3><?= __('Custom parameters') ?></h3>
<p class="text-muted"><?= __('Sent on every launch. A message parameter with the same name replaces this one. A value can include $Context.id. A substitution this platform does not fill is sent as written.') ?></p>
<?php $customList(isset($tool_edit['custom_parameters']) ? $tool_edit['custom_parameters'] : array()); ?>
<form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="edit_action" value="add_custom">
    <div class="form-group">
        <label for="lti13_custom_name"><?= __('Name') ?></label>
        <input class="form-control" style="max-width:24em;" id="lti13_custom_name" name="custom_name" type="text" maxlength="255" required>
    </div>
    <div class="form-group">
        <label for="lti13_custom_value"><?= __('Value') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_custom_value" name="custom_value" type="text" maxlength="2048">
    </div>
    <p><button type="submit" class="btn btn-default"><?= __('Add') ?></button></p>
</form>

<h3><?= __('Scopes') ?></h3>
<?php
$scopes = isset($tool_edit['scopes']) && is_array($tool_edit['scopes']) ? $tool_edit['scopes'] : array();
foreach ( $scopes as $scope ) {
    $grants = isset($scope['deployments']) && is_array($scope['deployments']) ? $scope['deployments'] : array();
    $off = true;
    foreach ( $grants as $grant ) {
        if ( ! empty($grant['on']) ) {
            $off = false;
        }
    }
    echo $rowOpen($off, array(
        'data-kind' => 'scope',
        'data-value' => (string) $scope['value'],
        'data-label' => (string) $scope['label'],
    ));
    echo '<span data-lti13-name style="flex:1;min-width:16em;'.($off ? 'color:#777;' : '').'">';
    echo htmlspecialchars((string) $scope['label']);
    echo '<br><span class="text-muted" style="font-size:12px;word-break:break-all;">'.htmlspecialchars((string) $scope['value']).'</span>';
    echo '</span>';
    echo $switches('scope_on', 'scope_off', 'scope', (string) $scope['value'], $grants);
    echo ' '.$button('delete_scope', array('scope' => (string) $scope['value']), __('Delete'), 'btn btn-danger btn-sm', __('Remove this scope from the tool?'), 'delete');
    echo '</div>';
}
$scopeAvailable = isset($tool_edit['scope_available']) && is_array($tool_edit['scope_available']) ? $tool_edit['scope_available'] : array();
echo '<div data-lti13-available="scopes">';
echo '<p class="text-muted" data-lti13-available-note'.(count($scopeAvailable) > 0 ? '' : ' hidden').'>'.htmlspecialchars(__('Not on this tool')).'</p>';
foreach ( $scopeAvailable as $scope ) {
    echo $rowOpen(true);
    echo '<span style="flex:1;min-width:16em;color:#777;">'.htmlspecialchars((string) $scope['label']).'</span>';
    echo $button('add_scope', array('scope' => (string) $scope['value']), __('Add'), 'btn btn-default btn-sm');
    echo '</div>';
}
echo '</div>';
?>

<h3><?= __('Claims') ?></h3>
<?php
$claims = isset($tool_edit['claims']) && is_array($tool_edit['claims']) ? $tool_edit['claims'] : array();
foreach ( $claims as $claim ) {
    $locked = ! empty($claim['locked']);
    $grants = isset($claim['deployments']) && is_array($claim['deployments']) ? $claim['deployments'] : array();
    $off = ! $locked;
    foreach ( $grants as $grant ) {
        if ( ! empty($grant['on']) ) {
            $off = false;
        }
    }
    echo $rowOpen($off, array(
        'data-kind' => 'claim',
        'data-value' => (string) $claim['value'],
        'data-label' => (string) $claim['label'],
    ));
    echo '<span data-lti13-name style="flex:1;min-width:16em;'.($off ? 'color:#777;' : '').'">'.htmlspecialchars((string) $claim['label']).'</span>';
    if ( $locked ) {
        echo '<span class="text-muted">'.htmlspecialchars(__('Always sent')).'</span>';
    } else {
        echo $switches('claim_on', 'claim_off', 'claim', (string) $claim['value'], $grants);
        echo ' '.$button('delete_claim', array('claim' => (string) $claim['value']), __('Delete'), 'btn btn-danger btn-sm', __('Remove this claim from the tool?'), 'delete');
    }
    echo '</div>';
}
$claimAvailable = isset($tool_edit['claim_available']) && is_array($tool_edit['claim_available']) ? $tool_edit['claim_available'] : array();
echo '<div data-lti13-available="claims">';
echo '<p class="text-muted" data-lti13-available-note'.(count($claimAvailable) > 0 ? '' : ' hidden').'>'.htmlspecialchars(__('Not on this tool')).'</p>';
foreach ( $claimAvailable as $claim ) {
    echo $rowOpen(true);
    echo '<span style="flex:1;min-width:16em;color:#777;">'.htmlspecialchars((string) $claim['label']).'</span>';
    echo $button('add_claim', array('claim' => (string) $claim['value']), __('Add'), 'btn btn-default btn-sm');
    echo '</div>';
}
echo '</div>';
?>

<h3><?= __('Messages') ?></h3>
<?php
$messages = isset($tool_edit['messages']) && is_array($tool_edit['messages']) ? $tool_edit['messages'] : array();
echo '<p class="text-muted" data-lti13-messages-empty'.(count($messages) === 0 ? '' : ' hidden').'>'.htmlspecialchars(__('The tool did not declare any messages.')).'</p>';
foreach ( $messages as $message ) {
    $messageId = (int) $message['message_id'];
    ?>
<div data-lti13-message="<?= $messageId ?>" style="border:1px solid #ddd;padding:12px;margin:0 0 16px;">
    <p style="margin-top:0;"><b><?= htmlspecialchars((string) $message['type_label']) ?></b>
    <span class="text-muted"><?= htmlspecialchars((string) $message['message_type']) ?></span></p>
    <form method="post" action="<?= htmlspecialchars($editUrl) ?>">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
        <input type="hidden" name="edit_action" value="save_message">
        <input type="hidden" name="message_id" value="<?= $messageId ?>">
        <div class="form-group">
            <label for="lti13_label_<?= $messageId ?>"><?= __('Label') ?></label>
            <input class="form-control" style="max-width:40em;" id="lti13_label_<?= $messageId ?>" name="label" type="text" maxlength="512" value="<?= htmlspecialchars((string) $message['label'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="lti13_target_<?= $messageId ?>"><?= __('Target link') ?></label>
            <input class="form-control" style="max-width:40em;" id="lti13_target_<?= $messageId ?>" name="target_link_uri" type="url" value="<?= htmlspecialchars((string) $message['target_link_uri'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <p><button type="submit" class="btn btn-primary btn-sm"><?= __('Save message') ?></button></p>
    </form>
    <p><?= $button('delete_message', array('message_id' => (string) $messageId), __('Delete message'), 'btn btn-danger btn-sm', __('Delete this message?'), 'delete') ?></p>
    <h4><?= __('Placements') ?></h4>
    <div data-lti13-placements>
    <?php
    $placements = isset($message['placements']) && is_array($message['placements']) ? $message['placements'] : array();
    echo '<p class="text-muted" data-lti13-none'.(count($placements) === 0 ? '' : ' hidden').'>'.htmlspecialchars(__('None')).'</p>';
    foreach ( $placements as $placement ) {
        $grants = isset($placement['deployments']) && is_array($placement['deployments']) ? $placement['deployments'] : array();
        $off = true;
        foreach ( $grants as $grant ) {
            if ( ! empty($grant['on']) ) {
                $off = false;
            }
        }
        $group = isset($placement['group']) ? (string) $placement['group'] : '';
        $identifier = (string) $placement['value'];
        $detail = $group === '' ? $identifier : $group.' · '.$identifier;
        echo $rowOpen($off, array(
            'data-kind' => 'placement',
            'data-value' => $identifier,
            'data-label' => (string) $placement['label'],
            'data-group' => $group,
            'data-menu' => empty($placement['menu']) ? '0' : '1',
        ));
        echo '<span data-lti13-name style="flex:1;min-width:12em;'.($off ? 'color:#777;' : '').'">';
        echo htmlspecialchars((string) $placement['label']);
        if ( $detail !== (string) $placement['label'] ) {
            echo '<br><span class="text-muted" style="font-size:12px;word-break:break-all;">'.htmlspecialchars($detail).'</span>';
        }
        echo '</span>';
        echo $switches('placement_on', 'placement_off', 'message_placement_id', (string) $placement['message_placement_id'], $grants, array(
            'message_id' => (string) $messageId,
        ));
        echo ' '.$button('delete_placement', array(
            'message_id' => (string) $messageId,
            'message_placement_id' => (string) $placement['message_placement_id'],
        ), __('Delete'), 'btn btn-danger btn-sm', __('Remove this placement from the message?'), 'delete');
        echo '</div>';
    }
    echo '</div>';
    $placementGroups = isset($message['placement_groups']) && is_array($message['placement_groups']) ? $message['placement_groups'] : array();
    ?>
    <form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;" data-lti13-placement-add<?= count($placementGroups) > 0 ? '' : ' hidden' ?>>
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
        <input type="hidden" name="edit_action" value="add_placement">
        <input type="hidden" name="message_id" value="<?= $messageId ?>">
        <label for="lti13_placement_<?= $messageId ?>"><?= __('Add a placement') ?></label>
        <div>
            <select class="form-control" style="max-width:28em;display:inline-block;" id="lti13_placement_<?= $messageId ?>" name="placement">
                <?php foreach ( $placementGroups as $group ) {
                    $options = isset($group['placements']) && is_array($group['placements']) ? $group['placements'] : array();
                    if ( count($options) === 0 ) {
                        continue;
                    }
                    ?>
                <optgroup label="<?= htmlspecialchars((string) $group['label']) ?>">
                    <?php foreach ( $options as $placement ) {
                        $option = (string) $placement['label'];
                        if ( (string) $placement['value'] !== $option ) {
                            $option .= ' ('.(string) $placement['value'].')';
                        }
                        ?>
                    <option value="<?= htmlspecialchars((string) $placement['value']) ?>"><?= htmlspecialchars($option) ?></option>
                    <?php } ?>
                </optgroup>
                <?php } ?>
            </select>
            <button type="submit" class="btn btn-default btn-sm"><?= __('Add') ?></button>
        </div>
    </form>
    <form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
        <input type="hidden" name="edit_action" value="add_placement">
        <input type="hidden" name="message_id" value="<?= $messageId ?>">
        <label for="lti13_placement_custom_<?= $messageId ?>"><?= __('Other placement') ?></label>
        <div>
            <input class="form-control" style="max-width:28em;display:inline-block;" id="lti13_placement_custom_<?= $messageId ?>" name="placement" type="text" maxlength="255" required placeholder="<?= htmlspecialchars(__('Placement identifier'), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-default btn-sm"><?= __('Add') ?></button>
        </div>
        <p class="text-muted"><?= __('Stored exactly as typed. Use this for a platform this list does not name.') ?></p>
    </form>
    <h4><?= __('Custom parameters') ?></h4>
    <p class="text-muted"><?= __('Sent when this message is launched. These replace a tool parameter with the same name.') ?></p>
    <?php
    $messageCustom = isset($message['custom_parameters']) && is_array($message['custom_parameters']) ? $message['custom_parameters'] : array();
    $customList($messageCustom, $messageId);
    ?>
    <form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;">
        <?= \Tsugi\Controllers\Settings::csrfField() ?>
        <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
        <input type="hidden" name="edit_action" value="add_custom">
        <input type="hidden" name="message_id" value="<?= $messageId ?>">
        <div class="form-group">
            <label for="lti13_custom_name_<?= $messageId ?>"><?= __('Name') ?></label>
            <input class="form-control" style="max-width:24em;" id="lti13_custom_name_<?= $messageId ?>" name="custom_name" type="text" maxlength="255" required>
        </div>
        <div class="form-group">
            <label for="lti13_custom_value_<?= $messageId ?>"><?= __('Value') ?></label>
            <input class="form-control" style="max-width:40em;" id="lti13_custom_value_<?= $messageId ?>" name="custom_value" type="text" maxlength="2048">
        </div>
        <p><button type="submit" class="btn btn-default btn-sm"><?= __('Add') ?></button></p>
    </form>
</div>
<?php } ?>
<form method="post" action="<?= htmlspecialchars($editUrl) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="edit_action" value="add_message">
    <h4><?= __('Add a message') ?></h4>
    <div class="form-group">
        <label for="lti13_new_type"><?= __('Type') ?></label>
        <select class="form-control" style="max-width:24em;" id="lti13_new_type" name="message_type">
            <?php
            $messageTypes = isset($tool_edit['message_types']) && is_array($tool_edit['message_types']) ? $tool_edit['message_types'] : array();
            foreach ( $messageTypes as $type ) { ?>
            <option value="<?= htmlspecialchars((string) $type['value']) ?>"><?= htmlspecialchars((string) $type['label']) ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="lti13_new_label"><?= __('Label') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_new_label" name="label" type="text" maxlength="512">
    </div>
    <div class="form-group">
        <label for="lti13_new_target"><?= __('Target link') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_new_target" name="target_link_uri" type="url">
    </div>
    <p><button type="submit" class="btn btn-default"><?= __('Add message') ?></button></p>
</form>

<?php include __DIR__.'/platform_urls.inc.php'; ?>
<?php
$registration_json = isset($tool_edit['registration_json']) ? (string) $tool_edit['registration_json'] : '';
$registration_logs = isset($tool_edit['registration_logs']) && is_array($tool_edit['registration_logs']) ? $tool_edit['registration_logs'] : array();
include __DIR__.'/registration_sources.inc.php';
?>
<p style="margin-top:16px;">
<?php if ( $registrationId > 0 && isset($view_url) && is_string($view_url) && $view_url !== '' ) {
    $viewSep = strpos($view_url, '?') === false ? '?' : '&';
    ?>
<a class="btn btn-default" href="<?= htmlspecialchars($view_url.$viewSep.'registration_id='.$registrationId) ?>"><?= __('View') ?></a>
<?php } ?>
<?php if ( $registrationId > 0 && isset($test_url) && is_string($test_url) && $test_url !== '' ) {
    $testSep = strpos($test_url, '?') === false ? '?' : '&';
    ?>
<a class="btn btn-default" href="<?= htmlspecialchars($test_url.$testSep.'registration_id='.$registrationId) ?>"><?= __('Test') ?></a>
<?php } ?>
<?php if ( $registrationId > 0 && isset($save_url) && is_string($save_url) && $save_url !== '' ) { ?>
<form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;" data-confirm="<?= htmlspecialchars(__('Delete this tool from the course?')) ?>" onsubmit="return confirm(this.getAttribute('data-confirm'));">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="tool_action" value="delete">
    <button type="submit" class="btn btn-danger"><?= __('Delete') ?></button>
</form>
<?php } ?>
</p>
</div>
<script>
(function () {
    var root = document.getElementById('lti13-editor');
    if (!root) {
        return;
    }
    var text = <?= json_encode(array(
        'on' => __('On'),
        'off' => __('Off'),
        'turnOn' => __('Turn on'),
        'turnOff' => __('Turn off'),
        'add' => __('Add'),
        'failed' => __('Could not save the tool.'),
        'groups' => array(__('Standard LTI'), __('Canvas'), __('Tsugi'), __('Other')),
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function showStatus(message, isError) {
        var el = document.getElementById('lti13-live-status');
        if (!el) {
            return;
        }
        if (!message) {
            el.hidden = true;
            el.textContent = '';
            return;
        }
        el.hidden = false;
        el.className = isError ? 'alert alert-danger' : 'text-muted';
        el.textContent = message;
        if (isError) {
            el.scrollIntoView({block: 'nearest'});
        }
    }

    function finish(form) {
        form.removeAttribute('data-busy');
        var button = form.querySelector('button');
        if (button) {
            button.disabled = false;
        }
    }

    function paintRow(row) {
        if (!row) {
            return;
        }
        var toggles = row.querySelectorAll('form[data-lti13-live="toggle"]');
        var anyOn = false;
        for (var i = 0; i < toggles.length; i++) {
            if (toggles[i].getAttribute('data-on') === '1') {
                anyOn = true;
            }
        }
        row.style.background = anyOn ? '' : '#f7f7f7';
        var name = row.querySelector('[data-lti13-name]');
        if (name) {
            name.style.color = anyOn ? '' : '#777';
        }
        var mark = row.querySelector('[data-lti13-state]');
        if (!mark || toggles.length === 0) {
            return;
        }
        if (toggles.length === 1) {
            mark.hidden = false;
            mark.className = anyOn ? '' : 'text-muted';
            mark.textContent = anyOn ? text.on : text.off;
            return;
        }
        mark.className = 'text-muted';
        mark.hidden = anyOn;
        mark.textContent = anyOn ? '' : text.off;
    }

    function afterToggle(form, sent) {
        var nowOn = /_on$/.test(sent);
        form.setAttribute('data-on', nowOn ? '1' : '0');
        var action = form.querySelector('input[name="edit_action"]');
        if (action) {
            action.value = nowOn ? sent.replace(/_on$/, '_off') : sent.replace(/_off$/, '_on');
        }
        var button = form.querySelector('button');
        if (button) {
            var short = form.getAttribute('data-short') || '';
            button.textContent = (nowOn ? text.turnOff : text.turnOn) + (short ? ' ' + short : '');
            button.className = nowOn ? 'btn btn-default btn-sm' : 'btn btn-primary btn-sm';
        }
        paintRow(form.closest('[data-lti13-row]'));
    }

    function hiddenInput(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }

    function addButton(action, fields) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = root.getAttribute('data-edit-url') || '';
        form.style.display = 'inline';
        var token = root.querySelector('input[name="CSRF_TOKEN"]');
        var registration = root.querySelector('input[name="registration_id"]');
        form.appendChild(hiddenInput('CSRF_TOKEN', token ? token.value : ''));
        form.appendChild(hiddenInput('registration_id', registration ? registration.value : ''));
        form.appendChild(hiddenInput('edit_action', action));
        Object.keys(fields).forEach(function (name) {
            form.appendChild(hiddenInput(name, fields[name]));
        });
        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn btn-default btn-sm';
        button.textContent = text.add;
        form.appendChild(button);
        return form;
    }

    function greyRow() {
        var div = document.createElement('div');
        div.setAttribute('data-lti13-row', '1');
        div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 10px;padding:8px;border:1px solid #e5e5e5;background:#f7f7f7;';
        return div;
    }

    function restoreCatalog(row, kind) {
        var section = root.querySelector('[data-lti13-available="' + kind + '"]');
        var value = row.getAttribute('data-value') || '';
        var field = kind === 'scopes' ? 'scope' : 'claim';
        if (section && value !== '') {
            var existing = section.querySelectorAll('input[name="' + field + '"]');
            var seen = false;
            for (var i = 0; i < existing.length; i++) {
                if (existing[i].value === value) {
                    seen = true;
                }
            }
            if (!seen) {
                var note = section.querySelector('[data-lti13-available-note]');
                if (note) {
                    note.hidden = false;
                }
                var div = greyRow();
                var span = document.createElement('span');
                span.style.cssText = 'flex:1;min-width:16em;color:#777;';
                span.textContent = row.getAttribute('data-label') || value;
                div.appendChild(span);
                var fields = {};
                fields[field] = value;
                div.appendChild(addButton(kind === 'scopes' ? 'add_scope' : 'add_claim', fields));
                section.appendChild(div);
            }
        }
        row.remove();
    }

    function hasOption(select, value) {
        var options = select.options;
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === value) {
                return true;
            }
        }
        return false;
    }

    function restoreOption(card, row) {
        var form = card.querySelector('[data-lti13-placement-add]');
        if (!form) {
            return;
        }
        var select = form.querySelector('select');
        var value = row.getAttribute('data-value') || '';
        if (!select || value === '' || hasOption(select, value)) {
            form.hidden = false;
            return;
        }
        var groupName = row.getAttribute('data-group') || '';
        var label = row.getAttribute('data-label') || value;
        var group = null;
        var groups = select.querySelectorAll('optgroup');
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].label === groupName) {
                group = groups[i];
            }
        }
        if (!group) {
            group = document.createElement('optgroup');
            group.label = groupName;
            var index = text.groups.indexOf(groupName);
            var placed = false;
            for (var j = 0; j < groups.length; j++) {
                var other = text.groups.indexOf(groups[j].label);
                if (index !== -1 && (other === -1 || other > index)) {
                    select.insertBefore(group, groups[j]);
                    placed = true;
                    break;
                }
            }
            if (!placed) {
                select.appendChild(group);
            }
        }
        var option = document.createElement('option');
        option.value = value;
        option.textContent = label !== value ? label + ' (' + value + ')' : label;
        group.appendChild(option);
        form.hidden = false;
    }

    function afterDelete(form) {
        var row = form.closest('[data-lti13-row]');
        var kind = row ? row.getAttribute('data-kind') : '';
        if (kind === 'scope' || kind === 'claim') {
            restoreCatalog(row, kind === 'scope' ? 'scopes' : 'claims');
            return;
        }
        if (kind === 'redirect') {
            row.remove();
            return;
        }
        if (kind === 'placement') {
            var card = form.closest('[data-lti13-message]');
            if (row.getAttribute('data-menu') === '1' && card) {
                restoreOption(card, row);
            }
            row.remove();
            if (card) {
                var list = card.querySelector('[data-lti13-placements]');
                if (list && !list.querySelector('[data-lti13-row]')) {
                    var none = list.querySelector('[data-lti13-none]');
                    if (none) {
                        none.hidden = false;
                    }
                }
            }
            return;
        }
        var message = form.closest('[data-lti13-message]');
        if (message) {
            message.remove();
        }
        var left = root.querySelectorAll('[data-lti13-message]');
        var empty = root.querySelector('[data-lti13-messages-empty]');
        if (empty) {
            empty.hidden = left.length !== 0;
        }
    }

    function postForm(form) {
        var actionInput = form.querySelector('input[name="edit_action"]');
        var sent = actionInput ? actionInput.value : '';
        var button = form.querySelector('button');
        if (button) {
            button.disabled = true;
        }
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json().then(function (data) {
                return {ok: response.ok && data && data.ok === true, data: data || {}};
            }, function () {
                return {ok: false, data: {}};
            });
        }).then(function (result) {
            finish(form);
            if (!result.ok) {
                showStatus((result.data && result.data.error) || text.failed, true);
                return;
            }
            showStatus(result.data.message || '', false);
            if (form.getAttribute('data-lti13-live') === 'toggle') {
                afterToggle(form, sent);
            } else {
                afterDelete(form);
            }
        }).catch(function () {
            finish(form);
            showStatus(text.failed, true);
        });
    }

    root.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.getAttribute || !form.getAttribute('data-lti13-live')) {
            return;
        }
        if (event.defaultPrevented) {
            return;
        }
        event.preventDefault();
        if (form.getAttribute('data-busy') === '1') {
            return;
        }
        form.setAttribute('data-busy', '1');
        postForm(form);
    });
})();
</script>
