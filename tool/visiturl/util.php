<?php

/**
 * True when $url is a usable http(s) address for this tool.
 */
function visiturl_valid_url($url) {
    if ( ! is_string($url) ) return false;
    $url = trim($url);
    if ( strlen($url) < 1 ) return false;
    if ( ! preg_match('#^https?://#i', $url) ) return false;
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Trimmed string form of a stored or posted URL (empty string if none).
 */
function visiturl_norm($url) {
    if ( ! is_string($url) ) return '';
    return trim($url);
}

/**
 * Estimated length in minutes from link settings (0 if unset or invalid).
 */
function visiturl_minutes() {
    $raw = \Tsugi\Core\Settings::linkGet('minutes', '');
    if ( $raw === false || $raw === null || $raw === '' ) return 0.0;
    if ( ! is_numeric($raw) ) return 0.0;
    $minutes = $raw + 0.0;
    return $minutes > 0 ? $minutes : 0.0;
}

/**
 * True when the instructor set an estimated length (watch-and-mark grading).
 */
function visiturl_timer_mode() {
    return visiturl_minutes() > 0;
}

/**
 * Seconds that must elapse after launch before "I watched this" is accepted.
 */
function visiturl_unlock_seconds($minutes) {
    if ( $minutes <= 0 ) return 0;
    return (int) ceil($minutes * 60.0 / 3.0);
}

/**
 * Short label like "30-minute" for student-facing copy.
 */
function visiturl_duration_label($minutes) {
    $n = (int) round($minutes);
    if ( $n < 1 ) $n = 1;
    if ( $n === 1 ) return __('1-minute');
    return $n.'-'.__('minute');
}

/**
 * Record that the current user opened the configured URL.
 */
function visiturl_record_visit($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return;
    $keys = array(
        'visited_at' => gmdate('c'),
        'url' => $url,
    );
    if ( visiturl_norm($RESULT->getJsonKey('url')) !== visiturl_norm($url) ) {
        $keys['watched_at'] = '';
    }
    $RESULT->setJsonKeys($keys);
}

/**
 * True when this launch already opened the currently configured URL.
 */
function visiturl_has_visit($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return false;
    if ( ! visiturl_valid_url($url) ) return false;
    $visited_url = $RESULT->getJsonKey('url');
    if ( visiturl_norm($visited_url) !== visiturl_norm($url) ) return false;
    return (bool) $RESULT->getJsonKey('visited_at');
}

/**
 * True when they already marked the current URL as watched.
 */
function visiturl_has_watched($url) {
    global $RESULT;
    if ( ! visiturl_has_visit($url) ) return false;
    $watched = $RESULT->getJsonKey('watched_at');
    return is_string($watched) && strlen($watched) > 0;
}

/**
 * Done with this URL: visited (immediate-grade mode) or marked watched (timer mode).
 */
function visiturl_is_done($url) {
    if ( visiturl_timer_mode() ) return visiturl_has_watched($url);
    return visiturl_has_visit($url);
}

/**
 * Start the watch clock for this URL on first launch. Does not change grades.
 */
function visiturl_ensure_watch_started($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return 0;
    $watch_url = $RESULT->getJsonKey('watch_url');
    $started = $RESULT->getJsonKey('watch_started_at');
    if ( visiturl_norm($watch_url) === visiturl_norm($url) && is_numeric($started) && $started > 0 ) {
        return (int) $started;
    }
    $started = time();
    $RESULT->setJsonKeys(array(
        'watch_url' => $url,
        'watch_started_at' => $started,
        'watched_at' => '',
    ));
    return $started;
}

/**
 * Unix time when the watched button may be accepted.
 */
function visiturl_unlock_at($url) {
    $minutes = visiturl_minutes();
    $started = visiturl_ensure_watch_started($url);
    return $started + visiturl_unlock_seconds($minutes);
}

/**
 * True when enough time has passed to accept "I watched this".
 */
function visiturl_watch_unlocked($url) {
    return time() >= visiturl_unlock_at($url);
}

/**
 * Mark the current URL watched. Does not send a grade.
 */
function visiturl_mark_watched($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return;
    $RESULT->setJsonKeys(array(
        'watched_at' => gmdate('c'),
        'url' => $url,
    ));
}

/**
 * Send 100% (with due-date penalty) but never lower an existing grade.
 */
function visiturl_send_grade() {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return;
    $dueDate = \Tsugi\UI\SettingsForm::getDueDate();
    $gradetosend = 1.0;
    if ( $dueDate->penalty > 0 ) {
        $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
    }
    $oldgrade = (float) ($RESULT->grade ?? 0);
    if ( $oldgrade > $gradetosend ) {
        $gradetosend = $oldgrade;
    }
    if ( $gradetosend > $oldgrade ) {
        \Tsugi\Core\LTIX::gradeSend($gradetosend, false);
    }
}
