<?php
/**
 * Plus roster-sync log. Variables come from Roster::log.
 *
 * @var array<string, mixed> $status
 * @var string $token
 * @var string $here
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
    $label = $entry['detail_status'] ?? '';
    if ( is_string($label) && $label !== '' ) {
        $parts[] = $label;
    }
    $count = (int) ($entry['detail_count'] ?? 0);
    if ( $count > 0 ) {
        $parts[] = $count.' people';
    }
    $inserted = (int) ($entry['detail_inserted'] ?? 0);
    if ( $inserted > 0 ) {
        $parts[] = $inserted.' new';
    }
    $expired = (int) ($entry['detail_expired'] ?? 0);
    if ( $expired > 0 ) {
        $parts[] = $expired.' removed';
    }
    $errors = (int) ($entry['detail_errors'] ?? 0);
    if ( $errors > 0 ) {
        $parts[] = $errors.' errors';
    }
    $http = $entry['http_response'] ?? null;
    if ( $http !== null && $http !== '' ) {
        $parts[] = 'HTTP '.$http;
    }
    return implode(', ', $parts);
};
?>
<p>Tsugi Plus keeps this roster from the LMS that launched the course. An instructor launch refreshes it when the last start is more than 5 minutes old. A student launch refreshes it after 30 minutes. A failed token is tried again about once a minute. Someone who leaves the LMS roster stays here for a week, then the membership is marked deleted. Their work stays. A later roster row or launch puts them back.</p>

<p><b>Last start:</b> <?= $when($status['nrps_at'] ?? null) ?></p>
<p><b>People in the course:</b> <?= (int) ($status['active'] ?? 0) ?>
<?php if ( (int) ($status['removed'] ?? 0) > 0 ) { ?>
 (<?= (int) $status['removed'] ?> marked deleted)
<?php } ?>
</p>
<p><b>Membership URL:</b>
<?php if ( is_string($status['membership_url'] ?? null) && trim($status['membership_url']) !== '' ) { ?>
<?= $h($status['membership_url']) ?>
<?php } else { ?>
None yet. The next Plus launch from the LMS can supply one.
<?php } ?>
</p>

<form method="post" action="<?= $h($here.'/log') ?>">
<input type="hidden" name="token" value="<?= $h($token) ?>">
<button type="submit" class="btn btn-primary">Refresh roster now</button>
</form>

<?php
$sections = array(
    array('title' => 'Recent errors', 'rows' => $status['failures'] ?? array(), 'empty' => 'No recent roster errors.'),
    array('title' => 'Recent pulls', 'rows' => $status['successes'] ?? array(), 'empty' => 'No completed roster pulls yet.'),
);
foreach ( $sections as $section ) {
    echo '<h2>'.$h($section['title']).'</h2>';
    $rows = is_array($section['rows']) ? $section['rows'] : array();
    if ( count($rows) < 1 ) {
        echo '<p>'.$h($section['empty']).'</p>';
        continue;
    }
    echo '<ul>';
    foreach ( $rows as $entry ) {
        if ( ! is_array($entry) ) {
            continue;
        }
        $debug = (string) ($entry['detail_debug'] ?? '');
        echo '<li><p>'.$when($entry['created_at'] ?? '').'</p>';
        $summary = $line($entry);
        if ( $summary !== '' ) {
            echo '<p>'.$h($summary).'</p>';
        }
        if ( $debug !== '' ) {
            echo '<pre hidden>'.$h($debug).'</pre>';
            echo '<button type="button" class="btn btn-default" onclick="var p=this.previousElementSibling; p.hidden=!p.hidden;">Detail</button>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
