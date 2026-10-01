<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Controllers\Tool;
use \Tsugi\Services\Org\OrgService;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../admin_util.php';

LTIX::getConnection();
\Tsugi\Core\Admin::session_start();
require_once __DIR__ . '/../gate.php';
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$keyId = (int) U::get($_GET, 'key_id', U::get($_POST, 'key_id', 0));
$returnOrg = (int) U::get($_GET, 'org_id', 0);
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $postedAction = (string) U::get($_POST, 'action', '');
    if ( $postedAction === 'place' || $postedAction === 'move_course' ) {
        $returnOrg = (int) U::get($_POST, 'org_id', 0);
    } else {
        $returnOrg = 0;
    }
}
$page = org_manager_url($keyId, $returnOrg);

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( Tool::csrfRedirect($page) ) return;
    org_manager_post($keyId);
    header('Location: '.$page);
    return;
}

$tenants = org_manager_tenants();
$tenant = $keyId > 0 ? org_manager_find_tenant($tenants, $keyId) : null;
if ( $keyId > 0 && $tenant === null ) {
    U::flashError(__('That tenant was not found.'));
}
$orgs = array();
$focus = null;
if ( $tenant !== null ) {
    try {
        $orgs = OrgService::orgsForKey($keyId);
    } catch ( \InvalidArgumentException $ex ) {
        $tenant = null;
        U::flashError($ex->getMessage());
    }
}
$focusId = (int) U::get($_GET, 'org_id', 0);
if ( $tenant !== null && $focusId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    foreach ( $orgs as $org ) {
        if ( (int) $org['org_id'] === $focusId ) {
            $focus = $org;
            break;
        }
    }
    if ( $focus === null ) {
        U::flashError(__('That organization is not in this tenant.'));
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1><?= htmlspecialchars(__('Organizations')) ?></h1>
<p><a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/') ?>"><?= __('Administration console') ?></a></p>
<?php if ( $tenant === null ) { ?>
<p><?= __('Pick a tenant. An organization stays inside that tenant.') ?></p>
<?php if ( count($tenants) < 1 ) { ?>
<p><?= __('No tenants yet.') ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= __('Tenant') ?></th>
            <th><?= __('Organizations') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $tenants as $row ) {
        $id = (int) $row['key_id'];
        $open = org_manager_url($id);
    ?>
        <tr>
            <td><?= htmlspecialchars(org_manager_tenant_label($row)) ?></td>
            <td><?= (int) $row['org_count'] ?></td>
            <td><a href="<?= htmlspecialchars($open) ?>"><?= __('Open') ?></a></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
<?php
    $OUTPUT->footer();
    return;
}

?>
<p>
    <?= htmlspecialchars(__('Tenant')) ?>:
    <strong><?= htmlspecialchars(org_manager_tenant_label($tenant)) ?></strong>
    ·
    <a href="<?= htmlspecialchars(org_manager_url(0)) ?>"><?= __('Switch tenant') ?></a>
</p>
<?php if ( $focus !== null ) {
    $focusId = (int) $focus['org_id'];
    $holdings = OrgService::directHoldings($focusId);
    $here = array();
    foreach ( $holdings['courses'] as $course ) {
        $here[(int) $course['context_id']] = true;
    }
    $available = array();
    foreach ( org_manager_courses($keyId) as $course ) {
        if ( ! isset($here[(int) $course['context_id']]) ) {
            $available[] = $course;
        }
    }
    $orgTitles = array();
    foreach ( $orgs as $org ) {
        $orgTitles[(int) $org['org_id']] = (string) $org['title'];
    }
?>
<p><a href="<?= htmlspecialchars(org_manager_url($keyId)) ?>"><?= htmlspecialchars(__('Back to organizations')) ?></a></p>
<h2><?= htmlspecialchars($focus['title']) ?></h2>

<h3><?= htmlspecialchars(__('Courses in this organization')) ?></h3>
<?php if ( count($holdings['courses']) < 1 ) { ?>
<p><?= __('No courses in this organization yet.') ?></p>
<?php } else { ?>
<table class="table">
    <thead>
        <tr>
            <th><?= __('Course') ?></th>
            <th><?= __('Move to') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $holdings['courses'] as $course ) {
        $contextId = (int) $course['context_id'];
        $courseTitle = trim((string) ($course['title'] ?? ''));
        if ( $courseTitle === '' ) {
            $courseTitle = 'Course '.$contextId;
        }
    ?>
        <tr>
            <td><?= htmlspecialchars($courseTitle) ?></td>
            <td>
                <form method="post" action="<?= htmlspecialchars($page) ?>">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="key_id" value="<?= $keyId ?>">
                    <input type="hidden" name="org_id" value="<?= $focusId ?>">
                    <input type="hidden" name="context_id" value="<?= $contextId ?>">
                    <input type="hidden" name="action" value="move_course">
                    <select class="form-control" name="parent_org_id" aria-label="<?= htmlspecialchars(__('Move to')) ?>">
                        <option value=""><?= htmlspecialchars(__('Not in an organization')) ?></option>
                        <?php foreach ( $orgs as $org ) {
                            if ( (int) $org['org_id'] === $focusId ) {
                                continue;
                            }
                        ?>
                        <option value="<?= (int) $org['org_id'] ?>"><?= htmlspecialchars(org_manager_indent($org).$org['title']) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn btn-default btn-sm" style="margin-top:0.25rem;"><?= htmlspecialchars(__('Move')) ?></button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<h3><?= htmlspecialchars(__('Add a course')) ?></h3>
<?php if ( count($available) < 1 ) { ?>
<p><?= __('No other courses in this tenant.') ?></p>
<?php } else { ?>
<form method="post" action="<?= htmlspecialchars($page) ?>">
    <?= Tool::csrfField() ?>
    <input type="hidden" name="key_id" value="<?= $keyId ?>">
    <input type="hidden" name="org_id" value="<?= $focusId ?>">
    <input type="hidden" name="action" value="place">
    <label for="add_course"><?= htmlspecialchars(__('Course')) ?></label>
    <select class="form-control" id="add_course" name="context_id" required>
        <?php foreach ( $available as $course ) {
            $contextId = (int) $course['context_id'];
            $courseTitle = trim((string) ($course['title'] ?? ''));
            if ( $courseTitle === '' ) {
                $courseTitle = 'Course '.$contextId;
            }
            $placed = (int) ($course['org_id'] ?? 0);
            if ( $placed > 0 && isset($orgTitles[$placed]) ) {
                $courseTitle .= ' ('.$orgTitles[$placed].')';
            }
        ?>
        <option value="<?= $contextId ?>"><?= htmlspecialchars($courseTitle) ?></option>
        <?php } ?>
    </select>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Add course')) ?></button>
</form>
<?php } ?>
<?php
    $OUTPUT->footer();
    return;
} ?>

<h2><?= htmlspecialchars(__('Add organization')) ?></h2>
<form method="post" action="<?= htmlspecialchars($page) ?>" class="form-inline" style="margin-bottom:1.5rem;">
    <?= Tool::csrfField() ?>
    <input type="hidden" name="key_id" value="<?= $keyId ?>">
    <input type="hidden" name="action" value="create">
    <label for="new_title"><?= htmlspecialchars(__('Name')) ?></label>
    <input type="text" class="form-control" id="new_title" name="title" maxlength="512" required>
    <label for="new_type"><?= htmlspecialchars(__('Type')) ?></label>
    <input type="text" class="form-control" id="new_type" name="org_type" maxlength="64" placeholder="<?= htmlspecialchars(__('optional')) ?>">
    <label for="new_parent"><?= htmlspecialchars(__('Inside')) ?></label>
    <select class="form-control" id="new_parent" name="parent_org_id">
        <option value=""><?= htmlspecialchars(__('Tenant root')) ?></option>
        <?php foreach ( $orgs as $org ) { ?>
        <option value="<?= (int) $org['org_id'] ?>"><?= htmlspecialchars(org_manager_indent($org).$org['title']) ?></option>
        <?php } ?>
    </select>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Create')) ?></button>
</form>

<?php if ( count($orgs) < 1 ) { ?>
<p><?= __('No organizations in this tenant yet.') ?></p>
<?php } else { ?>
<table class="table">
    <thead>
        <tr>
            <th><?= __('Organization') ?></th>
            <th><?= __('Inside') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $orgs as $org ) {
        $orgId = (int) $org['org_id'];
        $holdings = OrgService::directHoldings($orgId);
        $blocked = org_manager_blocked($holdings);
        $parentChoices = org_manager_parent_choices($orgs, $orgId);
    ?>
        <tr>
            <td style="padding-left: <?= (int) $org['depth'] * 24 + 8 ?>px;">
                <form method="post" action="<?= htmlspecialchars($page) ?>">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="key_id" value="<?= $keyId ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="org_id" value="<?= $orgId ?>">
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($org['title']) ?>" maxlength="512" required aria-label="<?= htmlspecialchars(__('Name')) ?>">
                    <input type="text" class="form-control" name="org_type" value="<?= htmlspecialchars((string) ($org['org_type'] ?? '')) ?>" maxlength="64" placeholder="<?= htmlspecialchars(__('Type')) ?>" aria-label="<?= htmlspecialchars(__('Type')) ?>" style="margin-top:0.25rem;">
                    <button type="submit" class="btn btn-default btn-sm" style="margin-top:0.25rem;"><?= htmlspecialchars(__('Save')) ?></button>
                </form>
                <p style="margin:0.5rem 0 0;"><a href="<?= htmlspecialchars(org_manager_url($keyId, $orgId)) ?>"><?= htmlspecialchars(__('Courses')) ?></a></p>
            </td>
            <td>
                <form method="post" action="<?= htmlspecialchars($page) ?>">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="key_id" value="<?= $keyId ?>">
                    <input type="hidden" name="action" value="move">
                    <input type="hidden" name="org_id" value="<?= $orgId ?>">
                    <select class="form-control" name="parent_org_id" aria-label="<?= htmlspecialchars(__('Inside')) ?>">
                        <option value=""<?= $org['parent_org_id'] === null ? ' selected' : '' ?>><?= htmlspecialchars(__('Tenant root')) ?></option>
                        <?php foreach ( $parentChoices as $choice ) {
                            $choiceId = (int) $choice['org_id'];
                            $selected = $org['parent_org_id'] === $choiceId ? ' selected' : '';
                        ?>
                        <option value="<?= $choiceId ?>"<?= $selected ?>><?= htmlspecialchars(org_manager_indent($choice).$choice['title']) ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn btn-default btn-sm" style="margin-top:0.25rem;"><?= htmlspecialchars(__('Move')) ?></button>
                </form>
            </td>
            <td>
                <?php if ( $blocked === '' ) { ?>
                <form method="post" action="<?= htmlspecialchars($page) ?>" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this organization?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="key_id" value="<?= $keyId ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="org_id" value="<?= $orgId ?>">
                    <button type="submit" class="btn btn-link"><?= htmlspecialchars(__('Delete')) ?></button>
                </form>
                <?php } else { ?>
                <p class="text-muted" style="margin:0;"><?= htmlspecialchars($blocked) ?></p>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
<?php
$OUTPUT->footer();

/**
 * @param int $keyId
 * @return string
 */
function org_manager_url($keyId, $orgId = 0) {
    $url = 'index.php';
    $query = array();
    if ( $keyId > 0 ) {
        $query[] = 'key_id='.(int) $keyId;
    }
    if ( $orgId > 0 ) {
        $query[] = 'org_id='.(int) $orgId;
    }
    if ( count($query) > 0 ) {
        $url .= '?'.implode('&', $query);
    }
    return U::addSession($url);
}

/**
 * @return array<int, array<string, mixed>>
 */
function org_manager_tenants() {
    global $CFG, $PDOX;
    $p = $CFG->dbprefix;
    $rows = $PDOX->allRowsDie(
        "SELECT k.key_id, k.key_title, k.key_key,
                (SELECT COUNT(*) FROM {$p}lti_org o WHERE o.key_id = k.key_id) AS org_count
         FROM {$p}lti_key k
         ORDER BY k.key_id ASC"
    );
    if ( ! is_array($rows) ) {
        return array();
    }
    usort($rows, function ($a, $b) {
        $left = strtolower(org_manager_tenant_label($a));
        $right = strtolower(org_manager_tenant_label($b));
        if ( $left === $right ) {
            return ((int) $a['key_id']) <=> ((int) $b['key_id']);
        }
        return $left <=> $right;
    });
    return $rows;
}

/**
 * @param array<int, array<string, mixed>> $tenants
 * @param int $keyId
 * @return array<string, mixed>|null
 */
function org_manager_find_tenant(array $tenants, $keyId) {
    foreach ( $tenants as $row ) {
        if ( (int) $row['key_id'] === $keyId ) {
            return $row;
        }
    }
    return null;
}

/**
 * @param array<string, mixed> $row
 * @return string
 */
function org_manager_tenant_label(array $row) {
    $title = trim((string) ($row['key_title'] ?? ''));
    if ( $title !== '' ) {
        return $title;
    }
    $key = trim((string) ($row['key_key'] ?? ''));
    if ( $key !== '' ) {
        return $key;
    }
    return 'Tenant '.(int) $row['key_id'];
}

/**
 * @param array<string, mixed> $org
 * @return string
 */
function org_manager_indent(array $org) {
    $depth = (int) ($org['depth'] ?? 0);
    if ( $depth < 1 ) {
        return '';
    }
    return str_repeat('· ', $depth);
}

/**
 * Parents this org may move under: the same tenant, excluding itself and its descendants.
 *
 * @param array<int, array<string, mixed>> $orgs
 * @param int $orgId
 * @return array<int, array<string, mixed>>
 */
function org_manager_parent_choices(array $orgs, $orgId) {
    $skip = array($orgId => true);
    $depth = null;
    foreach ( $orgs as $org ) {
        $id = (int) $org['org_id'];
        if ( $id === $orgId ) {
            $depth = (int) $org['depth'];
            continue;
        }
        if ( $depth === null ) {
            continue;
        }
        if ( (int) $org['depth'] <= $depth ) {
            break;
        }
        $skip[$id] = true;
    }
    $choices = array();
    foreach ( $orgs as $org ) {
        if ( isset($skip[(int) $org['org_id']]) ) {
            continue;
        }
        $choices[] = $org;
    }
    return $choices;
}

/**
 * @param array<string, mixed> $holdings
 * @return string empty when the org may be deleted
 */
function org_manager_blocked(array $holdings) {
    $counts = isset($holdings['counts']) && is_array($holdings['counts']) ? $holdings['counts'] : array();
    $courses = (int) ($counts['courses'] ?? 0);
    $children = (int) ($counts['children'] ?? 0);
    $deployments = (int) ($counts['deployments'] ?? 0);
    $registrations = (int) ($counts['registrations'] ?? 0);
    $parts = array();
    if ( $courses > 0 ) {
        $parts[] = org_manager_count($courses, 'course', 'courses').' in this organization';
    }
    if ( $children > 0 ) {
        $parts[] = org_manager_count($children, 'sub-organization', 'sub-organizations');
    }
    if ( $deployments > 0 ) {
        $parts[] = org_manager_count($deployments, 'deployment', 'deployments');
    }
    if ( $registrations > 0 ) {
        $parts[] = org_manager_count($registrations, 'registration', 'registrations');
    }
    if ( count($parts) < 1 ) {
        return '';
    }
    $total = $courses + $children + $deployments + $registrations;
    $there = $total === 1 ? 'There is' : 'There are';
    $them = $total === 1 ? 'it' : 'them';
    return $there.' '.org_manager_join($parts).'. You must move '.$them.' before you can delete this organization.';
}

/**
 * @param int $n
 * @param string $one
 * @param string $many
 * @return string
 */
function org_manager_count($n, $one, $many) {
    return $n.' '.($n === 1 ? $one : $many);
}

/**
 * @param string[] $parts
 * @return string
 */
function org_manager_join(array $parts) {
    if ( count($parts) < 2 ) {
        return $parts[0];
    }
    $last = array_pop($parts);
    return implode(', ', $parts).' and '.$last;
}

/**
 * Courses in this tenant, including where they are placed.
 *
 * @param int $keyId
 * @return array<int, array<string, mixed>>
 */
function org_manager_courses($keyId) {
    global $CFG, $PDOX;
    $rows = $PDOX->allRowsDie(
        "SELECT context_id, title, org_id
         FROM {$CFG->dbprefix}lti_context
         WHERE key_id = :key_id AND deleted = 0
         ORDER BY title ASC, context_id ASC",
        array(':key_id' => (int) $keyId)
    );
    return is_array($rows) ? $rows : array();
}

/**
 * @param int $keyId
 * @return void
 */
function org_manager_post($keyId) {
    if ( $keyId < 1 || org_manager_find_tenant(org_manager_tenants(), $keyId) === null ) {
        U::flashError(__('Pick a tenant first.'));
        return;
    }

    $action = (string) U::get($_POST, 'action', '');
    try {
        if ( $action === 'create' ) {
            $parent = org_manager_posted_parent();
            org_manager_require_in_tenant($keyId, $parent);
            OrgService::createOrg(
                $keyId,
                (string) U::get($_POST, 'title', ''),
                $parent,
                org_manager_posted_type()
            );
            U::flashSuccess(__('Organization created.'));
            return;
        }

        $orgId = (int) U::get($_POST, 'org_id', 0);
        org_manager_require_in_tenant($keyId, $orgId);
        if ( $action === 'update' ) {
            OrgService::updateOrg($orgId, (string) U::get($_POST, 'title', ''), org_manager_posted_type());
            U::flashSuccess(__('Organization saved.'));
            return;
        }
        if ( $action === 'move' ) {
            $parent = org_manager_posted_parent();
            org_manager_require_in_tenant($keyId, $parent);
            OrgService::moveOrg($orgId, $parent);
            U::flashSuccess(__('Organization moved.'));
            return;
        }
        if ( $action === 'place' ) {
            $contextId = (int) U::get($_POST, 'context_id', 0);
            org_manager_require_course($keyId, $contextId);
            OrgService::placeContext($contextId, $orgId);
            U::flashSuccess(__('Course added to this organization.'));
            return;
        }
        if ( $action === 'move_course' ) {
            $contextId = (int) U::get($_POST, 'context_id', 0);
            org_manager_require_course($keyId, $contextId);
            $target = org_manager_posted_parent();
            org_manager_require_in_tenant($keyId, $target);
            OrgService::placeContext($contextId, $target);
            U::flashSuccess(__('Course moved.'));
            return;
        }
        if ( $action === 'delete' ) {
            $holdings = OrgService::directHoldings($orgId);
            $blocked = org_manager_blocked($holdings);
            if ( $blocked !== '' ) {
                U::flashError($blocked);
                return;
            }
            OrgService::deleteOrg($orgId);
            U::flashSuccess(__('Organization deleted.'));
            return;
        }
        U::flashError(__('Unknown action.'));
    } catch ( \InvalidArgumentException $ex ) {
        U::flashError($ex->getMessage());
    } catch ( \RuntimeException $ex ) {
        U::flashError($ex->getMessage());
    }
}

/**
 * @return int|null
 */
function org_manager_posted_parent() {
    $parent = (int) U::get($_POST, 'parent_org_id', 0);
    return $parent > 0 ? $parent : null;
}

/**
 * @return string|null
 */
function org_manager_posted_type() {
    $type = trim((string) U::get($_POST, 'org_type', ''));
    return $type === '' ? null : $type;
}

/**
 * @param int $keyId
 * @param int $contextId
 * @return void
 */
function org_manager_require_course($keyId, $contextId) {
    global $CFG, $PDOX;
    $row = $PDOX->rowDie(
        "SELECT context_id, key_id
         FROM {$CFG->dbprefix}lti_context
         WHERE context_id = :context_id",
        array(':context_id' => (int) $contextId)
    );
    if ( ! is_array($row) || (int) $row['key_id'] !== (int) $keyId ) {
        throw new \InvalidArgumentException('That course is not in this tenant.');
    }
}

/**
 * Refuse an organization id that is not in the tenant currently open.
 * A null parent is the tenant root and is allowed.
 *
 * @param int $keyId
 * @param int|null $orgId
 * @return void
 */
function org_manager_require_in_tenant($keyId, $orgId) {
    if ( $orgId === null ) {
        return;
    }
    foreach ( OrgService::orgsForKey($keyId) as $org ) {
        if ( (int) $org['org_id'] === (int) $orgId ) {
            return;
        }
    }
    throw new \InvalidArgumentException('That organization is not in this tenant.');
}
