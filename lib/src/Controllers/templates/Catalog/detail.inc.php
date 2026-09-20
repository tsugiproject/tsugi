<?php
/**
 * Public catalog detail.
 *
 * Expected: $row, $logged_in, $enrol_url, $enter_url, $home
 */
$row = is_array($row ?? null) ? $row : array();
$logged_in = ! empty($logged_in);
$enrol_url = isset($enrol_url) ? (string) $enrol_url : '';
$enter_url = isset($enter_url) ? (string) $enter_url : '';
$home = isset($home) ? (string) $home : '';
$title = (string) ($row['title'] ?? '');
$short = (string) ($row['short_description'] ?? '');
$body = (string) ($row['description'] ?? '');
$hero_url = (string) ($row['hero_url'] ?? '');
$external_url = (string) ($row['external_url'] ?? '');
$context_id = (int) ($row['context_id'] ?? 0);
$catalog_id = (int) ($row['catalog_id'] ?? 0);
$enrolled = ! empty($row['enrolled']);
$new_window = ! empty($row['new_window']);
?>
<style>
.tsugi-catalog-detail-hero {
    aspect-ratio: 16 / 9;
    max-height: 22em;
    background: #e9ecef;
    overflow: hidden;
    position: relative;
    border-radius: 6px;
    margin: 0 0 1.25em;
}
.tsugi-catalog-detail-hero img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tsugi-catalog-detail-placeholder {
    display: flex;
    align-items: flex-end;
    background: #1b2a4a;
    min-height: 12em;
}
.tsugi-catalog-detail-hero .tsugi-course-card-placeholder-art {
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
    font-size: 1.6em;
    line-height: 1.25;
    padding: 0.85em 1em 0.9em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.45);
}
.tsugi-catalog-detail-short {
    font-size: 1.1em;
    color: #444;
    margin: 0 0 1em;
}
.tsugi-catalog-detail-body { margin: 1em 0 1.5em; }
.tsugi-catalog-detail-actions { margin: 1.5em 0; }
.tsugi-catalog-detail-actions form { display: inline; }
</style>
<main class="container" id="main-content">
    <p><a href="<?= htmlspecialchars($home) ?>"><?= __('Course catalog') ?></a></p>
    <?php if ( $hero_url !== '' ) { ?>
    <div class="tsugi-catalog-detail-hero">
        <img src="<?= htmlspecialchars($hero_url) ?>" alt="">
    </div>
    <?php } else { ?>
    <div class="tsugi-catalog-detail-hero tsugi-catalog-detail-placeholder">
        <?= \Tsugi\Core\ContextImages::heroPlaceholderSvg($catalog_id) ?>
        <span class="tsugi-catalog-card-placeholder-title"><?= htmlspecialchars($title) ?></span>
    </div>
    <?php } ?>
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ( $short !== '' ) { ?>
    <p class="tsugi-catalog-detail-short"><?= htmlspecialchars($short) ?></p>
    <?php } ?>
    <?php if ( $body !== '' ) { ?>
    <div class="tsugi-catalog-detail-body"><?= \Tsugi\Services\Catalog\CatalogRepository::purify($body) ?></div>
    <?php } ?>
    <div class="tsugi-catalog-detail-actions">
        <?php if ( $external_url !== '' ) {
            $target = $new_window ? ' target="_blank" rel="noopener noreferrer"' : '';
        ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($external_url) ?>"<?= $target ?>><?= __('Visit site') ?></a>
        <?php } elseif ( ! empty($can_enrol) && $context_id > 0 ) {
            if ( $enrolled && $enter_url !== '' ) { ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($enter_url) ?>"><?= __('Enter course') ?></a>
            <?php } elseif ( $enrol_url !== '' ) { ?>
        <form method="post" action="<?= htmlspecialchars($enrol_url) ?>">
            <?= \Tsugi\Controllers\Catalog::csrfField() ?>
            <button type="submit" class="btn btn-primary"><?= __('Join course') ?></button>
        </form>
            <?php }
        } ?>
    </div>
</main>
