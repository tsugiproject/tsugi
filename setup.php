<?php

if ( ! defined('COOKIE_SESSION') ) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1); 
}

if ( ! isset($CFG) ) die("Please configure this product using config.php");
if ( ! isset($CFG->staticroot) ) die('$CFG->staticroot not defined see https://github.com/csev/webauto/issues/2');

error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

date_default_timezone_set('America/New_York');

function do_analytics() {
    global $CFG;
    if ( $CFG->analytics_key ) { ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

<?php
echo("  ga('create', '{$CFG->analytics_key}', '{$CFG->analytics_name}');\n");
?>
  ga('send', 'pageview');

</script>
<?php 
    }  // if 
}

// No trailer
