<?php
/**
 * CLI bulk mail sender (SES / MailService).
 *
 * Dry-run by default. Use --send to actually deliver.
 * See scripts/README-send-bulk-mail.md
 */

if ( php_sapi_name() !== 'cli' ) {
    http_response_code(403);
    die("Error: This script can only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/admin/context/mail_audience.php';

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Services\Mail\MailService;

const MAIL_BULK_CLI_MAX = 200;

/**
 * Print usage / calling sequence and exit.
 */
function bulk_mail_cli_usage($exit_code = 0) {
    $self = basename(__FILE__);
    echo <<<EOF
Tsugi CLI bulk mail (MailService::sendDetailedBulk / SES when configured)

USAGE
  php scripts/{$self} --help
  php scripts/{$self}

  (no parameters or --help prints this calling sequence)

DRY-RUN (default — lists audience, does not send)
  php scripts/{$self} \\
    --context-id=CONTEXT_ID \\
    --subject='Subject line' \\
    --body-file=/path/to/body.txt \\
    [--days=30] \\
    [--exclude-recent-bulk-days=30] \\
    [--limit=5] \\
    [--email=member@example.com] \\
    [--premium-only] \\
    [--include-opted-out] \\
    [--from-user-id=USER_ID]

SEND (creates mail_bulk + mail_sent rows and calls SES/mail)
  php scripts/{$self} ...same options... --send

CALLING SEQUENCE
  1. Load config.php and connect to the database
  2. Resolve audience:
       --email=...  → single context member (list of one)
       otherwise    → mail_context_audience() with days / exclude / limit / flags
  3. Print transport, filters, recipient count, and a sample of emails
  4. If --send is omitted → stop (dry-run)
  5. If --send:
       a. INSERT mail_bulk (from-user-id, context, subject, body, filter meta)
       b. For each recipient: MailService::sendDetailedBulk() with signed unsubscribe
       c. INSERT mail_sent per recipient (status, message_id, json)
       d. UPDATE mail_bulk.json with sent/skipped/failed counts
  6. Print summary and exit 0 (or non-zero on hard failure)

OPTIONS
  --context-id=N                 Required. lti_context.context_id
  --subject=TEXT                 Required (unless dry-run with only --help)
  --body-file=PATH               Required plain-text body file
  --body=TEXT                    Alternative to --body-file (small bodies only)
  --from-user-id=N               lti_user.user_id for mail_bulk.user_id (required with --send)
  --days=N                       Logged in within last N days (1–365, default 30)
  --exclude-recent-bulk-days=N   Skip if successful bulk in this context within N days
                                 (0–365, default 30; 0 = do not exclude)
  --limit=N                      Most recent N by login_at (0–200, default 0 = no limit)
  --email=ADDR                   Single context member email (ignores days/limit filters)
  --premium-only                 Premium / supporters only
  --include-opted-out            Include subscribe=-1 in audience list
                                 (MailService still skips them at send for bulk)
  --send                         Actually send (default is dry-run)
  --help, -h                     Show this help

EXAMPLES
  # Preview five most recent eligible members
  php scripts/{$self} --context-id=42 --subject='Hi' --body-file=./msg.txt \\
    --days=30 --exclude-recent-bulk-days=30 --limit=5

  # Walk the list in batches of 50 (cron-friendly)
  php scripts/{$self} --context-id=42 --subject='Hi' --body-file=./msg.txt \\
    --days=30 --exclude-recent-bulk-days=30 --limit=50 --from-user-id=1 --send

  # Test one member (unsubscribe headers included)
  php scripts/{$self} --context-id=42 --subject='Test' --body-file=./msg.txt \\
    --email=you@example.com --from-user-id=1 --send

See scripts/README-send-bulk-mail.md and docs/ses-design.md

EOF;
    exit($exit_code);
}

/**
 * @return array<string, mixed>
 */
function bulk_mail_cli_parse_args(array $argv) {
    $opts = array(
        'help' => false,
        'send' => false,
        'context_id' => 0,
        'from_user_id' => 0,
        'subject' => '',
        'body' => '',
        'body_file' => '',
        'days' => 30,
        'exclude_recent_bulk_days' => 30,
        'limit' => 0,
        'email' => '',
        'premium_only' => false,
        'include_opted_out' => false,
    );

    $long = getopt('h', array(
        'help',
        'send',
        'context-id:',
        'from-user-id:',
        'subject:',
        'body:',
        'body-file:',
        'days:',
        'exclude-recent-bulk-days:',
        'limit:',
        'email:',
        'premium-only',
        'include-opted-out',
    ), $optind);

    if ( $long === false ) {
        fwrite(STDERR, "Failed to parse options.\n\n");
        bulk_mail_cli_usage(1);
    }

    // No options at all (or only script name) → help
    if ( count($argv) <= 1 ) {
        $opts['help'] = true;
        return $opts;
    }

    if ( isset($long['h']) || isset($long['help']) ) {
        $opts['help'] = true;
    }
    if ( isset($long['send']) ) {
        $opts['send'] = true;
    }
    if ( isset($long['premium-only']) ) {
        $opts['premium_only'] = true;
    }
    if ( isset($long['include-opted-out']) ) {
        $opts['include_opted_out'] = true;
    }
    if ( isset($long['context-id']) ) {
        $opts['context_id'] = (int) $long['context-id'];
    }
    if ( isset($long['from-user-id']) ) {
        $opts['from_user_id'] = (int) $long['from-user-id'];
    }
    if ( isset($long['subject']) ) {
        $opts['subject'] = (string) $long['subject'];
    }
    if ( isset($long['body']) ) {
        $opts['body'] = (string) $long['body'];
    }
    if ( isset($long['body-file']) ) {
        $opts['body_file'] = (string) $long['body-file'];
    }
    if ( isset($long['days']) ) {
        $opts['days'] = (int) $long['days'];
    }
    if ( isset($long['exclude-recent-bulk-days']) ) {
        $opts['exclude_recent_bulk_days'] = (int) $long['exclude-recent-bulk-days'];
    }
    if ( isset($long['limit']) ) {
        $opts['limit'] = (int) $long['limit'];
    }
    if ( isset($long['email']) ) {
        $opts['email'] = strtolower(trim((string) $long['email']));
    }

    return $opts;
}

// ---- main ----

$opts = bulk_mail_cli_parse_args($argv);
if ( $opts['help'] ) {
    bulk_mail_cli_usage(0);
}

if ( $opts['context_id'] < 1 ) {
    fwrite(STDERR, "Error: --context-id is required.\n\n");
    bulk_mail_cli_usage(1);
}

$body = $opts['body'];
if ( $opts['body_file'] !== '' ) {
    $path = $opts['body_file'];
    if ( !is_readable($path) ) {
        fwrite(STDERR, "Error: cannot read --body-file=$path\n");
        exit(1);
    }
    $body = file_get_contents($path);
    if ( $body === false ) {
        fwrite(STDERR, "Error: failed reading --body-file=$path\n");
        exit(1);
    }
}
$subject = trim($opts['subject']);
$body = (string) $body;
if ( $subject === '' || trim($body) === '' ) {
    fwrite(STDERR, "Error: --subject and --body or --body-file are required.\n\n");
    bulk_mail_cli_usage(1);
}

LTIX::getConnection();
global $CFG, $PDOX;

$context_id = $opts['context_id'];
$context_row = $PDOX->rowDie(
    "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
    array(':CID' => $context_id)
);
if ( $context_row === false || $context_row === null ) {
    fwrite(STDERR, "Error: context_id=$context_id not found.\n");
    exit(1);
}
$context_title = $context_row['title'] ? $context_row['title'] : "Context #$context_id";

$single_email = $opts['email'];
if ( $single_email !== '' ) {
    if ( strpos($single_email, '@') === false ) {
        fwrite(STDERR, "Error: --email looks invalid.\n");
        exit(1);
    }
    $rows = mail_context_audience_by_email($context_id, $single_email);
    $meta = array(
        'source' => 'cli',
        'single_email' => $single_email,
        'audience_count' => count($rows),
    );
} else {
    $days = $opts['days'];
    $exclude = $opts['exclude_recent_bulk_days'];
    $limit = $opts['limit'];
    if ( $days < 1 || $days > 365 ) {
        fwrite(STDERR, "Error: --days must be 1–365.\n");
        exit(1);
    }
    if ( $exclude < 0 || $exclude > 365 ) {
        fwrite(STDERR, "Error: --exclude-recent-bulk-days must be 0–365.\n");
        exit(1);
    }
    if ( $limit < 0 || $limit > MAIL_BULK_CLI_MAX ) {
        fwrite(STDERR, "Error: --limit must be 0–".MAIL_BULK_CLI_MAX.".\n");
        exit(1);
    }
    $rows = mail_context_audience(
        $context_id,
        $days,
        $opts['include_opted_out'],
        $opts['premium_only'],
        $exclude,
        $limit
    );
    $meta = array(
        'source' => 'cli',
        'days' => $days,
        'exclude_recent_bulk_days' => $exclude,
        'limit' => $limit,
        'include_opted_out' => $opts['include_opted_out'] ? 1 : 0,
        'premium_only' => $opts['premium_only'] ? 1 : 0,
        'audience_count' => count($rows),
    );
}

$count = count($rows);
if ( $count < 1 ) {
    fwrite(STDERR, "Error: no recipients match the filters.\n");
    exit(1);
}
if ( $count > MAIL_BULK_CLI_MAX ) {
    fwrite(STDERR, "Error: audience is $count; max per run is ".MAIL_BULK_CLI_MAX.". Use --limit.\n");
    exit(1);
}

$mail_ok = isset($CFG->maildomain) && $CFG->maildomain !== false;
echo "Context: #$context_id ".trim((string) $context_title)."\n";
echo "Transport: ".MailService::transport().($mail_ok ? '' : ' (MAIL DISABLED — set \$CFG->maildomain)')."\n";
echo "Mode: ".($opts['send'] ? 'SEND' : 'DRY-RUN')."\n";
echo "Subject: $subject\n";
echo "Filters: ".json_encode($meta)."\n";
echo "Recipients: $count\n";
$sample = 0;
foreach ( $rows as $row ) {
    if ( $sample++ >= 20 ) {
        echo "  ...\n";
        break;
    }
    $login = isset($row['login_at']) ? $row['login_at'] : '';
    echo "  - ".$row['email']." (user_id=".$row['user_id']." login_at=$login)\n";
}

if ( !$opts['send'] ) {
    echo "\nDry-run complete. Re-run with --send --from-user-id=N to deliver.\n";
    exit(0);
}

if ( !$mail_ok ) {
    fwrite(STDERR, "Error: mail disabled (\$CFG->maildomain).\n");
    exit(1);
}

$from_user_id = $opts['from_user_id'];
if ( $from_user_id < 1 ) {
    fwrite(STDERR, "Error: --from-user-id is required with --send (mail_bulk.user_id).\n");
    exit(1);
}
$from_row = $PDOX->rowDie(
    "SELECT user_id FROM {$CFG->dbprefix}lti_user WHERE user_id = :UID",
    array(':UID' => $from_user_id)
);
if ( $from_row === false || $from_row === null ) {
    fwrite(STDERR, "Error: --from-user-id=$from_user_id not found in lti_user.\n");
    exit(1);
}

$PDOX->queryDie(
    "INSERT INTO {$CFG->dbprefix}mail_bulk
        (user_id, context_id, subject, body, json, created_at)
     VALUES (:UID, :CID, :SUB, :BOD, :JSON, NOW())",
    array(
        ':UID' => $from_user_id,
        ':CID' => $context_id,
        ':SUB' => substr($subject, 0, 256),
        ':BOD' => $body,
        ':JSON' => json_encode($meta),
    )
);
$bulk_id = $PDOX->lastInsertId();
echo "mail_bulk.bulk_id=$bulk_id\n";

$sent = 0;
$skipped = 0;
$failed = 0;
$errors = array();
$stopped_rate_limit = false;
$not_attempted = 0;
$audience_total = count($rows);
$attempted = 0;
$send_calls = 0;
$run_t0 = microtime(true);

foreach ( $rows as $row ) {
    $to = trim((string) U::get($row, 'email', ''));
    $user_to = (int) U::get($row, 'user_id', 0);
    if ( $to === '' || $user_to < 1 ) {
        $skipped++;
        $attempted++;
        continue;
    }
    $token = MailService::computeCheck($user_to);
    $t0 = microtime(true);
    $detail = MailService::sendDetailedBulk($to, $subject, $body, $user_to, $token);
    $send_calls++;
    $elapsed_s = microtime(true) - $t0;
    $elapsed_ms = (int) round($elapsed_s * 1000);
    $elapsed_txt = sprintf('%.3fs', $elapsed_s);
    $status = 'failed';
    if ( U::get($detail, 'suppressed') ) {
        $skipped++;
        $status = 'suppressed';
        echo "  skip suppressed $to ($elapsed_txt)\n";
    } else if ( U::get($detail, 'success') ) {
        $sent++;
        $status = 'sent';
        $mid = U::get($detail, 'message_id', '');
        echo "  sent $to".($mid ? " message_id=$mid" : '')." ($elapsed_txt)\n";
    } else if ( U::get($detail, 'disabled') ) {
        $failed++;
        $status = 'disabled';
        $errors[] = $to.': mail disabled';
        echo "  FAIL disabled $to ($elapsed_txt)\n";
    } else {
        $failed++;
        $err = (string) U::get($detail, 'error', 'unknown');
        if ( count($errors) < 10 ) {
            $errors[] = $to.': '.$err;
        }
        if ( MailService::isRateLimited($detail) ) {
            $status = 'rate_limited';
            $stopped_rate_limit = true;
            echo "  RATE LIMITED $to: $err ($elapsed_txt)\n";
        } else {
            echo "  FAIL $to: $err ($elapsed_txt)\n";
        }
    }

    $message_id = U::get($detail, 'message_id');
    if ( !is_string($message_id) || $message_id === '' ) {
        $message_id = null;
    }
    $sent_json = json_encode(array(
        'status' => $status,
        'transport' => U::get($detail, 'transport'),
        'message_id' => $message_id,
        'error' => U::get($detail, 'error'),
        'rate_limited' => MailService::isRateLimited($detail) ? 1 : 0,
        'elapsed_ms' => $elapsed_ms,
        'source' => 'cli',
    ));
    $PDOX->queryReturnError(
        "INSERT INTO {$CFG->dbprefix}mail_sent
            (context_id, bulk_id, user_to, user_from, subject, body, message_id, json, created_at)
         VALUES (:CID, :BID, :UTO, :UFR, :SUB, NULL, :MID, :JSON, NOW())",
        array(
            ':CID' => $context_id,
            ':BID' => $bulk_id,
            ':UTO' => $user_to,
            ':UFR' => $from_user_id,
            ':SUB' => substr($subject, 0, 256),
            ':MID' => $message_id,
            ':JSON' => $sent_json,
        )
    );
    $attempted++;
    if ( $stopped_rate_limit ) {
        $not_attempted = $audience_total - $attempted;
        echo "Stopping run: SES rate limit exceeded ($not_attempted not attempted).\n";
        break;
    }
}

$meta['sent'] = $sent;
$meta['skipped'] = $skipped;
$meta['failed'] = $failed;
$meta['errors'] = $errors;
$meta['stopped_rate_limit'] = $stopped_rate_limit ? 1 : 0;
$meta['not_attempted'] = $not_attempted;
$run_elapsed_s = microtime(true) - $run_t0;
$calls_per_sec = ($run_elapsed_s > 0.0) ? ($send_calls / $run_elapsed_s) : 0.0;
$meta['elapsed_s'] = round($run_elapsed_s, 3);
$meta['send_calls'] = $send_calls;
$meta['calls_per_sec'] = round($calls_per_sec, 3);
$PDOX->queryReturnError(
    "UPDATE {$CFG->dbprefix}mail_bulk SET json = :JSON WHERE bulk_id = :BID",
    array(':JSON' => json_encode($meta), ':BID' => $bulk_id)
);

echo "Done bulk_id=$bulk_id sent=$sent skipped=$skipped failed=$failed";
if ( $stopped_rate_limit ) {
    echo " stopped_rate_limit=1 not_attempted=$not_attempted";
}
echo sprintf(
    " elapsed=%.3fs send_calls=%d avg=%.2f calls/sec\n",
    $run_elapsed_s,
    $send_calls,
    $calls_per_sec
);
if ( $stopped_rate_limit ) {
    exit(3);
}
exit( $failed > 0 && $sent < 1 ? 2 : 0 );
