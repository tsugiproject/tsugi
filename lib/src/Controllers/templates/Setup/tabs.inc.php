<?php
/**
 * Setup tabs: Theme and Export.
 *
 * Expected: $setup_url, $export_url, $setup_tab ('theme'|'export')
 */
if ( ! isset($setup_tab) ) {
    $setup_tab = 'theme';
}
?>
<ul class="nav nav-tabs">
  <li class="<?= $setup_tab === 'theme' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($setup_url) ?>"><?= __('Theme') ?></a>
  </li>
  <li class="<?= $setup_tab === 'export' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($export_url) ?>"><?= __('Export') ?></a>
  </li>
</ul>
