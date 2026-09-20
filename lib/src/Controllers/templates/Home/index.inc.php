<?php
/**
 * Course home under-construction copy.
 *
 * Included from Home::render(). Expected: $course_title (context name).
 * Fully-qualified names so this file does not depend on the caller's namespace.
 */
$course_title = isset($course_title) ? trim((string) $course_title) : '';
?>
<main class="container" role="main" id="main-content">
    <?php if ( $course_title !== '' ) { ?>
    <h1><?= htmlspecialchars($course_title) ?></h1>
    <?php } ?>
    <p><?= __('This home page feature is under construction.') ?></p>
    <p><?= __('Eventually this page will hold a set of widgets and gadgets for the course. The first, and most important, will tell you exactly what to do next based on your current activity, progress, and due dates.') ?></p>
</main>
