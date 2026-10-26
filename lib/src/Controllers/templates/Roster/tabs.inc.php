<?php
/**
 * Roster tabs. The log tab is only on a Plus course.
 *
 * @var string $here
 * @var string $tab
 * @var bool $plus
 */
if ( empty($plus) ) {
    return;
}
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<ul class="nav nav-tabs">
  <li class="<?= $tab === 'people' ? 'active' : '' ?>">
    <a href="<?= $h($here) ?>">Roster</a>
  </li>
  <li class="<?= $tab === 'log' ? 'active' : '' ?>">
    <a href="<?= $h($here.'/log') ?>">Roster log</a>
  </li>
</ul>
