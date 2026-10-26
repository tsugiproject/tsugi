<?php
/**
 * Plus AGS log. Errors are listed before successful sends.
 *
 * @var array{failures:array<int,array<string,mixed>>,successes:array<int,array<string,mixed>>} $log
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$when = function ($value) use ($h) {
    if ( ! is_string($value) || trim($value) === '' ) {
        return 'Never';
    }
    return $h($value);
};
$line = function (array $entry) {
    $parts = array();
    $label = trim((string) ($entry['kind_label'] ?? ''));
    if ( $label !== '' ) {
        $parts[] = $label;
    }
    $title = trim((string) ($entry['link_title'] ?? ''));
    if ( $title !== '' ) {
        $parts[] = $title;
    }
    $name = trim((string) ($entry['displayname'] ?? ''));
    if ( $name !== '' ) {
        $parts[] = $name;
    }
    $http = $entry['http_response'] ?? null;
    if ( $http !== null && $http !== '' ) {
        $parts[] = 'HTTP '.$http;
    }
    $detail = trim((string) ($entry['detail_text'] ?? ''));
    if ( $detail !== '' ) {
        $parts[] = $detail;
    }
    return implode(', ', $parts);
};
$showDetail = $h(__('Show detail'));
$hideDetail = $h(__('Hide detail'));
?>
<p>This is the Assignment and Grade Services log for this Plus course. A line item create and a score post are kept here. Errors are listed first.</p>
<?php
$sections = array(
    array('title' => 'Recent errors', 'rows' => $log['failures'] ?? array(), 'empty' => 'No recent grade errors.'),
    array('title' => 'Recent sends', 'rows' => $log['successes'] ?? array(), 'empty' => 'No completed grade sends yet.'),
);
foreach ( $sections as $section ) {
    echo '<h2>'.$h($section['title']).'</h2>';
    $rows = is_array($section['rows']) ? $section['rows'] : array();
    if ( count($rows) < 1 ) {
        echo '<p>'.$h($section['empty']).'</p>';
        continue;
    }
    echo '<ul class="grade-log">';
    foreach ( $rows as $entry ) {
        if ( ! is_array($entry) ) {
            continue;
        }
        $debug = trim((string) ($entry['detail_debug'] ?? ''));
        $request = trim((string) ($entry['request'] ?? ''));
        $url = trim((string) ($entry['url'] ?? ''));
        $summary = $line($entry);
        echo '<li>';
        echo '<p class="grade-log-line">'.$when($entry['created_at'] ?? '');
        if ( $summary !== '' ) {
            echo ' '.$h($summary);
        }
        $extra = '';
        if ( $url !== '' ) {
            $extra .= $url."\n";
        }
        if ( $request !== '' ) {
            $extra .= $request."\n";
        }
        if ( $debug !== '' ) {
            $extra .= $debug;
        }
        $extra = trim($extra);
        if ( $extra !== '' ) {
            echo ' <button type="button" class="grade-log-expand" aria-expanded="false" title="'.$showDetail.'" aria-label="'.$showDetail.'" data-show="'.$showDetail.'" data-hide="'.$hideDetail.'">';
            echo '<i class="fa fa-caret-down" aria-hidden="true"></i>';
            echo '</button>';
        }
        echo '</p>';
        if ( $extra !== '' ) {
            echo '<pre class="grade-log-detail" hidden>'.$h($extra).'</pre>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<script>
document.querySelectorAll('.grade-log-expand').forEach(function (button) {
    button.addEventListener('click', function () {
        var detail = button.parentElement.nextElementSibling;
        if ( ! detail ) {
            return;
        }
        var open = detail.hidden;
        detail.hidden = ! open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        var label = open ? button.getAttribute('data-hide') : button.getAttribute('data-show');
        button.setAttribute('title', label);
        button.setAttribute('aria-label', label);
    });
});
</script>
