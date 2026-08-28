<?php
/**
 * Theme radio swatches.
 *
 * Expected: $theme_palettes (Manifest::palettes()), $theme_current (string key or '').
 */
if ( ! isset($theme_palettes) || ! is_array($theme_palettes) ) {
    $theme_palettes = array();
}
if ( ! isset($theme_current) ) {
    $theme_current = '';
}
$theme_current = is_string($theme_current) ? $theme_current : '';
?>
<style>
.theme-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 16px 0 20px 0;
}
.theme-swatch {
    display: flex;
    flex-direction: column;
    width: 140px;
    margin: 0;
    padding: 0;
    border: 2px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    background: #fff;
    font-weight: normal;
}
.theme-swatch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.theme-swatch.is-selected,
.theme-swatch:has(input:checked) {
    border-color: #333;
    box-shadow: 0 0 0 1px #333;
}
.theme-swatch-bar {
    display: block;
    height: 36px;
}
.theme-swatch-label {
    display: block;
    padding: 8px 10px;
    font-size: 13px;
    color: #333;
}
</style>
<fieldset class="theme-picker" id="theme-picker">
    <legend><?= __('Theme') ?></legend>
    <label class="theme-swatch<?= $theme_current === '' ? ' is-selected' : '' ?>">
        <input type="radio" name="theme" value=""<?= $theme_current === '' ? ' checked' : '' ?> />
        <span class="theme-swatch-bar" style="background: linear-gradient(90deg, #ccc, #eee);"></span>
        <span class="theme-swatch-label"><?= __('Site default') ?></span>
    </label>
    <?php foreach ( $theme_palettes as $key => $palette ) {
        $label = isset($palette['label']) ? $palette['label'] : $key;
        $primary = isset($palette['primary']) ? $palette['primary'] : '#999999';
        $selected = ($theme_current === $key);
        ?>
        <label class="theme-swatch<?= $selected ? ' is-selected' : '' ?>">
            <input type="radio" name="theme" value="<?= htmlspecialchars($key) ?>"<?= $selected ? ' checked' : '' ?> />
            <span class="theme-swatch-bar" style="background: <?= htmlspecialchars($primary) ?>;"></span>
            <span class="theme-swatch-label"><?= htmlspecialchars($label) ?></span>
        </label>
    <?php } ?>
</fieldset>
