<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/tsugi/lib/src/Util/U.php';

use Tsugi\Util\U;

if (!function_exists('htmlent_utf8')) {
    function htmlent_utf8($string) {
        return U::htmlent_utf8($string);
    }
}

if (!function_exists('print_stack_trace')) {
    function print_stack_trace() {
        $GLOBALS['__print_stack_trace_called'] = true;
    }
}

function assertTrue($condition, string $message = ''): void {
    if (!$condition) {
        throw new Exception($message ?: 'assertTrue failed');
    }
}

function assertFalse($condition, string $message = ''): void {
    if ($condition) {
        throw new Exception($message ?: 'assertFalse failed');
    }
}

function assertSame($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $detail = $message ?: 'Expected '.var_export($expected, true).' got '.var_export($actual, true);
        throw new Exception($detail);
    }
}

function assertContains(string $needle, string $haystack, string $message = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new Exception($message ?: 'Did not find '.$needle.' in output');
    }
}

function assertMatches(string $pattern, string $value, string $message = ''): void {
    if (!preg_match($pattern, $value)) {
        throw new Exception($message ?: 'Value did not match '.$pattern.': '.$value);
    }
}

function assertInstanceOf(string $class, $object, string $message = ''): void {
    if (!($object instanceof $class)) {
        throw new Exception($message ?: 'Expected instance of '.$class);
    }
}

$tests = [];

function test(string $name, callable $fn): void {
    global $tests;
    $tests[] = [$name, $fn];
}

function run_cli_snippet(string $code): array {
    $cmd = PHP_BINARY . ' -d display_errors=0 -r ' . escapeshellarg($code) . ' 2>/dev/null';
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    return [$exitCode, implode("\n", $output)];
}

function with_server(array $server, callable $fn): void {
    $backup = $_SERVER;
    $_SERVER = $server;
    try {
        $fn();
    } finally {
        $_SERVER = $backup;
    }
}

function with_get(array $get, callable $fn): void {
    $backup = $_GET;
    $_GET = $get;
    try {
        $fn();
    } finally {
        $_GET = $backup;
    }
}

function with_post(array $post, callable $fn): void {
    $backup = $_POST;
    $_POST = $post;
    try {
        $fn();
    } finally {
        $_POST = $backup;
    }
}

function with_cfg(object $cfg, callable $fn): void {
    $hadCfg = array_key_exists('CFG', $GLOBALS);
    $backup = $hadCfg ? $GLOBALS['CFG'] : null;
    $GLOBALS['CFG'] = $cfg;
    try {
        $fn();
    } finally {
        if ($hadCfg) {
            $GLOBALS['CFG'] = $backup;
        } else {
            unset($GLOBALS['CFG']);
        }
    }
}

function with_error_log_disabled(callable $fn): void {
    $old = ini_get('error_log');
    ini_set('error_log', '/dev/null');
    try {
        $fn();
    } finally {
        if ($old !== false) {
            ini_set('error_log', $old);
        }
    }
}

function with_session(string $name, string $id, callable $fn): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $oldName = session_name();
    $oldId = session_id();
    session_name($name);
    session_id($id);
    try {
        $fn();
    } finally {
        session_id($oldId);
        session_name($oldName);
    }
}

$uPath = realpath(__DIR__ . '/../vendor/tsugi/lib/src/Util/U.php');
$uPathEscaped = str_replace("'", "\\'", $uPath ?: '');

// die_with_error_log

test('die_with_error_log', function () use ($uPathEscaped) {
    $code = "require '$uPathEscaped';".
        "function print_stack_trace(){echo 'STACK';}".
        "function htmlent_utf8(\$s){return \$s;}".
        "\\Tsugi\\Util\\U::die_with_error_log('boom');";
    [, $out] = run_cli_snippet($code);
    assertContains('boom', $out);
    assertContains('STACK', $out);
});

// echo_log

test('echo_log', function () {
    with_error_log_disabled(function () {
        ob_start();
        U::echo_log('<tag>');
        $out = ob_get_clean();
        assertSame('&lt;tag&gt;', $out);
    });
});

// session_safe_id

test('session_safe_id', function () {
    with_session('SID', '12345678901', function () {
        assertSame('**********678901', U::session_safe_id());
    });
    with_session('SID', 'short', function () {
        assertSame(null, U::session_safe_id());
    });
});

// print_stack_trace

test('print_stack_trace', function () {
    with_error_log_disabled(function () {
        assertSame(null, U::print_stack_trace());
    });
});

// get

test('get', function () {
    assertSame('x', U::get(['a' => 'x'], 'a', 'y'));
    assertSame('y', U::get(['a' => null], 'a', 'y'));
    assertSame('y', U::get('not-array', 'a', 'y'));
});

// htmlpre_utf8

test('htmlpre_utf8', function () {
    assertSame('&lt;div>', U::htmlpre_utf8('<div>'));
});

// htmlspec_utf8

test('htmlspec_utf8', function () {
    assertSame('&lt;div&gt;', U::htmlspec_utf8('<div>'));
    assertSame(['x'], U::htmlspec_utf8(['x']));
});

// htmlent_utf8

test('htmlent_utf8', function () {
    assertSame('&lt;div&gt;', U::htmlent_utf8('<div>'));
});

// safe_href

test('safe_href', function () {
    assertSame('a&quot;bc', U::safe_href('a"b<c'));
    assertSame(['x'], U::safe_href(['x']));
});

// lti_sha256

test('lti_sha256', function () {
    assertSame(hash('sha256', 'abc'), U::lti_sha256('abc'));
    assertSame(null, U::lti_sha256(123));
});

// route_get_local_path

test('route_get_local_path', function () {
    with_server([
        'REQUEST_URI' => '/tsugi/lti/some/cool/stuff',
        'DOCUMENT_ROOT' => '/var/www',
    ], function () {
        $local = U::route_get_local_path('/var/www/tsugi/lti');
        assertSame('some/cool/stuff', $local);
    });
});

// get_request_document

test('get_request_document', function () {
    with_server(['REQUEST_URI' => '/py4e/lessons/intro?x=2'], function () {
        assertSame('intro', U::get_request_document());
    });
});

// get_base_url

test('get_base_url', function () {
    assertSame('http://localhost:8888', U::get_base_url('http://localhost:8888/tsugi'));
    assertSame('https://example.com', U::get_base_url('https://example.com/path'));
});

// get_rest_path

test('get_rest_path', function () {
    assertSame('/py4e/lessons/intro', U::get_rest_path('/py4e/lessons/intro?x=2'));
    assertSame('/py4e/lessons/intro', U::get_rest_path('/py4e/lessons/intro/?x=2'));
    assertSame(['x'], U::get_rest_path(['x']));
});

// get_rest_parent

test('get_rest_parent', function () {
    assertSame('/py4e/lessons', U::get_rest_parent('/py4e/lessons/intro?x=2'));
    assertSame('/py4e/lessons/intro', U::get_rest_parent('/py4e/lessons/intro/?x=2'));
});

// parse_rest_path

test('parse_rest_path', function () {
    $parts = U::parse_rest_path('/py4e/lessons/intro?x=2', '/py4e/koseu.php');
    assertSame(['/py4e', 'lessons', 'intro'], $parts);
    assertSame(false, U::parse_rest_path('/elsewhere', '/py4e/koseu.php'));
});

// rest_path

test('rest_path', function () {
    $cfg = new class {
        public string $wwwroot = 'http://localhost:8888/tsugi';
    };
    with_cfg($cfg, function () {
        $path = U::rest_path('/tsugi/rows/add/1', '/tsugi/tsugi.php');
        assertSame('/tsugi', $path->parent);
        assertSame('http://localhost:8888', $path->base_url);
        assertSame('rows', $path->controller);
        assertSame('add', $path->action);
        assertSame(['1'], $path->parameters);
        assertSame('/tsugi/rows', $path->current);
        assertSame('/tsugi/rows/add/1', $path->full);
    });
});

// addSession

test('addSession', function () {
    with_session('SID', 'abc123', function () {
        $url = U::addSession('http://example.com/path', true);
        assertSame('http://example.com/path?SID=abc123', $url);
    });
});

// reconstruct_query

test('reconstruct_query', function () {
    with_session('SID', 'abc123', function () {
        with_get(['SID' => 'skip', 'foo' => '1', 'bar' => '2'], function () {
            $url = U::reconstruct_query('http://example.com/path', ['bar' => '9', 'baz' => '3']);
            assertSame('http://example.com/path?foo=1&bar=9&baz=3', $url);
        });
    });
});

// add_url_parm

test('add_url_parm', function () {
    assertSame('http://example.com/path?x=1', U::add_url_parm('http://example.com/path', 'x', '1'));
    assertSame('http://example.com/path?x=1&y=2', U::add_url_parm('http://example.com/path?x=1', 'y', '2'));
    assertSame(123, U::add_url_parm(123, 'x', '1'));
});

// absolute_url_ref + absolute_url

test('absolute_url', function () {
    $cfg = new class {
        public string $apphome = 'http://localhost:8888/tsugi';
    };
    with_cfg($cfg, function () {
        assertSame('http://localhost:8888/tsugi/foo', U::absolute_url('foo'));
        assertSame('http://localhost:8888/tsugi/foo', U::absolute_url('/foo'));
        assertSame('https://example.com/x', U::absolute_url('https://example.com/x'));
        $ref = 'foo';
        U::absolute_url_ref($ref);
        assertSame('http://localhost:8888/tsugi/foo', $ref);
    });
});

// remove_relative_path

test('remove_relative_path', function () {
    assertSame('a/b/c', U::remove_relative_path('a/b/c'));
    assertSame('a/b/c/', U::remove_relative_path('a/b/c/'));
    assertSame('a/c/', U::remove_relative_path('a/./c/'));
    assertSame('c/', U::remove_relative_path('a/../c/'));
});

// apache_request_headers

test('apache_request_headers', function () {
    with_server([
        'HTTP_ACCEPT_LANGUAGE' => 'en',
        'HTTP_X_TEST' => '1',
        'SOME_KEY' => 'value',
    ], function () {
        $headers = U::apache_request_headers();
        assertSame('en', $headers['Accept-Language']);
        assertSame('1', $headers['X-Test']);
        assertSame('value', $headers['SOME_KEY']);
    });
});

// http_response_code

test('http_response_code', function () {
    $original = U::http_response_code();
    $set = U::http_response_code(418);
    assertSame(418, $set);
    assertSame(418, U::http_response_code());
    U::http_response_code($original);
});

// isCli

test('isCli', function () {
    if (php_sapi_name() === 'cli') {
        with_server([], function () {
            assertTrue(U::isCli());
        });
        with_server(['REMOTE_ADDR' => '127.0.0.1'], function () {
            assertFalse(U::isCli());
        });
    }
});

// lmsDie

test('lmsDie', function () use ($uPathEscaped) {
    $code = "require '$uPathEscaped';".
        "\$CFG = new class { public \$DEVELOPER = true; };".
        "\$DEBUG_STRING = 'debug';".
        "\\Tsugi\\Util\\U::lmsDie('bye');";
    [, $out] = run_cli_snippet($code);
    assertContains('bye', $out);
    assertContains('<pre>', $out);
    assertContains('debug', $out);
});

// line_out, error_out, success_out

test('line_out', function () {
    ob_start();
    U::line_out('<tag>');
    $out = ob_get_clean();
    assertSame('&lt;tag&gt;<br/>', rtrim($out, "\r\n"));
});

test('error_out', function () {
    ob_start();
    U::error_out('<tag>');
    $out = ob_get_clean();
    assertContains('color:red', $out);
    assertContains('&lt;tag&gt;', $out);
});

test('success_out', function () {
    ob_start();
    U::success_out('<tag>');
    $out = ob_get_clean();
    assertContains('color:green', $out);
    assertContains('&lt;tag&gt;', $out);
});

// startsWith / endsWith

test('startsWith', function () {
    assertTrue(U::startsWith('hello', 'he'));
    assertFalse(U::startsWith('hello', 'ha'));
});

test('endsWith', function () {
    assertTrue(U::endsWith('hello', 'lo'));
    assertFalse(U::endsWith('hello', 'he'));
});

// goodFolder

test('goodFolder', function () {
    assertTrue(U::goodFolder('abc-123_def'));
    assertFalse(U::goodFolder('1abc'));
});

// conservativeUrl

test('conservativeUrl', function () {
    assertTrue(U::conservativeUrl('http://example.com/path'));
    assertFalse(U::conservativeUrl('http://example.com/*'));
    assertFalse(U::conservativeUrl('http://example.com/\\x'));
});

// curPHPUrl

test('curPHPUrl', function () {
    with_server([
        'HTTPS' => 'on',
        'HTTP_HOST' => 'example.com',
        'SERVER_PORT' => '8888',
        'REQUEST_URI' => '/path?x=1',
    ], function () {
        assertSame('https://example.com/path?x=1', U::curPHPUrl());
    });
});

// array_Integer_Serialize / array_Integer_Serialize_Map

test('array_Integer_Serialize', function () {
    assertSame('1=42,2=43', U::array_Integer_Serialize([1 => 42, 2 => 43]));
    assertSame('3=4', U::array_Integer_Serialize_Map(3, 4));
    try {
        U::array_Integer_Serialize_Map('x', 4);
        throw new Exception('Expected exception');
    } catch (Exception $e) {
        assertContains('array_Integer_Serialize requires integers', $e->getMessage());
    }
});

// array_Integer_Deserialize

test('array_Integer_Deserialize', function () {
    assertSame([1 => 42, 2 => 43], U::array_Integer_Deserialize('1=42,2=43'));
    try {
        U::array_Integer_Deserialize('1=a');
        throw new Exception('Expected exception');
    } catch (Exception $e) {
        assertContains('array_Integer_Deserialize requires integers', $e->getMessage());
    }
});

// array_kshift

test('array_kshift', function () {
    $arr = ['x' => 'ball', 'y' => 'hat'];
    $first = U::array_kshift($arr);
    assertSame(['x' => 'ball'], $first);
    assertSame(['y' => 'hat'], $arr);
});

// allow_track

test('allow_track', function () {
    with_server(['HTTP_DNT' => '1'], function () {
        assertFalse(U::allow_track());
    });
    with_server([], function () {
        assertTrue(U::allow_track());
    });
});

// safe_var_dump

test('safe_var_dump', function () {
    $hash = md5('topsecret');
    $dump = U::safe_var_dump([
        'secret' => 'topsecret',
        'nested' => ['secret' => 'topsecret'],
    ]);
    assertContains($hash, $dump);
    assertFalse(strpos($dump, 'topsecret') !== false, 'Secret should be masked');
});

// safe_array

test('safe_array', function () {
    $out = U::safe_array(['mysecret' => 'x', 'other' => 'y']);
    assertSame('*****', $out['mysecret']);
    assertSame('y', $out['other']);
});

// conversion_time / iso8601

test('conversion_time', function () {
    assertSame('202001020304', U::conversion_time('2020-01-02 03:04:05'));
});

test('iso8601', function () {
    assertSame('2020-01-02T03:04:05+00:00', U::iso8601('2020-01-02 03:04:05'));
});

// apcAvailable / apcuAvailable / appCache

test('apcAvailable', function () {
    with_error_log_disabled(function () {
        assertSame(U::apcuAvailable(), U::apcAvailable());
    });
});

test('apcuAvailable', function () {
    $expected = function_exists('apcu_enabled') && apcu_enabled();
    assertSame($expected, U::apcuAvailable());
});

test('appCache', function () {
    $cfg = new class {
        public function serverPrefix(): string {
            return 'unit-test';
        }
    };
    with_cfg($cfg, function () {
        $default = U::appCacheGet('missing', 'default');
        assertSame('default', $default);
        U::appCacheSet('value-key', 'value', 1);
        if (U::apcuAvailable()) {
            assertSame('value', U::appCacheGet('value-key'));
            U::appCacheDelete('value-key');
            assertSame('default', U::appCacheGet('value-key', 'default'));
        } else {
            U::appCacheDelete('value-key');
        }
    });
});

// getCaller

test('getCaller', function () {
    $caller = U::getCaller();
    assertTrue($caller === null || is_string($caller), 'Expected caller to be string or null');
});

// displaySize

test('displaySize', function () {
    assertSame('3GB', U::displaySize(3 * 1024 * 1024 * 1024));
    assertSame('3MB', U::displaySize(3 * 1024 * 1024));
    assertSame('3KB', U::displaySize(3 * 1024));
    assertSame('512B', U::displaySize(512));
});

// createGUID / isGUIDValid

test('createGUID', function () {
    with_server([
        'HTTP_HOST' => 'example.com',
        'REQUEST_URI' => '/path',
    ], function () {
        $guid = U::createGUID();
        assertMatches('/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/', $guid);
    });
});

test('isGUIDValid', function () {
    // Standard UUID/GUID format: 8-4-4-4-12.
    assertTrue(U::isGUIDValid('11111111-2222-3333-4444-555566667777'));
    assertFalse(U::isGUIDValid('not-a-guid'));
});

// getServerBase

test('getServerBase', function () {
    assertSame('http://example.com:8888', U::getServerBase('http://example.com:8888/path'));
    assertSame('https://example.com', U::getServerBase('https://example.com/path'));
});

// isValidCSSColor

test('isValidCSSColor', function () {
    assertTrue(U::isValidCSSColor('#abc'));
    assertTrue(U::isValidCSSColor('#AABBCC'));
    assertFalse(U::isValidCSSColor('red'));
});

// strlen / isEmpty / isNotEmpty / isKeyNotEmpty

class StringableExample implements Stringable {
    public function __toString(): string {
        return 'abc';
    }
}

test('strlen', function () {
    assertSame(0, U::strlen(null));
    assertSame(0, U::strlen(false));
    assertSame(0, U::strlen(true));
    assertSame(3, U::strlen(123));
    assertSame(3, U::strlen(new StringableExample()));
    assertSame(0, U::strlen(['x']));
});

test('isEmpty', function () {
    assertTrue(U::isEmpty(''));
    assertFalse(U::isEmpty('0'));
    assertFalse(U::isEmpty(0));
    assertTrue(U::isEmpty(false));
});

test('isNotEmpty', function () {
    assertTrue(U::isNotEmpty('0'));
    assertFalse(U::isNotEmpty(''));
});

test('isKeyNotEmpty', function () {
    assertTrue(U::isKeyNotEmpty(['x' => '0'], 'x'));
    assertFalse(U::isKeyNotEmpty(['x' => ''], 'x'));
});

// escapeHtml

test('escapeHtml', function () {
    assertSame('&lt;div&gt;', U::escapeHtml('<div>'));
    assertSame('', U::escapeHtml(['x']));
});

// json_decode

test('json_decode', function () {
    $obj = U::json_decode('{"a":1}');
    assertInstanceOf(stdClass::class, $obj);
    assertSame(1, $obj->a);
    $empty = U::json_decode('{');
    assertInstanceOf(stdClass::class, $empty);
});

$passed = 0;
$failed = 0;
foreach ($tests as [$name, $fn]) {
    try {
        $fn();
        $passed++;
    } catch (Throwable $t) {
        $failed++;
        echo "FAIL: $name\n";
        echo $t->getMessage()."\n";
    }
}

echo "Passed: $passed, Failed: $failed\n";
if ($failed > 0) {
    exit(1);
}
