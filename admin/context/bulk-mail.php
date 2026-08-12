<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("mail_audience.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Services\Mail\MailService;

LTIX::getConnection();
header('Content-Type: text/html; charset=utf-8');
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    U::flashError("Bulk mail is limited to site administrators");
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

const MAIL_BULK_DEFAULT_LIMIT = 200;
const MAIL_BULK_MAX_RECIPIENTS = 200;

/**
 * Shell-escape a value for single-quoted CLI args.
 */
function bulk_mail_cli_shell_quote($value) {
    return "'".str_replace("'", "'\\''", (string) $value)."'";
}

/**
 * Build equivalent scripts/send-bulk-mail.php instructions for the current form values.
 * Includes --rate=5 as a batch-mode tip (UI send does not pace).
 *
 * @return string Multi-line text for a copyable textarea
 */
function bulk_mail_cli_instructions($context_id, $from_user_id, $subject, $body, $single_email,
        $days, $exclude_recent_bulk_days, $limit, $include_opted_out, $premium_only) {
    $lines = array();
    $lines[] = '# Equivalent CLI (scripts/send-bulk-mail.php)';
    $lines[] = '# Dry-run by default. Add --send to deliver.';
    $lines[] = '# Run from the Tsugi root (directory that contains scripts/).';
    $lines[] = '# Batch pacing (--rate) is CLI-only; admin UI caps at '.MAIL_BULK_MAX_RECIPIENTS.' recipients.';
    $lines[] = '#';
    $lines[] = '# 1) Save the body to a file, e.g. campaign.txt';
    $lines[] = '# 2) Dry-run:';

    $cmd = array('php scripts/send-bulk-mail.php');
    $cmd[] = '--context-id='.(int) $context_id;
    if ( trim((string) $subject) !== '' ) {
        $cmd[] = '--subject='.bulk_mail_cli_shell_quote($subject);
    } else {
        $cmd[] = "--subject='YOUR SUBJECT'";
    }
    $cmd[] = '--body-file=./campaign.txt';
    $cmd[] = '--rate=5';

    $single_email = strtolower(trim((string) $single_email));
    if ( $single_email !== '' ) {
        $cmd[] = '--email='.bulk_mail_cli_shell_quote($single_email);
    } else {
        $cmd[] = '--days='.(int) $days;
        $cmd[] = '--exclude-recent-bulk-days='.(int) $exclude_recent_bulk_days;
        $cmd[] = '--limit='.(int) $limit;
        if ( $premium_only ) {
            $cmd[] = '--premium-only';
        }
        if ( $include_opted_out ) {
            $cmd[] = '--include-opted-out';
        }
    }

    $lines[] = implode(" \\\n  ", $cmd);

    $from_user_id = (int) $from_user_id;
    $lines[] = '#';
    $lines[] = '# 3) Send (requires --from-user-id):';
    $send_cmd = $cmd;
    if ( $from_user_id > 0 ) {
        $send_cmd[] = '--from-user-id='.$from_user_id;
    } else {
        $send_cmd[] = '--from-user-id=USER_ID';
    }
    $send_cmd[] = '--send';
    $lines[] = implode(" \\\n  ", $send_cmd);

    $body = (string) $body;
    if ( trim($body) !== '' ) {
        $lines[] = '#';
        $lines[] = '# --- campaign.txt body (copy into the file) ---';
        $lines[] = $body;
        $lines[] = '# --- end body ---';
    }

    $lines[] = '#';
    $lines[] = '# See scripts/README-send-bulk-mail.md';
    return implode("\n", $lines);
}

if ( ! isset($_REQUEST['context_id']) || ! is_numeric($_REQUEST['context_id']) ) {
    U::flashError("No context_id provided");
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}
$context_id = $_REQUEST['context_id'] + 0;

$context_row = $PDOX->rowDie(
    "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
    array(':CID' => $context_id)
);
if ( $context_row === false || $context_row === null ) {
    U::flashError("Context not found");
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}
$context_title = $context_row['title'] ? $context_row['title'] : "Context #$context_id";

$from_user_id = loggedInUserId();
$step = U::get($_REQUEST, 'step', 'compose');

// ---------- Send (confirm POST) ----------
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && U::get($_POST, 'step') === 'send' ) {
    if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) {
        U::flashError('Mail disabled: set $CFG->maildomain');
        header('Location: bulk-mail.php?context_id='.$context_id);
        return;
    }
    if ( $from_user_id <= 0 ) {
        U::flashError('Bulk mail requires a logged-in user id (lti_user) for mail_bulk.user_id');
        header('Location: bulk-mail.php?context_id='.$context_id);
        return;
    }
    if ( ! U::get($_POST, 'confirm_send') ) {
        U::flashError('You must confirm the send');
        header('Location: bulk-mail.php?context_id='.$context_id);
        return;
    }

    $subject = trim((string) U::get($_POST, 'subject', ''));
    $body = (string) U::get($_POST, 'body', '');
    $single_email = strtolower(trim((string) U::get($_POST, 'single_email', '')));
    $days = (int) U::get($_POST, 'days', 0);
    $include_opted_out = U::get($_POST, 'include_opted_out') == '1';
    $premium_only = U::get($_POST, 'premium_only') == '1';
    $exclude_recent_bulk_days = (int) U::get($_POST, 'exclude_recent_bulk_days', 0);
    $limit = (int) U::get($_POST, 'limit', MAIL_BULK_DEFAULT_LIMIT);

    if ( $subject === '' || trim($body) === '' ) {
        U::flashError('Subject and body are required');
        header('Location: bulk-mail.php?context_id='.$context_id);
        return;
    }

    if ( $single_email !== '' ) {
        if ( strpos($single_email, '@') === false ) {
            U::flashError('Single email address looks invalid');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        $rows = mail_context_audience_by_email($context_id, $single_email);
        if ( count($rows) < 1 ) {
            U::flashError('No context member with email '.$single_email);
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        $meta = array(
            'single_email' => $single_email,
            'audience_count' => 1,
        );
    } else {
        if ( $days < 1 || $days > 365 ) {
            U::flashError('Days must be between 1 and 365');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        if ( $exclude_recent_bulk_days < 0 || $exclude_recent_bulk_days > 365 ) {
            U::flashError('Exclude recent bulk days must be between 0 and 365');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        if ( $limit < 0 || $limit > MAIL_BULK_MAX_RECIPIENTS ) {
            U::flashError('Limit must be between 0 and '.MAIL_BULK_MAX_RECIPIENTS.' (0 = no limit, still capped at '.MAIL_BULK_MAX_RECIPIENTS.' for admin UI)');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }

        $rows = mail_context_audience($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days, $limit);
        $count = count($rows);
        if ( $count < 1 ) {
            U::flashError('No recipients match the filters');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        if ( $count > MAIL_BULK_MAX_RECIPIENTS ) {
            U::flashError('Audience is '.$count.' recipients; admin UI max is '.MAIL_BULK_MAX_RECIPIENTS.'. Use scripts/send-bulk-mail.php for larger batches.');
            header('Location: bulk-mail.php?context_id='.$context_id.'&step=preview');
            return;
        }
        $meta = array(
            'days' => $days,
            'include_opted_out' => $include_opted_out ? 1 : 0,
            'premium_only' => $premium_only ? 1 : 0,
            'exclude_recent_bulk_days' => $exclude_recent_bulk_days,
            'limit' => $limit,
            'audience_count' => $count,
        );
    }
    $count = count($rows);

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
    // Admin UI: no local pacing (batch CLI owns --rate). Still stop on SES throttle.
    MailService::setBulkPacePerSecond(0);

    foreach ( $rows as $row ) {
        $to = trim((string) U::get($row, 'email', ''));
        $user_to = (int) U::get($row, 'user_id', 0);
        if ( $to === '' || $user_to < 1 ) {
            $skipped++;
            $attempted++;
            continue;
        }
        $token = MailService::computeCheck($user_to);
        $detail = MailService::sendDetailedBulk($to, $subject, $body, $user_to, $token);
        $send_calls++;
        $status = 'failed';
        if ( U::get($detail, 'suppressed') ) {
            $skipped++;
            $status = 'suppressed';
        } else if ( U::get($detail, 'success') ) {
            $sent++;
            $status = 'sent';
        } else if ( U::get($detail, 'disabled') ) {
            $failed++;
            $status = 'disabled';
            if ( count($errors) < 5 ) {
                $errors[] = $to.': mail disabled';
            }
        } else {
            $failed++;
            $err = (string) U::get($detail, 'error', 'unknown');
            if ( count($errors) < 5 ) {
                $errors[] = $to.': '.$err;
            }
            if ( MailService::isRateLimited($detail) ) {
                $status = 'rate_limited';
                $stopped_rate_limit = true;
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

    $summary = sprintf(
        'Bulk mail #%s: sent=%d skipped=%d failed=%d elapsed=%.3fs avg=%.2f calls/sec',
        $bulk_id,
        $sent,
        $skipped,
        $failed,
        $run_elapsed_s,
        $calls_per_sec
    );
    if ( $stopped_rate_limit ) {
        $summary .= "; stopped on SES rate limit ($not_attempted not attempted — re-run later)";
        U::flashError($summary);
    } else {
        U::flashSuccess($summary);
    }
    header('Location: bulk-detail.php?bulk_id='.$bulk_id);
    return;
}

// ---------- Preview (compose POST → preview GET via PRG, or preview POST shows confirm) ----------
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && U::get($_POST, 'step') === 'compose' ) {
    $subject = trim((string) U::get($_POST, 'subject', ''));
    $body = (string) U::get($_POST, 'body', '');
    $single_email = strtolower(trim((string) U::get($_POST, 'single_email', '')));
    $days = (int) U::get($_POST, 'days', 30);
    $include_opted_out = isset($_POST['include_opted_out']) ? 1 : 0;
    $premium_only = isset($_POST['premium_only']) ? 1 : 0;
    $exclude_recent_bulk_days = (int) U::get($_POST, 'exclude_recent_bulk_days', 30);
    $limit = (int) U::get($_POST, 'limit', MAIL_BULK_DEFAULT_LIMIT);
    if ( $subject === '' || trim($body) === '' ) {
        U::flashError('Subject and body are required');
        header('Location: bulk-mail.php?context_id='.$context_id);
        return;
    }
    if ( $single_email !== '' ) {
        if ( strpos($single_email, '@') === false ) {
            U::flashError('Single email address looks invalid');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
    } else {
        if ( $days < 1 || $days > 365 ) {
            U::flashError('Days must be between 1 and 365');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        if ( $exclude_recent_bulk_days < 0 || $exclude_recent_bulk_days > 365 ) {
            U::flashError('Exclude recent bulk days must be between 0 and 365 (0 = do not exclude)');
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
        if ( $limit < 0 || $limit > MAIL_BULK_MAX_RECIPIENTS ) {
            U::flashError('Limit must be between 0 and '.MAIL_BULK_MAX_RECIPIENTS);
            header('Location: bulk-mail.php?context_id='.$context_id);
            return;
        }
    }
    // Stash body in session (too large for URL)
    $_SESSION['bulk_mail_draft'] = array(
        'context_id' => $context_id,
        'subject' => $subject,
        'body' => $body,
        'single_email' => $single_email,
        'days' => $days,
        'include_opted_out' => $include_opted_out,
        'premium_only' => $premium_only,
        'exclude_recent_bulk_days' => $exclude_recent_bulk_days,
        'limit' => $limit,
    );
    header('Location: bulk-mail.php?context_id='.$context_id.'&step=preview');
    return;
}

$draft = U::get($_SESSION, 'bulk_mail_draft', array());
if ( !is_array($draft) || (int) U::get($draft, 'context_id', 0) !== $context_id ) {
    $draft = array();
}

$subject = '';
$body = '';
$single_email = '';
$days = 30;
$include_opted_out = false;
$premium_only = false;
$exclude_recent_bulk_days = 30;
$limit = MAIL_BULK_DEFAULT_LIMIT;
$rows = array();
$audience_stats = false;

if ( $step === 'preview' && count($draft) > 0 ) {
    $subject = (string) U::get($draft, 'subject', '');
    $body = (string) U::get($draft, 'body', '');
    $single_email = strtolower(trim((string) U::get($draft, 'single_email', '')));
    $days = (int) U::get($draft, 'days', 30);
    $include_opted_out = (int) U::get($draft, 'include_opted_out', 0) === 1;
    $premium_only = (int) U::get($draft, 'premium_only', 0) === 1;
    $exclude_recent_bulk_days = (int) U::get($draft, 'exclude_recent_bulk_days', 30);
    $limit = (int) U::get($draft, 'limit', MAIL_BULK_DEFAULT_LIMIT);
    if ( $single_email !== '' ) {
        $rows = mail_context_audience_by_email($context_id, $single_email);
        $n1 = count($rows);
        $audience_stats = array(
            'matched_login' => $n1,
            'excluded_recent_bulk' => 0,
            'eligible_no_limit' => $n1,
            'limit' => 1,
            'will_send' => $n1,
            'exclude_recent_bulk_days' => 0,
            'single_email' => $single_email,
        );
    } else {
        $audience_stats = mail_context_audience_stats($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days, $limit);
        $rows = mail_context_audience($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days, $limit);
    }
} else {
    $step = 'compose';
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$mail_ok = isset($CFG->maildomain) && $CFG->maildomain !== false;
?>
<h2>Bulk mail: <?= htmlentities((string) $context_title) ?></h2>
<p>
  <a href="membership?context_id=<?= (int) $context_id ?>" class="btn btn-default">Membership</a>
  <a href="mailing-list.php?context_id=<?= (int) $context_id ?>" class="btn btn-default">Mailing list</a>
  <a href="<?= htmlentities($CFG->wwwroot) ?>/admin/mail/bulk">All bulk campaigns</a>
</p>
<p>
Transport: <strong><?= htmlentities(MailService::transport()) ?></strong>
<?= $mail_ok ? '' : ' — <span style="color:red">$CFG->maildomain not set; send disabled</span>' ?>
 · Max recipients per send: <?= (int) MAIL_BULK_MAX_RECIPIENTS ?> (use CLI for larger / paced batches)
</p>

<?php if ( $step === 'compose' ) { ?>
<div class="panel panel-default">
  <div class="panel-heading"><h3 class="panel-title">Compose</h3></div>
  <div class="panel-body">
    <form method="post" action="bulk-mail.php">
      <input type="hidden" name="context_id" value="<?= (int) $context_id ?>">
      <input type="hidden" name="step" value="compose">
      <div class="form-group">
        <label for="subject">Subject</label>
        <input class="form-control" type="text" name="subject" id="subject" required
               value="<?= htmlentities((string) U::get($draft, 'subject', '')) ?>">
      </div>
      <div class="form-group">
        <label for="body">Body (plain text)</label>
        <textarea class="form-control" name="body" id="body" rows="12" required><?= htmlentities((string) U::get($draft, 'body', '')) ?></textarea>
      </div>
      <div class="form-group">
        <label for="single_email">Send to one context member (optional)</label>
        <input class="form-control" type="email" name="single_email" id="single_email"
               value="<?= htmlentities((string) U::get($draft, 'single_email', '')) ?>"
               placeholder="exact@email.example" style="max-width:360px;">
        <p class="help-block">
          If set, ignores the audience filters below and sends bulk mail to that
          single member of this context (list of one). Useful for testing unsubscribe headers.
        </p>
      </div>
      <div class="form-group">
        <label for="days">Users who logged in within the last</label>
        <input type="number" class="form-control" id="days" name="days" min="1" max="365"
               value="<?= (int) U::get($draft, 'days', 30) ?>" style="width:80px;display:inline-block;">
        days
      </div>
      <div class="form-group">
        <label for="exclude_recent_bulk_days">Exclude users who already got bulk mail in this context within the last</label>
        <input type="number" class="form-control" id="exclude_recent_bulk_days" name="exclude_recent_bulk_days"
               min="0" max="365"
               value="<?= (int) U::get($draft, 'exclude_recent_bulk_days', 30) ?>" style="width:80px;display:inline-block;">
        days
        <p class="help-block">
          Scoped to this context only. Use <strong>0</strong> to disable.
          Example: mail users active in the last 15 days, then later mail users active in 30 days
          with exclude=30 so prior recipients are skipped.
        </p>
      </div>
      <div class="form-group">
        <label for="limit">Limit to most recently logged-in</label>
        <input type="number" class="form-control" id="limit" name="limit"
               min="0" max="<?= (int) MAIL_BULK_MAX_RECIPIENTS ?>"
               value="<?= (int) U::get($draft, 'limit', MAIL_BULK_DEFAULT_LIMIT) ?>" style="width:80px;display:inline-block;">
        users
        <p class="help-block">
          Defaults to <strong><?= (int) MAIL_BULK_DEFAULT_LIMIT ?></strong> most recent logins who match the filters
          (and have not already received bulk mail within the exclude window).
          Admin UI max is <strong><?= (int) MAIL_BULK_MAX_RECIPIENTS ?></strong>; use
          <code>scripts/send-bulk-mail.php</code> for larger paced batches.
        </p>
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" name="include_opted_out" value="1"
            <?= (int) U::get($draft, 'include_opted_out', 0) === 1 ? 'checked' : '' ?>>
          Include opted-out users in the audience list
        </label>
        <p class="help-block">Even if checked, MailService still skips suppressed addresses and subscribe=-1 at send time.</p>
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" name="premium_only" value="1"
            <?= (int) U::get($draft, 'premium_only', 0) === 1 ? 'checked' : '' ?>>
          Supporters / premium users only
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Preview recipients</button>
    </form>
<?php
$cli_compose = bulk_mail_cli_instructions(
    $context_id,
    $from_user_id,
    (string) U::get($draft, 'subject', ''),
    (string) U::get($draft, 'body', ''),
    (string) U::get($draft, 'single_email', ''),
    (int) U::get($draft, 'days', 30),
    (int) U::get($draft, 'exclude_recent_bulk_days', 30),
    (int) U::get($draft, 'limit', MAIL_BULK_DEFAULT_LIMIT),
    (int) U::get($draft, 'include_opted_out', 0) === 1,
    (int) U::get($draft, 'premium_only', 0) === 1
);
?>
    <details style="margin-top:20px;">
      <summary style="cursor:pointer;color:#666;">CLI equivalent (send-bulk-mail.php)</summary>
      <p class="help-block" style="margin-top:8px;">
        Hidden helper for cron / larger batches. Updates as you edit the form.
        See <code>scripts/README-send-bulk-mail.md</code>.
      </p>
      <textarea id="bulk-mail-cli" class="form-control" rows="16" readonly
                style="font-family:Menlo,Monaco,Consolas,monospace;font-size:12px;"><?= htmlentities($cli_compose) ?></textarea>
    </details>
    <script>
    (function () {
      var form = document.querySelector('form[action="bulk-mail.php"]');
      var out = document.getElementById('bulk-mail-cli');
      if (!form || !out) return;
      var contextId = <?= (int) $context_id ?>;
      var fromUserId = <?= (int) $from_user_id ?>;
      function q(s) {
        return "'" + String(s).replace(/'/g, "'\\''") + "'";
      }
      function rebuild() {
        var subject = (form.subject && form.subject.value) || '';
        var body = (form.body && form.body.value) || '';
        var email = ((form.single_email && form.single_email.value) || '').trim().toLowerCase();
        var days = parseInt(form.days && form.days.value, 10) || 30;
        var exclude = parseInt(form.exclude_recent_bulk_days && form.exclude_recent_bulk_days.value, 10);
        if (isNaN(exclude)) exclude = 30;
        var limit = parseInt(form.limit && form.limit.value, 10);
        if (isNaN(limit)) limit = <?= (int) MAIL_BULK_DEFAULT_LIMIT ?>;
        var opted = form.include_opted_out && form.include_opted_out.checked;
        var premium = form.premium_only && form.premium_only.checked;
        var lines = [];
        lines.push('# Equivalent CLI (scripts/send-bulk-mail.php)');
        lines.push('# Dry-run by default. Add --send to deliver.');
        lines.push('# Run from the Tsugi root (directory that contains scripts/).');
        lines.push('# Batch pacing (--rate) is CLI-only; admin UI caps at <?= (int) MAIL_BULK_MAX_RECIPIENTS ?> recipients.');
        lines.push('#');
        lines.push('# 1) Save the body to a file, e.g. campaign.txt');
        lines.push('# 2) Dry-run:');
        var cmd = ['php scripts/send-bulk-mail.php',
          '--context-id=' + contextId,
          '--subject=' + (subject ? q(subject) : "'YOUR SUBJECT'"),
          '--body-file=./campaign.txt',
          '--rate=5'];
        if (email) {
          cmd.push('--email=' + q(email));
        } else {
          cmd.push('--days=' + days);
          cmd.push('--exclude-recent-bulk-days=' + exclude);
          cmd.push('--limit=' + limit);
          if (premium) cmd.push('--premium-only');
          if (opted) cmd.push('--include-opted-out');
        }
        lines.push(cmd.join(' \\\n  '));
        lines.push('#');
        lines.push('# 3) Send (requires --from-user-id):');
        var send = cmd.slice();
        send.push('--from-user-id=' + (fromUserId > 0 ? fromUserId : 'USER_ID'));
        send.push('--send');
        lines.push(send.join(' \\\n  '));
        if (body.trim() !== '') {
          lines.push('#');
          lines.push('# --- campaign.txt body (copy into the file) ---');
          lines.push(body);
          lines.push('# --- end body ---');
        }
        lines.push('#');
        lines.push('# See scripts/README-send-bulk-mail.md');
        out.value = lines.join('\n');
      }
      form.addEventListener('input', rebuild);
      form.addEventListener('change', rebuild);
    })();
    </script>
  </div>
</div>
<?php } ?>

<?php if ( $step === 'preview' ) {
    $n = count($rows);
    $over = $n > MAIL_BULK_MAX_RECIPIENTS;
?>
<div class="panel panel-default">
  <div class="panel-heading"><h3 class="panel-title">Preview</h3></div>
  <div class="panel-body">
    <p><strong>Subject:</strong> <?= htmlentities($subject) ?></p>
    <p><strong>Filters:</strong>
      <?php if ( $single_email !== '' ) { ?>
        single email <?= htmlentities($single_email) ?>
      <?php } else { ?>
        logged in last <?= (int) $days ?> days
        <?= $exclude_recent_bulk_days > 0
              ? '; exclude if bulk mail already sent in this context within '.$exclude_recent_bulk_days.' days'
              : '; not excluding prior bulk recipients' ?>
        <?= $limit > 0 ? '; most recent '.$limit.' by login' : '; no count limit' ?>
        <?= $include_opted_out ? '; include opted-out in list' : '; exclude opted-out' ?>
        <?= $premium_only ? '; premium only' : '' ?>
      <?php } ?>
    </p>
    <p><strong>Recipients (this send):</strong> <?= (int) $n ?>
      <?= $over ? ' <span style="color:red">(over max '.MAIL_BULK_MAX_RECIPIENTS.' — use CLI for larger batches)</span>' : '' ?>
    </p>
    <?php if ( is_array($audience_stats) && empty($audience_stats['single_email']) ) { ?>
    <div class="well well-sm" style="max-width:640px;">
      <strong>Audience breakdown</strong>
      <ul style="margin-bottom:0;">
        <li>Logged in within last <?= (int) $days ?> days
          <?= $include_opted_out ? '' : ' (excluding opted-out)' ?>
          <?= $premium_only ? ', premium only' : '' ?>:
          <strong><?= (int) $audience_stats['matched_login'] ?></strong></li>
        <li>Excluded — already got successful bulk in this context within
          <?= (int) $audience_stats['exclude_recent_bulk_days'] ?> days:
          <strong><?= (int) $audience_stats['excluded_recent_bulk'] ?></strong>
          <?= (int) $audience_stats['exclude_recent_bulk_days'] < 1 ? ' <span class="text-muted">(exclude disabled)</span>' : '' ?>
        </li>
        <li>Eligible with no limit:
          <strong><?= (int) $audience_stats['eligible_no_limit'] ?></strong></li>
        <li>After limit<?= $limit > 0 ? ' ('.$limit.')' : ' (none)' ?>:
          <strong><?= (int) $audience_stats['will_send'] ?></strong>
          <?= $limit > 0 && (int) $audience_stats['eligible_no_limit'] > $limit
                ? ' <span class="text-muted">('.((int) $audience_stats['eligible_no_limit'] - $limit).' not included due to limit)</span>'
                : '' ?>
        </li>
      </ul>
    </div>
    <?php } ?>
    <pre style="white-space:pre-wrap;max-height:200px;overflow:auto;border:1px solid #ddd;padding:10px;"><?= htmlentities($body) ?></pre>
    <?php if ( $n > 0 && $n <= 50 ) { ?>
    <p>Sample emails:</p>
    <ul>
      <?php foreach ( $rows as $row ) { ?>
        <li><?= htmlentities((string) $row['email']) ?></li>
      <?php } ?>
    </ul>
    <?php } else if ( $n > 50 ) { ?>
    <p>First 20 emails:</p>
    <ul>
      <?php
      $i = 0;
      foreach ( $rows as $row ) {
          if ( $i++ >= 20 ) break;
          echo '<li>'.htmlentities((string) $row['email']).'</li>';
      }
      ?>
    </ul>
    <?php } ?>

    <p>
      <a class="btn btn-default" href="bulk-mail.php?context_id=<?= (int) $context_id ?>">Back to compose</a>
    </p>

<?php
$cli_preview = bulk_mail_cli_instructions(
    $context_id,
    $from_user_id,
    $subject,
    $body,
    $single_email,
    $days,
    $exclude_recent_bulk_days,
    $limit,
    $include_opted_out,
    $premium_only
);
?>
    <details style="margin-top:15px;">
      <summary style="cursor:pointer;color:#666;">CLI equivalent (send-bulk-mail.php)</summary>
      <p class="help-block" style="margin-top:8px;">
        Same audience filters as this preview. Prefer CLI for large / batched sends.
      </p>
      <textarea class="form-control" rows="16" readonly
                style="font-family:Menlo,Monaco,Consolas,monospace;font-size:12px;"><?= htmlentities($cli_preview) ?></textarea>
    </details>

    <?php if ( $mail_ok && !$over && $n > 0 ) { ?>
    <form method="post" action="bulk-mail.php" style="margin-top:15px;">
      <input type="hidden" name="context_id" value="<?= (int) $context_id ?>">
      <input type="hidden" name="step" value="send">
      <input type="hidden" name="subject" value="<?= htmlentities($subject) ?>">
      <input type="hidden" name="body" value="<?= htmlentities($body) ?>">
      <input type="hidden" name="single_email" value="<?= htmlentities($single_email) ?>">
      <input type="hidden" name="days" value="<?= (int) $days ?>">
      <input type="hidden" name="exclude_recent_bulk_days" value="<?= (int) $exclude_recent_bulk_days ?>">
      <input type="hidden" name="limit" value="<?= (int) $limit ?>">
      <input type="hidden" name="include_opted_out" value="<?= $include_opted_out ? '1' : '0' ?>">
      <input type="hidden" name="premium_only" value="<?= $premium_only ? '1' : '0' ?>">
      <div class="checkbox">
        <label>
          <input type="checkbox" name="confirm_send" value="1" required>
          I confirm sending this bulk mail to <?= (int) $n ?> recipients via <?= htmlentities(MailService::transport()) ?>
        </label>
      </div>
      <button type="submit" class="btn btn-danger">Send bulk mail</button>
    </form>
    <?php } ?>
  </div>
</div>
<?php } ?>

<?php
$OUTPUT->footer();
