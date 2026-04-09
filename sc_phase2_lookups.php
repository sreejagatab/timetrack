<?php
/**
 * Phase 2: Configure all FK lookup fields across all forms
 * Changes field types to SELECT and sets up SQL lookups
 */

$DB_PATH = 'C:/Program Files/NetMake/v9-php82/wwwroot/scriptcase/devel/conf/scriptcase/nm_scriptcase.db';
$PROJECT = 'project';

$db = new SQLite3($DB_PATH);
$db->busyTimeout(10000);

// Build the Def_Complemento for a SELECT field
function buildSelectDC($sql) {
    return serialize([
        'global' => [
            'objeto' => 'SELECT',
            'metodo' => 'A',
            'tipo' => 'SIMPLES',
            'multipla' => 'N',
            'altura' => '1',
            'altura_ajax' => '10',
            'delimitador' => '',
            'desl_global' => '',
            'titulo' => '',
            'titulo_flag' => 'S',
            'def_ajax_edit_cap_texto' => 'N',
            'def_ajax_edit_like' => 'qqparte',
            'def_ajax_edit_label' => '',
            'def_ajax_edit_largura' => '30',
            'def_ajax_edit_case' => 'N',
            'iteracoes' => 1,
            'separador' => '',
            'entra' => null,
        ],
        'conteudo' => [],
        'relacoes' => [],
        'select' => $sql,
        'original' => '',
        'permitir_valor_branco' => '',
        'lookup_delimitador' => '',
        'connection' => '',
        'chk_opt_mark_all' => 'N',
    ]);
}

// Define all FK fields and their lookup SQLs
// Format: [app_name_pattern, field_name, lookup_sql]
$lookups = [
    // --- departments ---
    ['form_public_departments', 'parent_department_id',
     "SELECT department_id, department_name FROM departments WHERE is_active = TRUE ORDER BY department_name"],
    ['form_public_departments', 'manager_user_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- employees ---
    ['form_public_employees', 'department_id',
     "SELECT department_id, department_name FROM departments WHERE is_active = TRUE ORDER BY department_name"],
    ['form_public_employees', 'manager_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],
    ['form_public_employees', 'employment_type',
     null], // Will use fixed values instead

    // --- clock_entries ---
    ['form_public_clock_entries', 'employee_id',
     "SELECT employee_id, employee_code || ' - ' || first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- timesheets ---
    ['form_public_timesheets', 'employee_id',
     "SELECT employee_id, employee_code || ' - ' || first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],
    ['form_public_timesheets', 'period_id',
     "SELECT period_id, period_start || ' to ' || period_end FROM timesheet_periods ORDER BY period_start DESC"],
    ['form_public_timesheets', 'approved_by',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- timesheet_lines ---
    ['form_public_timesheet_lines', 'timesheet_id',
     "SELECT timesheet_id, 'TS-' || timesheet_id FROM timesheets ORDER BY timesheet_id DESC"],
    ['form_public_timesheet_lines', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects WHERE is_active = TRUE ORDER BY project_name"],
    ['form_public_timesheet_lines', 'task_id',
     "SELECT task_id, task_name FROM project_tasks WHERE is_active = TRUE ORDER BY task_name"],
    ['form_public_timesheet_lines', 'activity_type_id',
     "SELECT activity_type_id, type_name FROM activity_types WHERE is_active = TRUE ORDER BY type_name"],

    // --- projects ---
    ['form_public_projects', 'client_id',
     "SELECT client_id, client_name FROM clients WHERE is_active = TRUE ORDER BY client_name"],
    ['form_public_projects', 'department_id',
     "SELECT department_id, department_name FROM departments WHERE is_active = TRUE ORDER BY department_name"],
    ['form_public_projects', 'project_manager_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- project_tasks ---
    ['form_public_project_tasks', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects WHERE is_active = TRUE ORDER BY project_name"],
    ['form_public_project_tasks', 'parent_task_id',
     "SELECT task_id, task_name FROM project_tasks WHERE is_active = TRUE ORDER BY task_name"],
    ['form_public_project_tasks', 'assigned_to',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- project_members ---
    ['form_public_project_members', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects WHERE is_active = TRUE ORDER BY project_name"],
    ['form_public_project_members', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- holidays ---
    ['form_public_holidays', 'applies_to_department_id',
     "SELECT department_id, department_name FROM departments WHERE is_active = TRUE ORDER BY department_name"],

    // --- leave_policies ---
    ['form_public_leave_policies', 'leave_type_id',
     "SELECT leave_type_id, type_name FROM leave_types WHERE is_active = TRUE ORDER BY type_name"],

    // --- leave_balances ---
    ['form_public_leave_balances', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],
    ['form_public_leave_balances', 'leave_type_id',
     "SELECT leave_type_id, type_name FROM leave_types WHERE is_active = TRUE ORDER BY type_name"],

    // --- leave_requests ---
    ['form_public_leave_requests', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],
    ['form_public_leave_requests', 'leave_type_id',
     "SELECT leave_type_id, type_name || ' (' || type_code || ')' FROM leave_types WHERE is_active = TRUE ORDER BY type_name"],

    // --- leave_approvals ---
    ['form_public_leave_approvals', 'request_id',
     "SELECT request_id, 'REQ-' || request_id FROM leave_requests ORDER BY request_id DESC"],
    ['form_public_leave_approvals', 'approver_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- notifications ---
    ['form_public_notifications', 'recipient_employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],

    // --- employee_sec_link ---
    ['form_public_employee_sec_link', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees WHERE is_active = TRUE ORDER BY first_name"],
];

// Also configure GRID lookups (for display only)
$grid_lookups = [
    // Departments grid
    ['grid_public_departments', 'parent_department_id',
     "SELECT department_id, department_name FROM departments"],
    ['grid_public_departments', 'manager_user_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Employees grid
    ['grid_public_employees', 'department_id',
     "SELECT department_id, department_name FROM departments"],
    ['grid_public_employees', 'manager_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Clock entries grid
    ['grid_public_clock_entries', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Timesheets grid
    ['grid_public_timesheets', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],
    ['grid_public_timesheets', 'period_id',
     "SELECT period_id, period_start || ' to ' || period_end FROM timesheet_periods"],
    ['grid_public_timesheets', 'approved_by',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Timesheet lines grid
    ['grid_public_timesheet_lines', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects"],
    ['grid_public_timesheet_lines', 'task_id',
     "SELECT task_id, task_name FROM project_tasks"],
    ['grid_public_timesheet_lines', 'activity_type_id',
     "SELECT activity_type_id, type_name FROM activity_types"],

    // Projects grid
    ['grid_public_projects', 'client_id',
     "SELECT client_id, client_name FROM clients"],
    ['grid_public_projects', 'department_id',
     "SELECT department_id, department_name FROM departments"],
    ['grid_public_projects', 'project_manager_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Project tasks grid
    ['grid_public_project_tasks', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects"],
    ['grid_public_project_tasks', 'assigned_to',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Leave requests grid
    ['grid_public_leave_requests', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],
    ['grid_public_leave_requests', 'leave_type_id',
     "SELECT leave_type_id, type_name FROM leave_types"],

    // Leave balances grid
    ['grid_public_leave_balances', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],
    ['grid_public_leave_balances', 'leave_type_id',
     "SELECT leave_type_id, type_name FROM leave_types"],

    // Leave approvals grid
    ['grid_public_leave_approvals', 'approver_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Holidays grid
    ['grid_public_holidays', 'applies_to_department_id',
     "SELECT department_id, department_name FROM departments"],

    // Leave policies grid
    ['grid_public_leave_policies', 'leave_type_id',
     "SELECT leave_type_id, type_name FROM leave_types"],

    // Project members grid
    ['grid_public_project_members', 'project_id',
     "SELECT project_id, project_code || ' - ' || project_name FROM projects"],
    ['grid_public_project_members', 'employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Notifications grid
    ['grid_public_notifications', 'recipient_employee_id',
     "SELECT employee_id, first_name || ' ' || last_name FROM employees"],

    // Audit log grid
    ['grid_public_audit_log', 'changed_by_login',
     null], // Text field, no lookup needed
];

function updateField($db, $project, $app, $field, $sql) {
    if ($sql === null) return false;

    $dc = buildSelectDC($sql);

    // Update Tipo_Dado to SELECT
    $stmt = $db->prepare("UPDATE sc_tbcmp SET Tipo_Dado = 'SELECT', Def_Complemento = :dc
                          WHERE Cod_Prj = :prj AND Cod_Apl = :app AND Campo = :campo");
    $stmt->bindValue(':dc', $dc, SQLITE3_TEXT);
    $stmt->bindValue(':prj', $project, SQLITE3_TEXT);
    $stmt->bindValue(':app', $app, SQLITE3_TEXT);
    $stmt->bindValue(':campo', $field, SQLITE3_TEXT);
    $stmt->execute();

    return $db->changes() > 0;
}

function updateGridLookup($db, $project, $app, $field, $sql) {
    if ($sql === null) return false;

    // For grids, update Def_Complemento_Cons with lookup config
    $dc = serialize([
        'global' => [
            'objeto' => '',
            'metodo' => 'A',
            'tipo' => 'SIMPLES',
            'multipla' => 'N',
            'altura' => '1',
            'altura_ajax' => '10',
            'delimitador' => '',
            'desl_global' => '',
            'titulo' => '',
            'titulo_flag' => 'N',
            'def_ajax_edit_cap_texto' => 'N',
            'def_ajax_edit_like' => 'qqparte',
            'def_ajax_edit_label' => '',
            'def_ajax_edit_largura' => '30',
            'def_ajax_edit_case' => 'N',
            'iteracoes' => 1,
            'separador' => '',
            'entra' => 3,
        ],
        'conteudo' => [],
        'relacoes' => [],
        'select' => $sql,
        'original' => '',
        'permitir_valor_branco' => '',
        'lookup_delimitador' => '',
        'connection' => '',
        'chk_opt_mark_all' => 'N',
    ]);

    // Also update Attr1 to enable lookup display
    $r = $db->prepare("SELECT Attr1 FROM sc_tbcmp WHERE Cod_Prj = :prj AND Cod_Apl = :app AND Campo = :campo");
    $r->bindValue(':prj', $project); $r->bindValue(':app', $app); $r->bindValue(':campo', $field);
    $result = $r->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result && $result['Attr1']) {
        $a1 = @unserialize($result['Attr1']);
        if ($a1) {
            $a1['lookup_mostra'] = 'S';
            $a1['lookup_delim'] = ' - ';
            $a1_s = serialize($a1);

            $stmt = $db->prepare("UPDATE sc_tbcmp SET Def_Complemento_Cons = :dc, Attr1 = :a1
                                  WHERE Cod_Prj = :prj AND Cod_Apl = :app AND Campo = :campo");
            $stmt->bindValue(':dc', $dc); $stmt->bindValue(':a1', $a1_s);
            $stmt->bindValue(':prj', $project); $stmt->bindValue(':app', $app); $stmt->bindValue(':campo', $field);
            $stmt->execute();
            return $db->changes() > 0;
        }
    }

    $stmt = $db->prepare("UPDATE sc_tbcmp SET Def_Complemento_Cons = :dc
                          WHERE Cod_Prj = :prj AND Cod_Apl = :app AND Campo = :campo");
    $stmt->bindValue(':dc', $dc); $stmt->bindValue(':prj', $project);
    $stmt->bindValue(':app', $app); $stmt->bindValue(':campo', $field);
    $stmt->execute();
    return $db->changes() > 0;
}

// ---- MAIN ----
echo "=== Phase 2: Configuring Lookup Fields ===" . PHP_EOL . PHP_EOL;

echo "--- Form Lookups ---" . PHP_EOL;
$fc = 0;
foreach ($lookups as $l) {
    if ($l[2] === null) continue;
    if (updateField($db, $PROJECT, $l[0], $l[1], $l[2])) {
        echo "  OK: {$l[0]}.{$l[1]}" . PHP_EOL;
        $fc++;
    } else {
        echo "  SKIP: {$l[0]}.{$l[1]} (not found or unchanged)" . PHP_EOL;
    }
}

echo PHP_EOL . "--- Grid Lookups ---" . PHP_EOL;
$gc = 0;
foreach ($grid_lookups as $l) {
    if ($l[2] === null) continue;
    if (updateGridLookup($db, $PROJECT, $l[0], $l[1], $l[2])) {
        echo "  OK: {$l[0]}.{$l[1]}" . PHP_EOL;
        $gc++;
    } else {
        echo "  SKIP: {$l[0]}.{$l[1]}" . PHP_EOL;
    }
}

// Also hide created_at/updated_at on all forms
echo PHP_EOL . "--- Hiding auto-managed fields ---" . PHP_EOL;
$hide_fields = ['created_at', 'updated_at'];
$r = $db->query("SELECT Cod_Apl FROM sc_tbapl WHERE Cod_Prj = '$PROJECT' AND Tipo_Apl = 'form' AND Cod_Apl NOT LIKE 'app_%'");
$hc = 0;
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    foreach ($hide_fields as $hf) {
        $stmt = $db->prepare("SELECT Attr1 FROM sc_tbcmp WHERE Cod_Prj = :prj AND Cod_Apl = :app AND Campo = :campo");
        $stmt->bindValue(':prj', $PROJECT); $stmt->bindValue(':app', $row['Cod_Apl']); $stmt->bindValue(':campo', $hf);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ($res && $res['Attr1']) {
            $a1 = @unserialize($res['Attr1']);
            if ($a1) {
                $a1['hidden'] = 'S';
                $a1_s = serialize($a1);
                $db->exec("UPDATE sc_tbcmp SET Attr1 = '" . $db->escapeString($a1_s) .
                          "' WHERE Cod_Prj = '$PROJECT' AND Cod_Apl = '" . $row['Cod_Apl'] .
                          "' AND Campo = '$hf'");
                $hc++;
            }
        }
    }
}
echo "  Hidden $hc fields across all forms" . PHP_EOL;

// Set is_active fields as checkbox type
echo PHP_EOL . "--- Setting boolean fields as checkbox ---" . PHP_EOL;
$bc = 0;
$db->exec("UPDATE sc_tbcmp SET Tipo_Dado = 'CHECKBOX'
           WHERE Cod_Prj = '$PROJECT' AND Cod_Apl NOT LIKE 'app_%'
           AND Campo IN ('is_active','is_billable','is_paid','requires_approval','requires_attachment',
                         'is_recurring','overtime_eligible','is_manual_entry','is_overtime','is_billable_default',
                         'start_half_day','end_half_day','is_read','is_email_sent','is_locked')
           AND Tipo_Dado != 'CHECKBOX'");
$bc = $db->changes();
echo "  Updated $bc boolean fields to CHECKBOX" . PHP_EOL;

// Mark apps as outdated so they need re-generation
$db->exec("UPDATE sc_tbapl SET Data_Ger = NULL WHERE Cod_Prj = '$PROJECT' AND Cod_Apl NOT LIKE 'app_%'");

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
echo "Form lookups: $fc" . PHP_EOL;
echo "Grid lookups: $gc" . PHP_EOL;
echo "Hidden fields: $hc" . PHP_EOL;
echo "Checkbox fields: $bc" . PHP_EOL;
echo PHP_EOL . "Next: Re-generate all apps in ScriptCase IDE" . PHP_EOL;

$db->close();
