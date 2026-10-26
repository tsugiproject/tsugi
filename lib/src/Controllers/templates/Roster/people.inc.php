<?php
/**
 * Course membership list. Deleted rows stay visible.
 *
 * @var array<int, array<string, mixed>> $members
 * @var string $token
 * @var string $here
 * @var int $selfId
 * @var bool $plus
 */
use Tsugi\Services\Plus\RosterSync;

$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<p>People in this course. Removing someone sets their membership to deleted. Their work stays.</p>
<?php if ( ! empty($plus) ) { ?>
<p>On a Plus course, a later refresh from the LMS puts back anyone that roster still lists.</p>
<?php } ?>
<?php if ( count($members) < 1 ) { ?>
<p>No one is in this course yet.</p>
<?php } else { ?>
<table class="table table-striped">
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ( $members as $member ) {
    if ( ! is_array($member) ) {
        continue;
    }
    $deleted = (int) ($member['deleted'] ?? 0) === 1;
    $self = (int) ($member['user_id'] ?? 0) === (int) $selfId;
    $name = trim((string) ($member['displayname'] ?? ''));
    if ( $name === '' ) {
        $name = 'No name';
    }
    $email = trim((string) ($member['email'] ?? ''));
?>
<tr<?= $deleted ? ' class="text-muted"' : '' ?>>
<td><?= $h($name) ?></td>
<td><?= $email === '' ? '' : $h($email) ?></td>
<td><?= $h(RosterSync::roleLabel($member['role'] ?? 0, $member['role_override'] ?? null)) ?></td>
<td><?= $deleted ? 'Deleted' : 'Active' ?></td>
<td>
<?php if ( $self ) { ?>
You
<?php } else { ?>
<form method="post" action="<?= $h($here) ?>">
<input type="hidden" name="token" value="<?= $h($token) ?>">
<input type="hidden" name="membership_id" value="<?= (int) $member['membership_id'] ?>">
<input type="hidden" name="deleted" value="<?= $deleted ? '0' : '1' ?>">
<button type="submit" class="btn <?= $deleted ? 'btn-default' : 'btn-danger' ?> btn-sm"><?= $deleted ? 'Undelete' : 'Delete' ?></button>
</form>
<?php } ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
