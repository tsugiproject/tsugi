<?php
/**
 * Classes the connected Google account teaches, plus installs already in Tsugi.
 *
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $taught
 * @var array<int,array<string,mixed>> $other
 * @var string $email
 * @var array{connected:bool,status:string,checked_at:string} $connection
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<h1>Classroom courses</h1>
<?php if ( $email !== '' ) { ?>
<p>Connected as <?= $h($email) ?>. These are the classes that account teaches.</p>
<?php } else if ( ! empty($connection['connected']) ) { ?>
<p>Connected to Google Classroom<?php
    if ( $connection['checked_at'] !== '' ) {
        echo '. Last checked '.$h($connection['checked_at']);
    }
?>.</p>
<?php } else if ( $connection['status'] !== '' ) { ?>
<p>Google Classroom needs to be reconnected. <?= $h($connection['status']) ?></p>
<?php } else { ?>
<p>Not connected to Google Classroom. Connect from Settings, then install a lesson.</p>
<?php } ?>
<?php if ( count($taught) > 0 ) { ?>
<table class="table">
<thead>
<tr><th>Class</th><th>Queue</th></tr>
</thead>
<tbody>
<?php foreach ( $taught as $course ) {
    if ( ! is_array($course) ) {
        continue;
    }
    $name = trim((string) ($course['name'] ?? ''));
    if ( $name === '' ) {
        $name = 'Class';
    }
    $contextId = (int) ($course['context_id'] ?? 0);
    echo '<tr><td>'.$h($name).'</td><td>';
    if ( $contextId > 0 ) {
        $href = $CFG->wwwroot.'/gclass/course/'.$contextId;
        echo '<a href="'.$h($href).'">'.(int) ($course['waiting'] ?? 0).' waiting</a>';
    } else {
        echo 'Not installed yet';
    }
    echo '</td></tr>';
} ?>
</tbody>
</table>
<p>A class gets a queue after you install an assignment into it from lessons.</p>
<?php } else if ( count($rows) < 1 ) { ?>
<p>No Classroom courses yet. Install an assignment from lessons after you connect.</p>
<?php } ?>
<?php if ( count($taught) > 0 && count($other) > 0 ) { ?>
<h2>Installed earlier</h2>
<p>These are in Tsugi, but they are not classes of the Google account above.</p>
<?php } ?>
<?php if ( count($taught) < 1 && count($other) > 0 ) { ?>
<table class="table">
<thead>
<tr><th>Course</th><th>Waiting</th></tr>
</thead>
<tbody>
<?php } else if ( count($other) > 0 ) { ?>
<table class="table">
<thead>
<tr><th>Course</th><th>Waiting</th></tr>
</thead>
<tbody>
<?php }
if ( count($other) > 0 ) {
    foreach ( $other as $course ) {
        if ( ! is_array($course) ) {
            continue;
        }
        $id = (int) ($course['context_id'] ?? 0);
        $title = trim((string) ($course['title'] ?? ''));
        if ( $title === '' ) {
            $title = 'Course '.$id;
        }
        $href = $CFG->wwwroot.'/gclass/course/'.$id;
        echo '<tr><td><a href="'.$h($href).'">'.$h($title).'</a></td>';
        echo '<td>'.(int) ($course['waiting'] ?? 0).'</td></tr>';
    }
    echo '</tbody></table>';
}
?>
