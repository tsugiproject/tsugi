<?php
/**
 * Catalog card grid.
 *
 * Expected: $rows (title, href, hero_url, icon_url, short_description, enrolled)
 */
if ( ! isset($rows) || ! is_array($rows) || count($rows) < 1 ) {
    return;
}
?>
<ul class="tsugi-catalog-cards">
    <?php foreach ( $rows as $row ) {
        $title = isset($row['title']) ? (string) $row['title'] : '';
        $href = isset($row['href']) ? (string) $row['href'] : '';
        $hero_url = isset($row['hero_url']) ? (string) $row['hero_url'] : '';
        $icon_url = isset($row['icon_url']) ? (string) $row['icon_url'] : '';
        $short = isset($row['short_description']) ? (string) $row['short_description'] : '';
        $cid = isset($row['catalog_id']) ? (int) $row['catalog_id'] : 0;
        $enrolled = ! empty($row['enrolled']);
        $new_window = ! empty($row['href_new_window']);
        $target = $new_window ? ' target="_blank" rel="noopener noreferrer"' : '';
        $star = '<span class="tsugi-catalog-card-enrolled" title="'.htmlspecialchars(__('Enrolled'), ENT_QUOTES, 'UTF-8').'">'
            .'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            .'<path fill="#ffc107" stroke="#e0a800" stroke-width="1.2" stroke-linejoin="round" '
            .'d="M12 2.7l2.85 6.05 6.6.7-4.95 4.5 1.4 6.5L12 17.2l-5.9 3.25 1.4-6.5-4.95-4.5 6.6-.7z"/>'
            .'</svg><span class="tsugi-catalog-sr">'.htmlspecialchars(__('Enrolled')).'</span></span>';
    ?>
    <li>
        <a class="tsugi-catalog-card" href="<?= htmlspecialchars($href) ?>"<?= $target ?>>
            <?php if ( $hero_url !== '' ) { ?>
            <div class="tsugi-catalog-card-hero">
                <img src="<?= htmlspecialchars($hero_url) ?>" alt="">
                <?= $enrolled ? $star : '' ?>
            </div>
            <?php } else { ?>
            <div class="tsugi-catalog-card-hero tsugi-catalog-card-placeholder">
                <?= \Tsugi\Core\ContextImages::heroPlaceholderSvg($cid) ?>
                <?= $enrolled ? $star : '' ?>
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
