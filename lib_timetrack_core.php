<?php
/**
 * TimeTrack Core Library
 * Internal Library for ScriptCase: lib_timetrack_core
 *
 * Paste this code into ScriptCase IDE:
 *   Project > Internal Libraries > New Library > Name: lib_timetrack_core
 */

// Get the current employee_id from the logged-in user
function tt_get_employee_id() {
    $login = isset($_SESSION['sc_session']['login']) ? $_SESSION['sc_session']['login'] : '';
    if (empty($login)) return null;
    sc_lookup(rs, "SELECT employee_id FROM employee_sec_link WHERE sec_login = '{$login}'");
    if (isset({rs}[0][0])) {
        return {rs}[0][0];
    }
    return null;
}

// Get employee's manager_id
function tt_get_manager_id($employee_id) {
    if (!$employee_id) return null;
    sc_lookup(rs, "SELECT manager_id FROM employees WHERE employee_id = {$employee_id}");
    return isset({rs}[0][0]) ? {rs}[0][0] : null;
}

// Get employee's department_id
function tt_get_department_id($employee_id) {
    if (!$employee_id) return null;
    sc_lookup(rs, "SELECT department_id FROM employees WHERE employee_id = {$employee_id}");
    return isset({rs}[0][0]) ? {rs}[0][0] : null;
}

// Check if current user belongs to a security group
function tt_user_in_group($group_name) {
    $login = isset($_SESSION['sc_session']['login']) ? $_SESSION['sc_session']['login'] : '';
    if (empty($login)) return false;
    sc_lookup(rs, "SELECT 1 FROM sec_users_groups ug
                    JOIN sec_groups g ON g.group_id = ug.group_id
                    WHERE ug.login = '{$login}' AND g.description = '{$group_name}'");
    return isset({rs}[0][0]);
}

// Get all direct reports of a manager
function tt_get_direct_reports($manager_id) {
    if (!$manager_id) return [];
    sc_lookup(rs, "SELECT employee_id FROM employees WHERE manager_id = {$manager_id} AND is_active = TRUE");
    $ids = [];
    if (is_array({rs})) {
        foreach ({rs} as $row) { $ids[] = $row[0]; }
    }
    return $ids;
}

// Get all employees in a department
function tt_get_dept_employees($department_id) {
    if (!$department_id) return [];
    sc_lookup(rs, "SELECT employee_id FROM employees WHERE department_id = {$department_id} AND is_active = TRUE");
    $ids = [];
    if (is_array({rs})) {
        foreach ({rs} as $row) { $ids[] = $row[0]; }
    }
    return $ids;
}

// Insert audit log entry
function tt_audit_log($table_name, $record_id, $action, $old_values = null, $new_values = null) {
    $login = isset($_SESSION['sc_session']['login']) ? $_SESSION['sc_session']['login'] : 'system';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $old_json = $old_values ? "'" . pg_escape_string(json_encode($old_values)) . "'::jsonb" : "NULL";
    $new_json = $new_values ? "'" . pg_escape_string(json_encode($new_values)) . "'::jsonb" : "NULL";
    sc_exec_sql("INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by_login, ip_address)
                  VALUES ('{$table_name}', {$record_id}, '{$action}', {$old_json}, {$new_json}, '{$login}', '{$ip}'::inet)");
}

// Send notification
function tt_notify($recipient_employee_id, $type, $title, $message, $ref_table = null, $ref_id = null) {
    if (!$recipient_employee_id) return;
    $ref_table_sql = $ref_table ? "'{$ref_table}'" : "NULL";
    $ref_id_sql = $ref_id ? $ref_id : "NULL";
    $title_safe = str_replace("'", "''", $title);
    $message_safe = str_replace("'", "''", $message);
    sc_exec_sql("INSERT INTO notifications (recipient_employee_id, notification_type, title, message, reference_table, reference_id)
                  VALUES ({$recipient_employee_id}, '{$type}', '{$title_safe}', '{$message_safe}', {$ref_table_sql}, {$ref_id_sql})");
}

// Calculate business days between two dates (excluding weekends and holidays)
function tt_business_days($start_date, $end_date, $dept_id = null) {
    $dept_filter = $dept_id
        ? "AND (applies_to_department_id IS NULL OR applies_to_department_id = {$dept_id})"
        : "AND applies_to_department_id IS NULL";
    sc_lookup(holidays, "SELECT holiday_date::text FROM holidays
                          WHERE holiday_date BETWEEN '{$start_date}' AND '{$end_date}'
                          AND is_active = TRUE {$dept_filter}");
    $holiday_dates = [];
    if (is_array({holidays})) {
        foreach ({holidays} as $h) { $holiday_dates[] = $h[0]; }
    }

    $days = 0;
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    while ($current <= $end) {
        $dow = $current->format('N');
        if ($dow < 6 && !in_array($current->format('Y-m-d'), $holiday_dates)) {
            $days++;
        }
        $current->modify('+1 day');
    }
    return $days;
}

// Apply row-level security WHERE clause
function tt_apply_row_security($employee_field = 'employee_id') {
    $emp_id = tt_get_employee_id();
    if (!$emp_id) return;

    if (tt_user_in_group('Admin') || tt_user_in_group('HR_Manager')) {
        return; // No filter - see all
    } elseif (tt_user_in_group('Dept_Manager')) {
        $dept_id = tt_get_department_id($emp_id);
        $dept_emps = tt_get_dept_employees($dept_id);
        if (!empty($dept_emps)) {
            $in_list = implode(',', $dept_emps);
            sc_where(add) = "{$employee_field} IN ({$in_list})";
        }
    } elseif (tt_user_in_group('Team_Lead')) {
        $reports = tt_get_direct_reports($emp_id);
        $reports[] = $emp_id;
        $in_list = implode(',', $reports);
        sc_where(add) = "{$employee_field} IN ({$in_list})";
    } else {
        // Employee - own data only
        sc_where(add) = "{$employee_field} = {$emp_id}";
    }
}
