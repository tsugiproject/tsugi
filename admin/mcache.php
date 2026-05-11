<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Util\MCache;
use \Tsugi\UI\Output;

$cache = new MCache($CFG);
$mc = $cache->getMemcached();

$result_html = '';
if ( $cache->isEnabled() && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $op = U::get($_POST, 'mcache_op', '');
    $key = trim((string) U::get($_POST, 'mcache_key', ''));
    if ( U::strlen($op) < 1 ) {
        // ignore empty POST (e.g. accidental submit)
    } elseif ( U::strlen($key) < 1 ) {
        $result_html = '<p class="mcache-err">Key is required.</p>';
    } elseif ( U::strlen($key) > 250 ) {
        $result_html = '<p class="mcache-err">Key is too long (Memcached allows up to 250 bytes).</p>';
    } elseif ( $op === 'get' ) {
        $val = $cache->get($key);
        $code = $mc->getResultCode();
        $msg = $mc->getResultMessage();
        if ( $code === \Memcached::RES_NOTFOUND ) {
            $result_html = '<p>Get: <strong>not found</strong>.</p>';
        } elseif ( $code === \Memcached::RES_SUCCESS ) {
            $dump = Output::safe_print_r($val);
            $result_html = '<p class="mcache-ok">Get: <strong>ok</strong> ('.htmlentities((string) $msg).').</p>'
                ."<pre>\n".$dump."</pre>\n";
        } else {
            $result_html = '<p class="mcache-err">Get failed: '.htmlentities((string) $msg)
                .' (code '.intval($code).')</p>';
        }
    } elseif ( $op === 'set' ) {
        $val = U::get($_POST, 'mcache_value', '');
        if ( ! is_string($val) ) {
            $val = '';
        }
        $ttl = intval(U::get($_POST, 'mcache_ttl', '0'));
        if ( $ttl < 0 ) {
            $ttl = 0;
        }
        $cache->set($key, $val, $ttl);
        $code = $mc->getResultCode();
        $msg = $mc->getResultMessage();
        if ( $code === \Memcached::RES_SUCCESS ) {
            $result_html = '<p class="mcache-ok">Set: <strong>ok</strong> ('.htmlentities((string) $msg).').</p>';
        } else {
            $result_html = '<p class="mcache-err">Set failed: '.htmlentities((string) $msg)
                .' (code '.intval($code).')</p>';
        }
    } elseif ( $op === 'delete' ) {
        $cache->delete($key);
        $code = $mc->getResultCode();
        $msg = $mc->getResultMessage();
        if ( $code === \Memcached::RES_SUCCESS ) {
            $result_html = '<p class="mcache-ok">Delete: <strong>ok</strong> ('.htmlentities((string) $msg).').</p>';
        } elseif ( $code === \Memcached::RES_NOTFOUND ) {
            $result_html = '<p>Delete: key was <strong>not present</strong>.</p>';
        } else {
            $result_html = '<p class="mcache-err">Delete failed: '.htmlentities((string) $msg)
                .' (code '.intval($code).')</p>';
        }
    }
}

?>
<html>
<head>
<title>Memcached (MCache) debug</title>
<style>
body { font-family: sans-serif; margin: 1em; }
.mcache-ok { color: #0a0; }
.mcache-err { color: #a00; }
pre { background: #f4f4f4; padding: 0.75em; overflow: auto; max-height: 28em; }
fieldset { margin-bottom: 1em; }
label { display: block; margin: 0.35em 0; }
input[type="text"], textarea { width: 98%; max-width: 52em; }
.mcache-actions input { margin-right: 0.5em; margin-top: 0.5em; }
</style>
</head>
<body>
<h1>Memcached (MCache) debug</h1>

<p>This page uses <code>\Tsugi\Util\MCache</code> with <code>$CFG-&gt;memcached</code> (same format as the memcached
session save path in <code>config-dist.php</code>). It is intended for administrators only.</p>

<?php
if ( ! $cache->isEnabled() ) {
    echo('<p><strong>Memcached application cache is not active.</strong> Set a non-empty <code>$CFG-&gt;memcached</code>, '
        .'install the PHP memcached extension, and ensure at least one server is listed (for example '
        .'<code>127.0.0.1:11211</code>).</p>'."\n");
    if ( isset($CFG->memcached) && is_string($CFG->memcached) && U::strlen(trim($CFG->memcached)) > 0
        && ! class_exists('\Memcached', false) ) {
        echo('<p class="mcache-err">The memcached PHP extension is not loaded.</p>'."\n");
    }
} else {
    echo('<p class="mcache-ok"><strong>Connected</strong> using <code>$CFG-&gt;memcached</code>: '
        .htmlentities((string) $CFG->memcached)."</p>\n");

    if ( U::strlen($result_html) > 0 ) {
        echo("<h2>Last action</h2>\n".$result_html."\n");
    }

    $last_key = isset($_POST['mcache_key']) ? (string) $_POST['mcache_key'] : '';
    $last_val = isset($_POST['mcache_value']) && is_string($_POST['mcache_value']) ? $_POST['mcache_value'] : '';
    $last_ttl = isset($_POST['mcache_ttl']) ? htmlentities((string) $_POST['mcache_ttl']) : '0';
?>
<h2>Get / set / delete</h2>
<form method="POST">
<fieldset>
<legend>Key and value</legend>
<label>Key<br/>
<input type="text" name="mcache_key" maxlength="250" required
 value="<?= htmlentities($last_key) ?>"></label>
<label>Value (for Set only)<br/>
<textarea name="mcache_value" rows="6" cols="72"><?= htmlentities($last_val) ?></textarea></label>
<label>TTL seconds (for Set; 0 = no expiry)<br/>
<input type="text" name="mcache_ttl" value="<?= $last_ttl ?>"></label>
</fieldset>
<p class="mcache-actions">
<input type="submit" name="mcache_op" value="get">
<input type="submit" name="mcache_op" value="set">
<input type="submit" name="mcache_op" value="delete">
</p>
</form>

<h2>Stats</h2>
<p><code>getStats()</code> / <code>getVersion()</code> from the underlying client (per server).</p>
<?php
    $stats = $mc->getStats();
    $vers = $mc->getVersion();
    echo("<h3>getVersion()</h3>\n<pre>\n".Output::safe_print_r($vers)."</pre>\n");
    echo("<h3>getStats()</h3>\n<pre>\n".Output::safe_print_r($stats)."</pre>\n");
}
?>
</body>
</html>
