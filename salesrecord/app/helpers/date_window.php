<?php
// app/helpers/date_window.php

/**
 * Canonical "this month" window as DATE strings (no time), half-open [from, to)
 * e.g., ['2025-11-01', '2025-12-01']
 */
function month_window(): array {
  $from = date('Y-m-01');
  $to   = date('Y-m-01', strtotime('first day of next month'));
  return [$from, $to];
}

/** Quick YYYY-MM-DD validator */
function _is_ymd($s){
  return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
}

/**
 * Build a window from ?date_from=YYYY-MM-DD & ?date_to=YYYY-MM-DD (exclusive).
 * Falls back to current month if not provided/invalid.
 * Guarantees from < to.
 */
function window_from_query(): array {
  [$defFrom, $defTo] = month_window();

  $from = $_GET['date_from'] ?? $defFrom;
  $to   = $_GET['date_to']   ?? $defTo;

  if (!_is_ymd($from)) $from = $defFrom;
  if (!_is_ymd($to))   $to   = $defTo;

  if (strtotime($from) >= strtotime($to)) {
    // force at least a 1-day window
    $to = date('Y-m-d', strtotime($from.' +1 day'));
  }
  return [$from, $to];
}

/** Friendly label for UI; note $to is exclusive, so show (to-1 day) */
function window_label(string $from, string $to): string {
  $thisMonthFrom = date('Y-m-01');
  $thisMonthTo   = date('Y-m-01', strtotime('first day of next month'));
  if ($from === $thisMonthFrom && $to === $thisMonthTo) {
    return 'This Month';
  }
  $toInc = date('Y-m-d', strtotime($to.' -1 day'));
  return $from.' - '.$toInc;
}

/**
 * BACKWARD COMPAT: your older helper returning DATETIME strings + month name.
 * Now implemented in terms of month_window(), using Asia/Karachi timezone.
 */
function current_month_window(): array {
  [$from, $to] = month_window();
  $tz    = new DateTimeZone('Asia/Karachi');
  $start = new DateTime($from.' 00:00:00', $tz);
  $end   = new DateTime($to.' 00:00:00', $tz);

  return [
    'start' => $start->format('Y-m-d H:i:s'),
    'end'   => $end->format('Y-m-d H:i:s'),
    'label' => date('F', strtotime($from)),
  ];
}



// ---- Annual window helpers ----

/**
 * Returns ['from' => date, 'to' => date, 'year' => int].
 * Accepts ?date_from / ?date_to (exclusive upper bound) or falls back to ?year/current year.
 */
function year_window_from_query(): array {
  $defaultYear = isset($_GET['year']) && preg_match('/^\d{4}$/', $_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
  $defaultFrom = sprintf('%04d-01-01', $defaultYear);
  $defaultTo   = sprintf('%04d-01-01', $defaultYear + 1);

  $from = $_GET['date_from'] ?? $defaultFrom;
  $to   = $_GET['date_to']   ?? $defaultTo;

  if (!_is_ymd($from)) $from = $defaultFrom;
  if (!_is_ymd($to))   $to   = $defaultTo;

  if (strtotime($from) >= strtotime($to)) {
    $to = date('Y-m-d', strtotime($from.' +1 month'));
  }

  return [
    'from' => $from,
    'to'   => $to,
    'year' => (int)date('Y', strtotime($from)),
  ];
}

/** Month key like '2025-07' -> 'Jul 2025' */
function month_short_label(string $ym): string {
  $ts = strtotime($ym . '-01');
  return date('M Y', $ts);
}
