<?php
/**
 * Course Settings tabs: Theme, Navigation, Images, External Tools, External Links,
 * Import, Export, Delete.
 *
 * Expected: $setup_url, $navigation_url, $images_url, $tools_url, $lesson_links_url,
 * $import_url, $export_url, $delete_url,
 * $setup_tab ('theme'|'navigation'|'images'|'tools'|'links'|'import'|'export'|'delete')
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
if ( ! isset($tools_url) ) {
    $tools_url = $setup_url;
}
if ( ! isset($lesson_links_url) || ! is_string($lesson_links_url) || $lesson_links_url === '' ) {
    $lesson_links_url = $tools_url;
    $query = strpos($tools_url, '?');
    $path = $query === false ? $tools_url : substr($tools_url, 0, $query);
    $tail = $query === false ? '' : substr($tools_url, $query);
    if ( substr($path, -6) === '/tools' ) {
        $lesson_links_url = $path.'/links'.$tail;
    }
}
if ( ! isset($import_url) ) {
    $import_url = $setup_url;
}
if ( ! isset($export_url) ) {
    $export_url = $setup_url;
}
if ( ! isset($delete_url) ) {
    $delete_url = $setup_url;
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
  <li class="<?= $setup_tab === 'tools' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($tools_url) ?>"><?= __('External Tools') ?></a>
  </li>
  <li class="<?= $setup_tab === 'links' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($lesson_links_url) ?>"><?= __('External Links') ?></a>
  </li>
  <li class="<?= $setup_tab === 'import' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($import_url) ?>"><?= __('Import') ?></a>
  </li>
  <li class="<?= $setup_tab === 'export' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($export_url) ?>"><?= __('Export') ?></a>
  </li>
  <li class="<?= $setup_tab === 'delete' ? 'active' : '' ?>">
    <a href="<?= htmlspecialchars($delete_url) ?>"><?= __('Delete') ?></a>
  </li>
</ul>
