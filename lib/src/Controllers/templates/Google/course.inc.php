<?php
/**
 * Queue and log for one Classroom course.
 *
 * @var array<string,mixed> $row
 * @var array{connected:bool,status:string,checked_at:string} $connection
 * @var array<int,array<string,mixed>> $queue
 * @var array{failures:array<int,array<string,mixed>>,successes:array<int,array<string,mixed>>} $log
 * @var string $courseUrl
 * @var string $sendUrl
 * @var string $resetUrl
 * @var string $reconnectUrl
 * @var string $coursesUrl
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$title = trim((string) ($row['title'] ?? ''));
if ( $title === '' ) {
    $title = 'Classroom course';
}
$kindLabel = function ($kind) {
    if ( $kind === 'score' ) {
        return 'Score';
    }
    if ( $kind === 'coursework_create' ) {
        return 'Assignment';
    }
    if ( $kind === 'reconnect' ) {
        return 'Reconnect';
    }
    if ( $kind === 'due_date' ) {
        return 'Due date';
    }
    if ( $kind === 'lesson_link' ) {
        return 'Lesson';
    }
    if ( $kind === 'course_link' ) {
        return 'Course';
    }
    return (string) $kind;
};
$percent = function ($grade) {
    if ( $grade === null || $grade === '' ) {
        return '—';
    }
    return (string) (int) round((float) $grade * 100);
};
?>
<p><a href="<?= $h($coursesUrl) ?>">Classroom courses</a></p>
<h1><?= $h($title) ?></h1>
<?php if ( ! empty($connection['connected']) ) { ?>
<p>Connected to Google Classroom<?php
    if ( $connection['checked_at'] !== '' ) {
        echo '. Last checked '.$h($connection['checked_at']);
    }
?>.</p>
<?php } else if ( $connection['status'] !== '' ) { ?>
<p>Needs reconnect. <?= $h($connection['status']) ?></p>
<?php } else { ?>
<p>Not connected to Google Classroom.</p>
<?php } ?>
<p>
<a class="btn btn-default" href="<?= $h($reconnectUrl) ?>">Reconnect</a>
</p>
<form method="post" action="<?= $h($resetUrl) ?>" style="display:inline">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<button type="submit" class="btn btn-default">Reset queue</button>
</form>
<form method="post" action="<?= $h($sendUrl) ?>" style="display:inline">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<button type="submit" class="btn btn-primary">Send queue</button>
</form>

<h2>Queue</h2>
<?php if ( count($queue) < 1 ) { ?>
<p>Nothing is waiting.</p>
<?php } else { ?>
<table class="table">
<thead>
<tr><th>Student</th><th>Assignment</th><th>Score</th><th>State</th><th>Detail</th><th>Updated</th></tr>
</thead>
<tbody>
<?php foreach ( $queue as $item ) {
    if ( ! is_array($item) ) {
        continue;
    }
    $when = trim((string) ($item['updated_at'] ?? ''));
    if ( $when === '' ) {
        $when = (string) ($item['created_at'] ?? '');
    }
    echo '<tr>';
    echo '<td>'.$h($item['displayname'] ?? '').'</td>';
    echo '<td>'.$h($item['link_title'] ?? '').'</td>';
    echo '<td>'.$h($percent($item['grade'] ?? null)).'</td>';
    echo '<td>'.$h($item['state'] ?? '').'</td>';
    echo '<td>'.$h($item['detail'] ?? '').'</td>';
    echo '<td>'.$h($when).'</td>';
    echo '</tr>';
} ?>
</tbody>
</table>
<?php } ?>

<h2>Log</h2>
<p>Each send, due date, assignment install, and reconnect is kept here. Reset does not erase this list.</p>
<?php
$sections = array(
    array('title' => 'Recent errors', 'rows' => $log['failures'] ?? array(), 'empty' => 'No recent errors.'),
    array('title' => 'Recent successes', 'rows' => $log['successes'] ?? array(), 'empty' => 'No completed attempts yet.'),
);
foreach ( $sections as $section ) {
    echo '<h3>'.$h($section['title']).'</h3>';
    $entries = is_array($section['rows']) ? $section['rows'] : array();
    if ( count($entries) < 1 ) {
        echo '<p>'.$h($section['empty']).'</p>';
        continue;
    }
    echo '<ul>';
    foreach ( $entries as $entry ) {
        if ( ! is_array($entry) ) {
            continue;
        }
        $parts = array($kindLabel($entry['kind'] ?? ''));
        $link = trim((string) ($entry['link_title'] ?? ''));
        if ( $link !== '' ) {
            $parts[] = $link;
        }
        $name = trim((string) ($entry['displayname'] ?? ''));
        if ( $name !== '' ) {
            $parts[] = $name;
        }
        $http = $entry['http_response'] ?? null;
        if ( $http !== null && $http !== '' ) {
            $parts[] = 'HTTP '.$http;
        }
        $detail = trim((string) ($entry['detail'] ?? ''));
        if ( $detail !== '' ) {
            $parts[] = $detail;
        }
        echo '<li>'.$h($entry['created_at'] ?? '');
        echo ' '.$h(implode(', ', $parts));
        $extra = trim((string) ($entry['url'] ?? ''));
        $request = trim((string) ($entry['request'] ?? ''));
        if ( $request !== '' ) {
            $extra .= ($extra === '' ? '' : "\n").$request;
        }
        if ( $extra !== '' ) {
            echo '<details><summary>Detail</summary><pre>'.$h($extra).'</pre></details>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
