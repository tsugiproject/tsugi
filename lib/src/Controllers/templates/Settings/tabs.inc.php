<?php
/**
 * Course Settings tabs: Theme, Navigation, Images, Import, Export.
 *
 * Expected: $setup_url, $navigation_url, $images_url, $import_url, $export_url,
 * $setup_tab ('theme'|'navigation'|'images'|'import'|'export')
 */
if ( ! isset($setup_tab) ) {
    $setup_tab = 'theme';
}
if ( ! isset($navigation_url) ) {
    $navigation_url = $setup_url;
}
if ( ! isset($images_url) ) {
    $images_url = $setup_url;
}
if ( ! isset($import_url) ) {
    $import_url = $setup_url;
}
if ( ! isset($export_url) ) {
    $export_url = $setup_url;
}
?>
<h1><?= htmlspecialchars(\Tsugi\Controllers\Settings::courseHeadingTitle()) ?></h1>
<ul class="nav nav-tabs">
  <li class="<?= $setup_tab === 'theme' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($setup_url) ?>"><?= __('Theme') ?></a>
  </li>
  <li class="<?= $setup_tab === 'navigation' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($navigation_url) ?>"><?= __('Navigation') ?></a>
  </li>
  <li class="<?= $setup_tab === 'images' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($images_url) ?>"><?= __('Images') ?></a>
  </li>
  <li class="<?= $setup_tab === 'import' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($import_url) ?>"><?= __('Import') ?></a>
  </li>
  <li class="<?= $setup_tab === 'export' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($export_url) ?>"><?= __('Export') ?></a>
  </li>
</ul>
