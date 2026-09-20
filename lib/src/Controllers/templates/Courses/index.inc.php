<?php
/**
 * All-courses list: 16×9 hero cards with optional square icon.
 *
 * Expected: $rows (title, href, hero_url, icon_url), $can_create, $create_url
 */
if ( ! isset($rows) || ! is_array($rows) ) {
    $rows = array();
}
$can_create = ! empty($can_create);
$create_url = isset($create_url) ? (string) $create_url : '';
?>
<style>
.tsugi-course-page-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1em;
    margin: 0 0 0.75em;
}
.tsugi-course-page-head h1 {
    margin: 0;
}
.tsugi-course-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.25em;
    list-style: none;
    padding: 0;
    margin: 1em 0 2em;
}
.tsugi-course-card {
    display: block;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    background: #fff;
}
.tsugi-course-card:hover,
.tsugi-course-card:focus {
    border-color: #337ab7;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.tsugi-course-card-hero {
    aspect-ratio: 16 / 9;
    background: #e9ecef;
    overflow: hidden;
    position: relative;
}
.tsugi-course-card-hero img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tsugi-course-card-placeholder {
    display: flex;
    align-items: flex-end;
    background: #1b2a4a;
}
.tsugi-course-card-placeholder-art {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
}
.tsugi-course-card-placeholder-title {
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
.tsugi-course-card-body {
    display: flex;
    align-items: center;
    gap: 0.65em;
    padding: 0.75em 0.9em;
    min-height: 3.25em;
}
.tsugi-course-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 4px;
    object-fit: cover;
    flex: 0 0 36px;
}
.tsugi-course-card-title {
    font-weight: 600;
    color: #337ab7;
    line-height: 1.3;
}
</style>
<main class="container" id="main-content">
    <div class="tsugi-course-page-head">
        <h1><?= __('Courses') ?></h1>
        <?php if ( $can_create && $create_url !== '' ) { ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($create_url) ?>"><?= __('Add course') ?></a>
        <?php } ?>
    </div>
    <?php if ( count($rows) < 1 ) { ?>
        <p><?= __('You are not a member of any courses.') ?></p>
    <?php } else { ?>
        <ul class="tsugi-course-cards">
            <?php foreach ( $rows as $row ) {
                $title = isset($row['title']) ? (string) $row['title'] : '';
                $href = isset($row['href']) ? (string) $row['href'] : '';
                $hero_url = isset($row['hero_url']) ? (string) $row['hero_url'] : '';
                $icon_url = isset($row['icon_url']) ? (string) $row['icon_url'] : '';
                $cid = isset($row['context_id']) ? (int) $row['context_id'] : 0;
            ?>
            <li>
                <a class="tsugi-course-card" href="<?= htmlspecialchars($href) ?>">
                    <?php if ( $hero_url !== '' ) { ?>
                    <div class="tsugi-course-card-hero">
                        <img src="<?= htmlspecialchars($hero_url) ?>" alt="">
                    </div>
                    <?php } else { ?>
                    <div class="tsugi-course-card-hero tsugi-course-card-placeholder">
                        <?= \Tsugi\Core\ContextImages::heroPlaceholderSvg($cid) ?>
                        <span class="tsugi-course-card-placeholder-title"><?= htmlspecialchars($title) ?></span>
                    </div>
                    <?php } ?>
                    <div class="tsugi-course-card-body">
                        <?php if ( $icon_url !== '' ) { ?>
                        <img class="tsugi-course-card-icon" src="<?= htmlspecialchars($icon_url) ?>" alt="" width="36" height="36">
                        <?php } ?>
                        <span class="tsugi-course-card-title"><?= htmlspecialchars($title) ?></span>
                    </div>
                </a>
            </li>
            <?php } ?>
        </ul>
    <?php } ?>
</main>
