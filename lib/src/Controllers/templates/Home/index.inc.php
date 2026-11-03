<?php
/**
 * Course home.
 *
 * Included from Home::render(). Expected:
 *   $course_title (context name)
 *   $hero_url (course hero, floated beside the home text)
 *   $main_page (title, body_html, published, edit_url) when a main page replaces this screen
 * Fully-qualified names so this file does not depend on the caller's namespace.
 */
$course_title = isset($course_title) ? trim((string) $course_title) : '';
$hero_url = isset($hero_url) ? trim((string) $hero_url) : '';
$main_page = (isset($main_page) && is_array($main_page)) ? $main_page : null;
$hero_alt = $course_title !== '' ? $course_title : __('Course image');
?>
<main class="container" role="main" id="main-content">
<?php if ( $hero_url !== '' ) { ?>
<style>
.tsugi-home-hero {
    float: right;
    width: 400px;
    max-width: 46%;
    height: auto;
    margin: 0 0 1em 1.25em;
}
</style>
<?php } ?>
<?php if ( $main_page ) {
    $page_title = isset($main_page['title']) ? trim((string) $main_page['title']) : '';
    $heading = $page_title !== '' ? $page_title : $course_title;
    $body_html = isset($main_page['body_html']) ? (string) $main_page['body_html'] : '';
    $published = ! empty($main_page['published']);
    $edit_url = isset($main_page['edit_url']) ? (string) $main_page['edit_url'] : '';
    if ( ! class_exists('\Tsugi\UI\CKEditor', false) ) {
        require_once __DIR__ . '/../../../UI/CKEditor.php';
    }
?>
    <style>
    <?php \Tsugi\UI\CKEditor::renderStyles(['includeLinkPicker' => false]); ?>
    </style>
    <?php if ( $heading !== '' || $edit_url !== '' ) { ?>
    <h1 style="display: flex; justify-content: space-between; align-items: center;">
        <span><?= htmlspecialchars($heading) ?></span>
        <?php if ( $edit_url !== '' ) { ?>
        <span>
            <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-primary" aria-label="<?= htmlspecialchars(__('Edit')) ?> <?= htmlspecialchars($heading) ?>"><?= __('Edit') ?></a>
        </span>
        <?php } ?>
    </h1>
    <?php } ?>
    <?php if ( ! $published ) { ?>
    <div class="alert alert-warning" role="status">
        <p><?= __('This page is not published. Students still see the default home page.') ?></p>
    </div>
    <?php } ?>
    <?php if ( $hero_url !== '' ) { ?>
    <img class="tsugi-home-hero" src="<?= htmlspecialchars($hero_url) ?>" alt="<?= htmlspecialchars($hero_alt) ?>">
    <?php } ?>
    <div class="page-content">
        <?= $body_html ?>
    </div>
<?php } else { ?>
    <?php if ( $course_title !== '' ) { ?>
    <h1><?= htmlspecialchars($course_title) ?></h1>
    <?php } ?>
    <?php if ( $hero_url !== '' ) { ?>
    <img class="tsugi-home-hero" src="<?= htmlspecialchars($hero_url) ?>" alt="<?= htmlspecialchars($hero_alt) ?>">
    <?php } ?>
    <p><?= __('This home page feature is under construction.') ?></p>
    <p><?= __('TODO: Write Home Controller') ?></p>
    <p><?= __('Eventually this page will hold a set of widgets and gadgets for the course. The first, and most important, will tell you exactly what to do next based on your current activity, progress, and due dates.') ?></p>
<?php } ?>
<div style="clear: both;"></div>
</main>
