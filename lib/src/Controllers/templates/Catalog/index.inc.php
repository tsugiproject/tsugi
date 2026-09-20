<?php
/**
 * Public course catalog listing. Tabs only when both enrolled and other rows exist.
 *
 * Expected: $rows, optional $enrolled_rows / $other_rows
 */
if ( ! isset($rows) || ! is_array($rows) ) {
    $rows = array();
}
if ( ! isset($enrolled_rows) || ! is_array($enrolled_rows) || ! isset($other_rows) || ! is_array($other_rows) ) {
    list($enrolled_rows, $other_rows) = \Tsugi\Controllers\Catalog::partitionRows($rows);
}
$show_tabs = count($enrolled_rows) > 0 && count($other_rows) > 0;
?>
<style>
.tsugi-catalog-page-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1em;
    margin: 0 0 0.75em;
}
.tsugi-catalog-page-head h1 { margin: 0; }
.tsugi-catalog-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.25em;
    list-style: none;
    padding: 0;
    margin: 1em 0 2em;
}
.tsugi-catalog-card {
    display: block;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    background: #fff;
}
.tsugi-catalog-card:hover,
.tsugi-catalog-card:focus {
    border-color: #337ab7;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.tsugi-catalog-card-hero {
    aspect-ratio: 16 / 9;
    background: #e9ecef;
    overflow: hidden;
    position: relative;
}
.tsugi-catalog-card-hero img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tsugi-catalog-card-placeholder {
    display: flex;
    align-items: flex-end;
    background: #1b2a4a;
}
.tsugi-catalog-card-hero .tsugi-course-card-placeholder-art {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
}
.tsugi-catalog-card-placeholder-title {
    position: relative;
    z-index: 1;
    color: #fff;
    font-weight: 700;
    font-size: 1.2em;
    line-height: 1.25;
    letter-spacing: 0.01em;
    padding: 0.85em 1em 0.9em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.45), 0 8px 18px rgba(0,0,0,0.35);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.tsugi-catalog-card-body {
    display: flex;
    align-items: flex-start;
    gap: 0.65em;
    padding: 0.75em 0.9em;
    min-height: 3.25em;
}
.tsugi-catalog-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 4px;
    object-fit: cover;
    flex: 0 0 36px;
}
.tsugi-catalog-card-text { min-width: 0; }
.tsugi-catalog-card-title {
    font-weight: 600;
    color: #337ab7;
    line-height: 1.3;
    display: block;
}
.tsugi-catalog-card-short {
    display: block;
    margin-top: 0.25em;
    color: #555;
    font-size: 0.9em;
    line-height: 1.35;
}
.tsugi-catalog-card-enrolled {
    position: absolute;
    top: 0.55em;
    right: 0.55em;
    z-index: 2;
    width: 2.1em;
    height: 2.1em;
    border-radius: 50%;
    background: rgba(20, 24, 32, 0.55);
    box-shadow: 0 1px 4px rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}
.tsugi-catalog-card-enrolled svg {
    display: block;
    width: 1.15em;
    height: 1.15em;
}
.tsugi-catalog-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
.tsugi-catalog-tabs {
    margin: 0.35em 0 0;
}
.tsugi-catalog-tab-content {
    margin-top: 1em;
}
.tsugi-catalog-section-head {
    font-size: 1.25em;
    font-weight: 600;
    margin: 0.35em 0 0.75em;
}
</style>
<main class="container" id="main-content">
    <div class="tsugi-catalog-page-head">
        <h1><?= __('Course catalog') ?></h1>
    </div>
    <?php if ( count($rows) < 1 ) { ?>
        <p><?= __('No courses are listed yet.') ?></p>
    <?php } elseif ( $show_tabs ) { ?>
        <ul class="nav nav-tabs tsugi-catalog-tabs" role="tablist">
            <li class="active" role="presentation">
                <a href="#catalog-enrolled" id="catalog-enrolled-tab" data-toggle="tab" role="tab" aria-controls="catalog-enrolled" aria-selected="true"><?= htmlspecialchars(__('Courses you are enrolled in')) ?></a>
            </li>
            <li role="presentation">
                <a href="#catalog-other" id="catalog-other-tab" data-toggle="tab" role="tab" aria-controls="catalog-other" aria-selected="false"><?= htmlspecialchars(__('Other courses')) ?></a>
            </li>
        </ul>
        <div class="tab-content tsugi-catalog-tab-content">
            <div class="tab-pane fade active in" id="catalog-enrolled" role="tabpanel" aria-labelledby="catalog-enrolled-tab">
                <?php $rows = $enrolled_rows; include __DIR__ . '/cards.inc.php'; ?>
            </div>
            <div class="tab-pane fade" id="catalog-other" role="tabpanel" aria-labelledby="catalog-other-tab">
                <?php $rows = $other_rows; include __DIR__ . '/cards.inc.php'; ?>
            </div>
        </div>
    <?php } elseif ( count($enrolled_rows) > 0 ) { ?>
        <h2 class="tsugi-catalog-section-head"><?= htmlspecialchars(__('Courses you are enrolled in')) ?></h2>
        <?php $rows = $enrolled_rows; include __DIR__ . '/cards.inc.php'; ?>
    <?php } else { ?>
        <?php $rows = $other_rows; include __DIR__ . '/cards.inc.php'; ?>
    <?php } ?>
</main>
