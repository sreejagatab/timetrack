<?php
/**
 * TimeTrack Timesheet Calculation Library
 * Internal Library for ScriptCase: lib_timesheet_calc
 *
 * Paste this code into ScriptCase IDE:
 *   Project > Internal Libraries > New Library > Name: lib_timesheet_calc
 */

// Recalculate timesheet totals from line items
function tt_recalc_timesheet($timesheet_id) {
    sc_lookup(totals, "SELECT
        COALESCE(SUM(CASE WHEN NOT is_overtime THEN hours ELSE 0 END), 0) AS regular,
        COALESCE(SUM(CASE WHEN is_overtime THEN hours ELSE 0 END), 0) AS overtime
        FROM timesheet_lines WHERE timesheet_id = {$timesheet_id}");

    $regular = isset({totals}[0][0]) ? {totals}[0][0] : 0;
    $overtime = isset({totals}[0][1]) ? {totals}[0][1] : 0;

    sc_exec_sql("UPDATE timesheets SET total_regular_hours = {$regular},
                 total_overtime_hours = {$overtime}, updated_at = NOW()
                 WHERE timesheet_id = {$timesheet_id}");
}

// Auto-populate timesheet lines from clock entries
function tt_populate_from_clock($employee_id, $timesheet_id, $period_start, $period_end) {
    sc_lookup(clocks, "SELECT DATE(clock_in)::text AS work_date,
                              ROUND(SUM(net_minutes)/60.0, 2) AS total_hours
                        FROM clock_entries
                        WHERE employee_id = {$employee_id}
                          AND DATE(clock_in) BETWEEN '{$period_start}' AND '{$period_end}'
                          AND clock_out IS NOT NULL
                        GROUP BY DATE(clock_in)
                        ORDER BY work_date");
    if (is_array({clocks})) {
        foreach ({clocks} as $row) {
            $work_date = $row[0];
            $hours = $row[1];
            sc_exec_sql("INSERT INTO timesheet_lines (timesheet_id, line_date, hours, description)
                          SELECT {$timesheet_id}, '{$work_date}', {$hours}, 'Auto from clock'
                          WHERE NOT EXISTS (
                            SELECT 1 FROM timesheet_lines
                            WHERE timesheet_id = {$timesheet_id}
                            AND line_date = '{$work_date}'
                            AND project_id IS NULL AND task_id IS NULL
                          )");
        }
    }
    tt_recalc_timesheet($timesheet_id);
}

// Check overtime based on rules
function tt_check_overtime($employee_id, $timesheet_id) {
    // Get applicable overtime rule
    sc_lookup(rule, "SELECT daily_threshold_hours, weekly_threshold_hours
                     FROM overtime_rules WHERE is_active = TRUE
                     AND effective_from <= CURRENT_DATE
                     AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)
                     ORDER BY rule_id LIMIT 1");

    if (!isset({rule}[0])) return;

    $daily_threshold = {rule}[0][0];
    $weekly_threshold = {rule}[0][1];

    // Get timesheet period
    sc_lookup(period, "SELECT tp.period_start, tp.period_end
                       FROM timesheets t JOIN timesheet_periods tp ON tp.period_id = t.period_id
                       WHERE t.timesheet_id = {$timesheet_id}");
    if (!isset({period}[0])) return;

    // Weekly overtime check
    if ($weekly_threshold) {
        sc_lookup(weekly, "SELECT COALESCE(SUM(hours), 0)
                           FROM timesheet_lines WHERE timesheet_id = {$timesheet_id}");
        $total_weekly = isset({weekly}[0][0]) ? {weekly}[0][0] : 0;

        if ($total_weekly > $weekly_threshold) {
            // Mark excess hours as overtime (simplified: mark last entries)
            $overtime_hours = $total_weekly - $weekly_threshold;
            sc_exec_sql("UPDATE timesheet_lines SET is_overtime = TRUE
                         WHERE line_id IN (
                           SELECT line_id FROM timesheet_lines
                           WHERE timesheet_id = {$timesheet_id} AND NOT is_overtime
                           ORDER BY line_date DESC, line_id DESC
                         )");
        }
    }

    tt_recalc_timesheet($timesheet_id);
}
