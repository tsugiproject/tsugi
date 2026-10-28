<?php
/**
 * Course picker for the Classroom install iframe.
 *
 * @var string $assignTitle
 * @var string $googleEmail
 * @var string $switchUrl
 * @var string|false $installError
 * @var array<int,\Google_Service_Classroom_Course> $courses
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install in Classroom</title>
<style>
html, body {
    margin: 0;
    background: #fff;
    color: #202124;
    font-family: Roboto, Arial, sans-serif;
}
.card {
    padding: 8px 28px 28px;
    text-align: center;
}
.logo {
    width: 48px;
    height: 48px;
    margin-top: 12px;
}
h1 {
    margin: 16px 0 4px;
    font-size: 22px;
    font-weight: 400;
}
.item {
    margin: 0 0 18px;
    color: #5f6368;
    font-size: 14px;
}
.who {
    margin: 0 0 16px;
    color: #5f6368;
    font-size: 13px;
}
.who a {
    color: #1a73e8;
    text-decoration: none;
}
.err {
    margin: 0 0 12px;
    color: #d93025;
    font-size: 13px;
    text-align: left;
}
.menu {
    margin: 0 auto 22px;
    max-width: 320px;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(60,64,67,.3), 0 2px 6px rgba(60,64,67,.15);
    text-align: left;
    overflow: hidden;
}
.menu label {
    display: block;
    margin: 0;
    padding: 14px 20px;
    font-size: 16px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
}
.menu label:hover {
    background: #f8f9fa;
}
.menu input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.menu input:checked + span {
    display: block;
}
.menu label:has(input:checked) {
    background: #f1f3f4;
    border-bottom-color: #1a73e8;
}
.empty {
    margin: 0;
    padding: 16px 20px;
    color: #5f6368;
}
.go {
    min-width: 72px;
    height: 36px;
    padding: 0 24px;
    border: 0;
    border-radius: 18px;
    background: #1a73e8;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}
.go:disabled {
    background: #f1f3f4;
    color: #80868b;
    cursor: default;
}
</style>
</head>
<body>
<div class="card">
<img class="logo" alt="" src="https://www.gstatic.com/classroom/logo_square_48.svg">
<h1>Install in Classroom</h1>
<?php if ( $assignTitle !== '' ) { ?>
<p class="item"><?= $h($assignTitle) ?></p>
<?php } ?>
<?php if ( $googleEmail !== '' ) { ?>
<p class="who">Connected as <?= $h($googleEmail) ?>
· <a href="<?= $h($switchUrl) ?>" target="_top">Switch account</a></p>
<?php } ?>
<?php if ( $installError ) { ?>
<p class="err"><?= $h($installError) ?></p>
<?php } ?>
<form method="get">
<input type="hidden" name="rlid" value="<?= $h(\Tsugi\Util\U::get($_GET,'rlid') ?? '') ?>">
<input type="hidden" name="lti" value="<?= $h(\Tsugi\Util\U::get($_GET,'lti') ?? '') ?>">
<input type="hidden" name="title" value="<?= $h(\Tsugi\Util\U::get($_GET,'title') ?? '') ?>">
<input type="hidden" name="context" value="<?= $h(\Tsugi\Util\U::get($_GET,'context') ?? '') ?>">
<input type="hidden" name="module" value="<?= $h(\Tsugi\Util\U::get($_GET,'module') ?? '') ?>">
<input type="hidden" name="join" value="<?= $h(\Tsugi\Util\U::get($_GET,'join') ?? '') ?>">
<div class="menu">
<?php if ( count($courses) < 1 ) { ?>
<p class="empty">No active classes for this account.</p>
<?php } else { foreach ( $courses as $course ) {
    $id = $course->getId();
    $name = $course->getName();
?>
<label><input type="radio" name="gc_course" value="<?= $h($id) ?>"><span><?= $h($name) ?></span></label>
<?php } } ?>
</div>
<button class="go" id="gclass-go" type="submit" disabled>Go</button>
</form>
</div>
<script>
(function () {
    var go = document.getElementById('gclass-go');
    var radios = document.querySelectorAll('input[name="gc_course"]');
    function sync() {
        var on = false;
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) on = true;
        }
        go.disabled = !on;
    }
    for (var i = 0; i < radios.length; i++) {
        radios[i].addEventListener('change', sync);
    }
})();
</script>
</body>
</html>
