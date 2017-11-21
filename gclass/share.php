<?php

// https://developers.google.com/classroom/guides/sharebutton

$url = $CFG->wwwroot . '/gclass/launch';
?>
<h1>I am a share</h1>

<center>
<p>
Install in Google Classroom
</p>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<g:sharetoclassroom url="<?= $url ?>" size="32"></g:sharetoclassroom>
</center>
