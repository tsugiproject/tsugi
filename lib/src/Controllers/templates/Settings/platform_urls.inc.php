<?php
/**
 * Platform URLs to give an LTI 1.3 tool. Same values dynamic registration publishes.
 */
$platform_urls = \Tsugi\Services\Outbound\PlatformDynamicRegistration::platformUrls();
?>
<h3 style="margin-top:24px;"><?= __('Platform URLs') ?></h3>
<p class="text-muted"><?= __('Give these to the tool. Dynamic registration publishes the same URLs.') ?></p>
<?php foreach ( $platform_urls as $platform_url ) {
    $platformValue = isset($platform_url['value']) ? (string) $platform_url['value'] : '';
    $platformLabel = isset($platform_url['label']) ? (string) $platform_url['label'] : '';
    ?>
<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 8px;">
    <span style="min-width:12em;"><?= htmlspecialchars($platformLabel) ?></span>
    <span style="word-break:break-all;flex:1;"><?= htmlspecialchars($platformValue) ?></span>
    <button type="button" class="btn btn-default btn-sm" data-url="<?= htmlspecialchars($platformValue, ENT_QUOTES, 'UTF-8') ?>" onclick="copyToClipboardNoScroll(this, this.getAttribute('data-url')); return false;"><i class="fa fa-clipboard" aria-hidden="true"></i> <?= __('Copy') ?></button>
</div>
<?php } ?>
