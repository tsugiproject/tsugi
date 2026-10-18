<?php

/**
 * Set lti_link.upward from existing result rows, one course at a time.
 *
 * A link is upward when any non-deleted result has a service_id and a
 * non-empty sourcedid. This only sets the flag to 1. It does not clear it.
 *
 * Each statement auto-commits. Nothing is left open across a sleep.
 *
 * Usage (from anywhere):
 *   php admin/lti-maint/upward.php
 *   php admin/lti-maint/upward.php apply
 *   php admin/lti-maint/upward.php --after=1000 --limit=25 --sleep=1 --pause=200
 *   php admin/lti-maint/upward.php apply --context=38
 *
 * --after   resume after this context_id (default 0)
 * --limit   stop after this many courses (default 0 = all)
 * --context one course, ignores --after and --limit
 * --sleep   seconds to pause after each course (default 1)
 * --pause   milliseconds to pause after each link check (default 200)
 */

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

require_once __DIR__ . '/../../config.php';

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

if ( realpath($CFG->dirroot) !== realpath(__DIR__ . '/../..') ) {
    fwrite(STDERR, "dirroot is {$CFG->dirroot}, expected this checkout.\n");
    exit(1);
}

$apply = false;
$after = 0;
$limit = 0;
$onlyContext = 0;
$sleepSeconds = 1.0;
$pauseMs = 200;

foreach ( array_slice($argv, 1) as $arg ) {
    if ( $arg === 'apply' ) {
        $apply = true;
        continue;
    }
    if ( $arg === 'dry' || $arg === 'dry-run' ) {
        continue;
    }
    if ( sscanf($arg, '--after=%d', $parsed) === 1 ) {
        $after = (int) $parsed;
        continue;
    }
    if ( sscanf($arg, '--limit=%d', $parsed) === 1 ) {
        $limit = (int) $parsed;
        continue;
    }
    if ( sscanf($arg, '--context=%d', $parsed) === 1 ) {
        $onlyContext = (int) $parsed;
        continue;
    }
    if ( sscanf($arg, '--sleep=%f', $parsed) === 1 ) {
        $sleepSeconds = (float) $parsed;
        continue;
    }
    if ( sscanf($arg, '--pause=%d', $parsed) === 1 ) {
        $pauseMs = (int) $parsed;
        continue;
    }
    fwrite(STDERR, "Unknown argument: {$arg}\n");
    fwrite(STDERR, "Usage: php upward.php [apply] [--after=N] [--limit=N] [--context=N] [--sleep=SECONDS] [--pause=MS]\n");
    exit(1);
}

if ( $after < 0 || $limit < 0 || $onlyContext < 0 || $sleepSeconds < 0 || $pauseMs < 0 ) {
    fwrite(STDERR, "after, limit, context, sleep, and pause must be >= 0.\n");
    exit(1);
}

LTIX::getConnection();

if ( $PDOX->inTransaction() ) {
    fwrite(STDERR, "Refusing to run inside a transaction.\n");
    exit(1);
}
$PDOX->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

$linkTable = $CFG->dbprefix . 'lti_link';
if ( ! $PDOX->columnExists('upward', $linkTable) ) {
    fwrite(STDERR, "lti_link.upward is missing. Run php admin/upgrade.php first.\n");
    exit(1);
}

$p = $CFG->dbprefix;

if ( $apply ) {
    upward_out("This IS NOT A DRILL! Setting lti_link.upward from result rows.");
    upward_release($PDOX);
    sleep(5);
    upward_out("...");
    upward_release($PDOX);
    sleep(5);
} else {
    upward_out("This is a dry run, use 'php ".$argv[0]." apply' to set lti_link.upward.");
}

if ( $onlyContext > 0 ) {
    $courses = $PDOX->allRowsDie(
        "SELECT context_id, title
         FROM {$p}lti_context
         WHERE context_id = :CID",
        array(':CID' => $onlyContext)
    );
} else {
    $courses = $PDOX->allRowsDie(
        "SELECT context_id, title
         FROM {$p}lti_context
         WHERE context_id > :AFTER
         ORDER BY context_id ASC",
        array(':AFTER' => $after)
    );
}
upward_release($PDOX);

$t0 = microtime(true);
$coursesSeen = 0;
$linksChecked = 0;
$set = 0;
$lastContext = $after;

foreach ( $courses as $course ) {
    if ( $limit > 0 && $coursesSeen >= $limit ) {
        break;
    }
    $coursesSeen++;
    $contextId = (int) $course['context_id'];
    $lastContext = $contextId;
    $title = trim((string) ($course['title'] ?? ''));

    $links = $PDOX->allRowsDie(
        "SELECT link_id, link_key, title
         FROM {$p}lti_link
         WHERE context_id = :CID AND deleted = 0 AND upward = 0
         ORDER BY link_id ASC",
        array(':CID' => $contextId)
    );
    upward_release($PDOX);

    $courseSets = 0;
    foreach ( $links as $link ) {
        $linkId = (int) $link['link_id'];
        $found = $PDOX->rowDie(
            "SELECT 1 AS hit
             FROM {$p}lti_result
             WHERE link_id = :LID AND deleted = 0
               AND service_id IS NOT NULL
               AND sourcedid IS NOT NULL AND sourcedid <> ''
             LIMIT 1",
            array(':LID' => $linkId)
        );
        upward_release($PDOX);
        $linksChecked++;

        if ( $found ) {
            $courseSets++;
            $set++;
            $key = (string) ($link['link_key'] ?? '');
            $linkTitle = trim((string) ($link['title'] ?? ''));
            $verb = $apply ? 'SET' : 'WOULD SET';
            upward_out("{$verb} context_id={$contextId} link_id={$linkId} key={$key} title={$linkTitle}");
            if ( $apply ) {
                $stmt = $PDOX->queryDie(
                    "UPDATE {$p}lti_link
                     SET upward = 1, updated_at = NOW()
                     WHERE link_id = :LID AND upward = 0",
                    array(':LID' => $linkId)
                );
                $stmt->closeCursor();
                upward_release($PDOX);
            }
        }

        if ( $pauseMs > 0 ) {
            upward_release($PDOX);
            usleep($pauseMs * 1000);
        }
    }

    if ( count($links) > 0 ) {
        upward_out("# context_id={$contextId} title={$title} links_checked=".count($links)." set={$courseSets}");
    } else if ( $coursesSeen % 25 === 0 ) {
        upward_out("# progress courses={$coursesSeen} last_context_id={$lastContext}");
    }

    if ( $sleepSeconds > 0 ) {
        upward_release($PDOX);
        usleep((int) round($sleepSeconds * 1000000));
    }
}

$elapsed = sprintf('%.1fs', microtime(true) - $t0);
upward_out("# courses={$coursesSeen} links_checked={$linksChecked} set={$set} elapsed={$elapsed} last_context_id={$lastContext}");

$stoppedEarly = $onlyContext < 1 && $limit > 0 && $coursesSeen >= $limit && count($courses) > $coursesSeen;
if ( $stoppedEarly ) {
    $again = $apply ? 'apply ' : '';
    upward_out("# next: php ".$argv[0]." {$again}--after={$lastContext} --limit={$limit}");
}

/**
 * @param string $line
 */
function upward_out($line)
{
    echo $line."\n";
    flush();
}

/**
 * Autocommit is on. If a statement left a transaction open, end it
 * before the next sleep so the run does not hold one.
 *
 * @param \PDO $PDOX
 */
function upward_release($PDOX)
{
    if ( $PDOX->inTransaction() ) {
        $PDOX->commit();
    }
}
