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

$button = function ($action, $hidden, $label, $class, $confirm = '') use ($editUrl, $registrationId) {
    $hidden['edit_action'] = $action;
    $hidden['registration_id'] = (string) $registrationId;
    $html = '<form method="post" action="'.htmlspecialchars($editUrl).'" style="display:inline;"';
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
            ? '<span>'.htmlspecialchars(__('On')).'</span> '
            : '<span class="text-muted">'.htmlspecialchars(__('Off')).'</span> ';
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
        $html .= $button($action, $fields, $label, $class);
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
        foreach ( $extra as $name => $value ) {
            $fields[$name] = $value;
        }
        $html .= $button($action, $fields, $label, $class).' ';
    }
    if ( $onCount === 0 ) {
        $html = '<span class="text-muted">'.htmlspecialchars(__('Off')).'</span> '.$html;
    }
    return $html;
};

$rowOpen = function ($off) {
    $style = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 10px;padding:8px;border:1px solid #e5e5e5;';
    if ( $off ) {
        $style .= 'background:#f7f7f7;';
    }
    return '<div style="'.htmlspecialchars($style).'">';
};
?>
<p><a href="<?= htmlspecialchars(isset($tools_url) ? (string) $tools_url : '') ?>"><?= __('Back to course tools') ?></a></p>
<h2 style="margin-top:0;"><?= htmlspecialchars($title) ?> <span class="text-muted">(LTI 1.3)</span></h2>
<p><?= __('Turn off leaves an item on the tool and stops sending it. Delete removes it. Add puts a known value on the tool and turns it on.') ?></p>

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
        <input class="form-control" style="max-width:40em;" id="lti13_login" name="oidc_login_url" type="url" required value="<?= htmlspecialchars(isset($tool_edit['oidc_login_url']) ? (string) $tool_edit['oidc_login_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="lti13_jwks"><?= __('Keyset URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_jwks" name="jwks_url" type="url" required value="<?= htmlspecialchars(isset($tool_edit['jwks_url']) ? (string) $tool_edit['jwks_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="lti13_launch"><?= __('Launch URL') ?></label>
        <input class="form-control" style="max-width:40em;" id="lti13_launch" name="launch_url" type="url" required value="<?= htmlspecialchars(isset($tool_edit['launch_url']) ? (string) $tool_edit['launch_url'] : '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <p><button type="submit" class="btn btn-primary"><?= __('Save tool') ?></button></p>
</form>
<p class="text-muted" style="word-break:break-all;"><?= __('Client ID') ?>: <?= htmlspecialchars(isset($tool_edit['client_id']) ? (string) $tool_edit['client_id'] : '') ?></p>

<h3><?= __('Redirect URLs') ?></h3>
<?php
$redirects = isset($tool_edit['redirect_uris']) && is_array($tool_edit['redirect_uris']) ? $tool_edit['redirect_uris'] : array();
foreach ( $redirects as $uri ) {
    echo $rowOpen(false);
    echo '<span style="word-break:break-all;flex:1;">'.htmlspecialchars((string) $uri).'</span>';
    echo $button('delete_redirect', array('redirect_uri' => (string) $uri), __('Delete'), 'btn btn-danger btn-sm', __('Remove this redirect URL?'));
    echo '</div>';
}
?>
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
    echo $rowOpen($off);
    echo '<span style="flex:1;min-width:16em;'.($off ? 'color:#777;' : '').'">';
    echo htmlspecialchars((string) $scope['label']);
    echo '<br><span class="text-muted" style="font-size:12px;word-break:break-all;">'.htmlspecialchars((string) $scope['value']).'</span>';
    echo '</span>';
    echo $switches('scope_on', 'scope_off', 'scope', (string) $scope['value'], $grants);
    echo ' '.$button('delete_scope', array('scope' => (string) $scope['value']), __('Delete'), 'btn btn-danger btn-sm', __('Remove this scope from the tool?'));
    echo '</div>';
}
$scopeAvailable = isset($tool_edit['scope_available']) && is_array($tool_edit['scope_available']) ? $tool_edit['scope_available'] : array();
if ( count($scopeAvailable) > 0 ) {
    echo '<p class="text-muted">'.htmlspecialchars(__('Not on this tool')).'</p>';
    foreach ( $scopeAvailable as $scope ) {
        echo $rowOpen(true);
        echo '<span style="flex:1;min-width:16em;color:#777;">'.htmlspecialchars((string) $scope['label']).'</span>';
        echo $button('add_scope', array('scope' => (string) $scope['value']), __('Add'), 'btn btn-default btn-sm');
        echo '</div>';
    }
}
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
    echo $rowOpen($off);
    echo '<span style="flex:1;min-width:16em;'.($off ? 'color:#777;' : '').'">'.htmlspecialchars((string) $claim['label']).'</span>';
    if ( $locked ) {
        echo '<span class="text-muted">'.htmlspecialchars(__('Always sent')).'</span>';
    } else {
        echo $switches('claim_on', 'claim_off', 'claim', (string) $claim['value'], $grants);
        echo ' '.$button('delete_claim', array('claim' => (string) $claim['value']), __('Delete'), 'btn btn-danger btn-sm', __('Remove this claim from the tool?'));
    }
    echo '</div>';
}
$claimAvailable = isset($tool_edit['claim_available']) && is_array($tool_edit['claim_available']) ? $tool_edit['claim_available'] : array();
if ( count($claimAvailable) > 0 ) {
    echo '<p class="text-muted">'.htmlspecialchars(__('Not on this tool')).'</p>';
    foreach ( $claimAvailable as $claim ) {
        echo $rowOpen(true);
        echo '<span style="flex:1;min-width:16em;color:#777;">'.htmlspecialchars((string) $claim['label']).'</span>';
        echo $button('add_claim', array('claim' => (string) $claim['value']), __('Add'), 'btn btn-default btn-sm');
        echo '</div>';
    }
}
?>

<h3><?= __('Messages') ?></h3>
<?php
$messages = isset($tool_edit['messages']) && is_array($tool_edit['messages']) ? $tool_edit['messages'] : array();
if ( count($messages) === 0 ) {
    echo '<p class="text-muted">'.htmlspecialchars(__('The tool did not declare any messages.')).'</p>';
}
foreach ( $messages as $message ) {
    $messageId = (int) $message['message_id'];
    ?>
<div style="border:1px solid #ddd;padding:12px;margin:0 0 16px;">
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
    <p><?= $button('delete_message', array('message_id' => (string) $messageId), __('Delete'), 'btn btn-danger btn-sm', __('Delete this message?')) ?></p>
    <h4><?= __('Placements') ?></h4>
    <?php
    $placements = isset($message['placements']) && is_array($message['placements']) ? $message['placements'] : array();
    if ( count($placements) === 0 ) {
        echo '<p class="text-muted">'.htmlspecialchars(__('None')).'</p>';
    }
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
        echo $rowOpen($off);
        echo '<span style="flex:1;min-width:12em;'.($off ? 'color:#777;' : '').'">';
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
        ), __('Delete'), 'btn btn-danger btn-sm', __('Remove this placement from the message?'));
        echo '</div>';
    }
    $placementGroups = isset($message['placement_groups']) && is_array($message['placement_groups']) ? $message['placement_groups'] : array();
    if ( count($placementGroups) > 0 ) {
        ?>
    <form method="post" action="<?= htmlspecialchars($editUrl) ?>" style="margin-top:8px;">
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
        <?php
    }
    ?>
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
