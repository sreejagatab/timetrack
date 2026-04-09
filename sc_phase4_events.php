<?php
/**
 * Phase 4: Inject business logic events into ScriptCase applications
 * Adds onScriptInit (row security), onBeforeInsert, custom button events
 */

$DB_PATH = 'C:/Program Files/NetMake/v9-php82/wwwroot/scriptcase/devel/conf/scriptcase/nm_scriptcase.db';
$PROJECT = 'project';

$db = new SQLite3($DB_PATH);
$db->busyTimeout(10000);

function addEvent($db, $project, $app, $event_name, $event_type, $code) {
    // Check if event already exists
    $stmt = $db->prepare("SELECT COUNT(*) c FROM sc_tbevt WHERE Cod_Prj=:prj AND Cod_Apl=:app AND Nome=:nome");
    $stmt->bindValue(':prj', $project);
    $stmt->bindValue(':app', $app);
    $stmt->bindValue(':nome', $event_name);
    $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($r['c'] > 0) {
        // Update existing
        $stmt = $db->prepare("UPDATE sc_tbevt SET Codigo=:code, Tipo=:tipo WHERE Cod_Prj=:prj AND Cod_Apl=:app AND Nome=:nome");
    } else {
        // Insert new
        $stmt = $db->prepare("INSERT INTO sc_tbevt (Cod_Prj, Versao, Cod_Apl, Nome, Tipo, Parms, Codigo)
                              VALUES (:prj, 1, :app, :nome, :tipo, '', :code)");
    }
    $stmt->bindValue(':prj', $project);
    $stmt->bindValue(':app', $app);
    $stmt->bindValue(':nome', $event_name);
    $stmt->bindValue(':tipo', $event_type);
    $stmt->bindValue(':code', $code);
    $stmt->execute();

    return $db->changes() > 0;
}

echo "=== Phase 4: Injecting Business Logic Events ===" . PHP_EOL . PHP_EOL;

// ============================================================
// 1. Row-Level Security on all grids with employee data
// ============================================================
echo "--- Row-Level Security (onScriptInit) ---" . PHP_EOL;

$security_grids = [
    ['grid_public_clock_entries', 'employee_id'],
    ['grid_public_timesheets', 'employee_id'],
    ['grid_public_timesheet_lines', 'employee_id'],
    ['grid_public_leave_requests', 'employee_id'],
    ['grid_public_leave_balances', 'employee_id'],
    ['grid_public_notifications', 'recipient_employee_id'],
];

$security_code = <<<'PHP'
// Row-Level Security
$emp_id = tt_get_employee_id();
if (!$emp_id) { sc_redir("app_Login"); sc_exit(); }

if (tt_user_in_group('Admin') || tt_user_in_group('HR_Manager')) {
    // No filter - see all
} elseif (tt_user_in_group('Dept_Manager')) {
    $dept_id = tt_get_department_id($emp_id);
    $dept_emps = tt_get_dept_employees($dept_id);
    if (!empty($dept_emps)) {
        $in_list = implode(',', $dept_emps);
        sc_where(add) = "FIELD_NAME IN ({$in_list})";
    }
} elseif (tt_user_in_group('Team_Lead')) {
    $reports = tt_get_direct_reports($emp_id);
    $reports[] = $emp_id;
    $in_list = implode(',', $reports);
    sc_where(add) = "FIELD_NAME IN ({$in_list})";
} else {
    sc_where(add) = "FIELD_NAME = {$emp_id}";
}
PHP;

foreach ($security_grids as $sg) {
    $code = str_replace('FIELD_NAME', $sg[1], $security_code);
    if (addEvent($db, $PROJECT, $sg[0], 'onScriptInit', 'E', $code)) {
        echo "  OK: {$sg[0]} onScriptInit" . PHP_EOL;
    }
}

// ============================================================
// 2. Form: Timesheets - onBeforeInsert (auto-set employee + status)
// ============================================================
echo PHP_EOL . "--- Timesheet Events ---" . PHP_EOL;

$ts_before_insert = <<<'PHP'
// Auto-set employee and initial status
$emp_id = tt_get_employee_id();
{employee_id} = $emp_id;
{status} = 'draft';
PHP;

addEvent($db, $PROJECT, 'form_public_timesheets', 'onBeforeInsert', 'E', $ts_before_insert);
echo "  OK: timesheets onBeforeInsert" . PHP_EOL;

// Timesheet - onAfterInsert (audit log)
$ts_after_insert = <<<'PHP'
tt_audit_log('timesheets', {timesheet_id}, 'INSERT', null, ['status' => 'draft', 'employee_id' => {employee_id}]);
PHP;
addEvent($db, $PROJECT, 'form_public_timesheets', 'onAfterInsert', 'E', $ts_after_insert);
echo "  OK: timesheets onAfterInsert" . PHP_EOL;

// Timesheet - onLoad (row security)
$ts_onload = <<<'PHP'
$emp_id = tt_get_employee_id();
if (tt_user_in_group('Admin') || tt_user_in_group('HR_Manager')) {
    // No filter
} elseif (tt_user_in_group('Dept_Manager')) {
    $dept_id = tt_get_department_id($emp_id);
    $dept_emps = tt_get_dept_employees($dept_id);
    if (!empty($dept_emps)) {
        $in_list = implode(',', $dept_emps);
        sc_where(add) = "employee_id IN ({$in_list})";
    }
} elseif (tt_user_in_group('Team_Lead')) {
    $reports = tt_get_direct_reports($emp_id);
    $reports[] = $emp_id;
    $in_list = implode(',', $reports);
    sc_where(add) = "employee_id IN ({$in_list})";
} else {
    sc_where(add) = "employee_id = {$emp_id}";
}
PHP;
addEvent($db, $PROJECT, 'form_public_timesheets', 'onLoad', 'E', $ts_onload);
echo "  OK: timesheets onLoad" . PHP_EOL;

// ============================================================
// 3. Form: Leave Requests
// ============================================================
echo PHP_EOL . "--- Leave Request Events ---" . PHP_EOL;

$lr_before_insert = <<<'PHP'
// Auto-set employee and status
$emp_id = tt_get_employee_id();
{employee_id} = $emp_id;
{status} = 'draft';

// Calculate total days
$dept_id = tt_get_department_id($emp_id);
$total = tt_business_days({start_date}, {end_date}, $dept_id);
if ({start_half_day} == 't' || {start_half_day} == true) $total -= 0.5;
if ({end_half_day} == 't' || {end_half_day} == true) $total -= 0.5;
{total_days} = $total;

// Check balance
$year = date('Y', strtotime({start_date}));
sc_lookup(bal, "SELECT available_days FROM leave_balances WHERE employee_id = {$emp_id} AND leave_type_id = {leave_type_id} AND year = {$year}");
if (isset({bal}[0][0]) && {bal}[0][0] < {total_days}) {
    sc_error_message("Insufficient leave balance. Available: " . {bal}[0][0] . " days, Requested: {total_days} days.");
    sc_exit();
}

// Check overlap
sc_lookup(overlap, "SELECT 1 FROM leave_requests WHERE employee_id = {$emp_id} AND status IN ('submitted','approved') AND start_date <= '{end_date}' AND end_date >= '{start_date}'");
if (isset({overlap}[0][0])) {
    sc_error_message("You already have a leave request overlapping these dates.");
    sc_exit();
}
PHP;

addEvent($db, $PROJECT, 'form_public_leave_requests', 'onBeforeInsert', 'E', $lr_before_insert);
echo "  OK: leave_requests onBeforeInsert" . PHP_EOL;

$lr_after_insert = <<<'PHP'
tt_audit_log('leave_requests', {request_id}, 'INSERT', null, ['status' => 'draft', 'employee_id' => {employee_id}, 'leave_type_id' => {leave_type_id}]);
PHP;
addEvent($db, $PROJECT, 'form_public_leave_requests', 'onAfterInsert', 'E', $lr_after_insert);
echo "  OK: leave_requests onAfterInsert" . PHP_EOL;

// Leave request onLoad
$lr_onload = <<<'PHP'
$emp_id = tt_get_employee_id();
if (tt_user_in_group('Admin') || tt_user_in_group('HR_Manager')) {
    // See all
} elseif (tt_user_in_group('Dept_Manager')) {
    $dept_id = tt_get_department_id($emp_id);
    $dept_emps = tt_get_dept_employees($dept_id);
    if (!empty($dept_emps)) {
        $in_list = implode(',', $dept_emps);
        sc_where(add) = "employee_id IN ({$in_list})";
    }
} else {
    sc_where(add) = "employee_id = {$emp_id}";
}
PHP;
addEvent($db, $PROJECT, 'form_public_leave_requests', 'onLoad', 'E', $lr_onload);
echo "  OK: leave_requests onLoad" . PHP_EOL;

// ============================================================
// 4. Form: Clock Entries - onBeforeInsert
// ============================================================
echo PHP_EOL . "--- Clock Entry Events ---" . PHP_EOL;

$ce_before_insert = <<<'PHP'
$emp_id = tt_get_employee_id();
{employee_id} = $emp_id;
{clock_in_source} = 'manual';
{is_manual_entry} = true;
PHP;
addEvent($db, $PROJECT, 'form_public_clock_entries', 'onBeforeInsert', 'E', $ce_before_insert);
echo "  OK: clock_entries onBeforeInsert" . PHP_EOL;

$ce_after_insert = <<<'PHP'
tt_audit_log('clock_entries', {clock_entry_id}, 'INSERT', null, ['employee_id' => {employee_id}, 'clock_in' => {clock_in}]);
PHP;
addEvent($db, $PROJECT, 'form_public_clock_entries', 'onAfterInsert', 'E', $ce_after_insert);
echo "  OK: clock_entries onAfterInsert" . PHP_EOL;

$ce_after_update = <<<'PHP'
tt_audit_log('clock_entries', {clock_entry_id}, 'UPDATE', null, ['clock_in' => {clock_in}, 'clock_out' => {clock_out}]);
PHP;
addEvent($db, $PROJECT, 'form_public_clock_entries', 'onAfterUpdate', 'E', $ce_after_update);
echo "  OK: clock_entries onAfterUpdate" . PHP_EOL;

// ============================================================
// 5. Form: Employees - onAfterInsert (create sec_users link)
// ============================================================
echo PHP_EOL . "--- Employee Events ---" . PHP_EOL;

$emp_after_insert = <<<'PHP'
// Auto-create security user and link
$login = strtolower({first_name}) . '.' . strtolower({last_name});
$login = preg_replace('/[^a-z0-9.]/', '', $login);

// Check if login exists
sc_lookup(chk, "SELECT login FROM sec_users WHERE login = '{$login}'");
if (!isset({chk}[0][0])) {
    // Create sec_users record with default password
    $default_pwd = password_hash('Welcome@123', PASSWORD_BCRYPT);
    sc_exec_sql("INSERT INTO sec_users (login, pswd, name, email, active) VALUES ('{$login}', '{$default_pwd}', '{first_name} {last_name}', '{email}', 'S')");

    // Add to Employee group
    sc_lookup(grp, "SELECT group_id FROM sec_groups WHERE description = 'Employee'");
    if (isset({grp}[0][0])) {
        sc_exec_sql("INSERT INTO sec_users_groups (login, group_id) VALUES ('{$login}', " . {grp}[0][0] . ")");
    }
}

// Create employee_sec_link
sc_exec_sql("INSERT INTO employee_sec_link (employee_id, sec_login) VALUES ({employee_id}, '{$login}') ON CONFLICT DO NOTHING");

tt_audit_log('employees', {employee_id}, 'INSERT', null, ['name' => '{first_name} {last_name}', 'login' => $login]);
PHP;
addEvent($db, $PROJECT, 'form_public_employees', 'onAfterInsert', 'E', $emp_after_insert);
echo "  OK: employees onAfterInsert" . PHP_EOL;

// ============================================================
// 6. Grid: Timesheets view - onScriptInit (security)
// ============================================================
echo PHP_EOL . "--- View Grid Security ---" . PHP_EOL;

$views_security = [
    ['grid_public_v_timesheet_summary', 'employee_id'],
    ['grid_public_v_leave_request_full', 'employee_id'],
    ['grid_public_v_leave_balance_current', 'employee_id'],
    ['grid_public_v_current_clock_status', 'employee_id'],
];

foreach ($views_security as $vs) {
    $code = str_replace('FIELD_NAME', $vs[1], $security_code);
    if (addEvent($db, $PROJECT, $vs[0], 'onScriptInit', 'E', $code)) {
        echo "  OK: {$vs[0]} onScriptInit" . PHP_EOL;
    }
}

// Mark apps as needing regeneration
$db->exec("UPDATE sc_tbapl SET Data_Ger = NULL WHERE Cod_Prj = '$PROJECT' AND Cod_Apl NOT LIKE 'app_%'");

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
echo "All events injected. Re-generate apps to apply." . PHP_EOL;

$db->close();
