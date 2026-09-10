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
 * Seconds that must elapse after they visit the URL before "I watched this" is accepted.
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
 * In watch mode, the first visit for this URL starts the wait clock.
 */
function visiturl_record_visit($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return;
    $same_url = visiturl_norm($RESULT->getJsonKey('url')) === visiturl_norm($url);
    $already_visited = $same_url && (bool) $RESULT->getJsonKey('visited_at');
    $keys = array(
        'visited_at' => gmdate('c'),
        'url' => $url,
    );
    if ( ! $same_url ) {
        $keys['watched_at'] = '';
    }
    if ( visiturl_timer_mode() ) {
        $watch_url = $RESULT->getJsonKey('watch_url');
        $started = $RESULT->getJsonKey('watch_started_at');
        $clock_for_url = visiturl_norm($watch_url) === visiturl_norm($url)
            && is_numeric($started) && $started > 0;
        // Keep the first visit's clock; do not restart if they open the URL again.
        if ( ! ($clock_for_url && $already_visited) ) {
            $keys['watch_url'] = $url;
            $keys['watch_started_at'] = time();
            $keys['watched_at'] = '';
        }
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
 * Unix time when the watch clock started for this URL, or 0 if they have not visited yet.
 * Does not start the clock — that happens in visiturl_record_visit().
 */
function visiturl_watch_started_at($url) {
    global $RESULT;
    if ( ! $RESULT || ! $RESULT->id ) return 0;
    if ( ! visiturl_has_visit($url) ) return 0;
    $watch_url = $RESULT->getJsonKey('watch_url');
    $started = $RESULT->getJsonKey('watch_started_at');
    if ( visiturl_norm($watch_url) === visiturl_norm($url) && is_numeric($started) && $started > 0 ) {
        return (int) $started;
    }
    return 0;
}

/**
 * Unix time when the watched button may be accepted, or 0 if the clock has not started.
 */
function visiturl_unlock_at($url) {
    $minutes = visiturl_minutes();
    $started = visiturl_watch_started_at($url);
    if ( $minutes <= 0 || $started <= 0 ) return 0;
    return $started + visiturl_unlock_seconds($minutes);
}

/**
 * True when they have visited and enough time has passed to accept "I watched this".
 */
function visiturl_watch_unlocked($url) {
    $unlock = visiturl_unlock_at($url);
    if ( $unlock <= 0 ) return false;
    return time() >= $unlock;
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
