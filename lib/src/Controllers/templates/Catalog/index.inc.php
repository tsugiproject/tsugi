<?php
/**
 * Public course catalog cards.
 *
 * Expected: $rows (title, href, hero_url, icon_url, short_description), $logged_in
 */
if ( ! isset($rows) || ! is_array($rows) ) {
    $rows = array();
}
$logged_in = ! empty($logged_in);
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
</style>
<main class="container" id="main-content">
    <div class="tsugi-catalog-page-head">
        <h1><?= __('Course catalog') ?></h1>
    </div>
    <?php if ( count($rows) < 1 ) { ?>
        <p><?= __('No courses are listed yet.') ?></p>
    <?php } else { ?>
        <ul class="tsugi-catalog-cards">
            <?php foreach ( $rows as $row ) {
                $title = isset($row['title']) ? (string) $row['title'] : '';
                $href = isset($row['href']) ? (string) $row['href'] : '';
                $hero_url = isset($row['hero_url']) ? (string) $row['hero_url'] : '';
                $icon_url = isset($row['icon_url']) ? (string) $row['icon_url'] : '';
                $short = isset($row['short_description']) ? (string) $row['short_description'] : '';
                $cid = isset($row['catalog_id']) ? (int) $row['catalog_id'] : 0;
            ?>
            <li>
                <a class="tsugi-catalog-card" href="<?= htmlspecialchars($href) ?>">
                    <?php if ( $hero_url !== '' ) { ?>
                    <div class="tsugi-catalog-card-hero">
                        <img src="<?= htmlspecialchars($hero_url) ?>" alt="">
                    </div>
                    <?php } else { ?>
                    <div class="tsugi-catalog-card-hero tsugi-catalog-card-placeholder">
                        <?= \Tsugi\Core\ContextImages::heroPlaceholderSvg($cid) ?>
                        <span class="tsugi-catalog-card-placeholder-title"><?= htmlspecialchars($title) ?></span>
                    </div>
                    <?php } ?>
                    <div class="tsugi-catalog-card-body">
                        <?php if ( $icon_url !== '' ) { ?>
                        <img class="tsugi-catalog-card-icon" src="<?= htmlspecialchars($icon_url) ?>" alt="" width="36" height="36">
                        <?php } ?>
                        <span class="tsugi-catalog-card-text">
                            <span class="tsugi-catalog-card-title"><?= htmlspecialchars($title) ?></span>
                            <?php if ( $short !== '' ) { ?>
                            <span class="tsugi-catalog-card-short"><?= htmlspecialchars($short) ?></span>
                            <?php } ?>
                        </span>
                    </div>
                </a>
            </li>
            <?php } ?>
        </ul>
    <?php } ?>
</main>
