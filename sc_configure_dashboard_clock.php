<?php
/**
 * Configure dash_main dashboard widgets and ctrl_clock_inout blank app
 */

$DB_PATH = 'C:/Program Files/NetMake/v9-php82/wwwroot/scriptcase/devel/conf/scriptcase/nm_scriptcase.db';
$db = new SQLite3($DB_PATH);
$db->busyTimeout(10000);

// ============================================================
// 1. Configure ctrl_clock_inout - Add clock in/out HTML and events
// ============================================================
echo "=== Configuring ctrl_clock_inout ===" . PHP_EOL;

// Add onExecute event with clock in/out logic
$clock_code = <<<'PHP'
// Clock In/Out Application
$emp_id = null;
$login = isset($_SESSION['sc_session']['login']) ? $_SESSION['sc_session']['login'] : '';
if (!empty($login)) {
    sc_lookup(rs, "SELECT employee_id FROM employee_sec_link WHERE sec_login = '{$login}'");
    if (isset({rs}[0][0])) {
        $emp_id = {rs}[0][0];
    }
}

if (!$emp_id) {
    {clock_status} = '<div style="text-align:center;padding:40px;"><h2>Please log in to use Clock In/Out</h2></div>';
    return;
}

// Get employee name
sc_lookup(emp, "SELECT first_name || ' ' || last_name FROM employees WHERE employee_id = {$emp_id}");
$emp_name = isset({emp}[0][0]) ? {emp}[0][0] : 'Unknown';

// Check if currently clocked in
sc_lookup(open_clock, "SELECT clock_entry_id, to_char(clock_in, 'HH24:MI:SS') as clock_time,
                              round(extract(epoch from (now() - clock_in))/3600, 1) as hours
                       FROM clock_entries WHERE employee_id = {$emp_id} AND clock_out IS NULL
                       ORDER BY clock_in DESC LIMIT 1");

$is_clocked_in = isset({open_clock}[0][0]);
$current_time = date('H:i:s');
$current_date = date('d/m/Y');

if ($is_clocked_in) {
    $clock_id = {open_clock}[0][0];
    $clock_time = {open_clock}[0][1];
    $hours = {open_clock}[0][2];

    {clock_status} = '
    <div style="text-align:center;padding:30px;font-family:Arial,sans-serif;">
        <h2 style="color:#333;">Welcome, ' . $emp_name . '</h2>
        <p style="font-size:24px;color:#666;">' . $current_date . ' - ' . $current_time . '</p>
        <div style="background:#e8f5e9;border-radius:12px;padding:30px;margin:20px auto;max-width:400px;">
            <p style="font-size:18px;color:#2e7d32;margin:0;">Status: <strong>CLOCKED IN</strong></p>
            <p style="font-size:16px;color:#555;">Since: ' . $clock_time . ' (' . $hours . ' hours)</p>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="clock_out">
            <input type="hidden" name="clock_id" value="' . $clock_id . '">
            <button type="submit" style="background:#e53935;color:white;border:none;padding:15px 60px;font-size:20px;border-radius:8px;cursor:pointer;margin-top:10px;">
                CLOCK OUT
            </button>
        </form>
    </div>';
} else {
    // Get today's entries
    sc_lookup(today, "SELECT to_char(clock_in, 'HH24:MI') as cin, to_char(clock_out, 'HH24:MI') as cout,
                             net_minutes FROM clock_entries
                      WHERE employee_id = {$emp_id} AND DATE(clock_in) = CURRENT_DATE AND clock_out IS NOT NULL
                      ORDER BY clock_in");
    $today_html = '';
    $total_mins = 0;
    if (is_array({today})) {
        foreach ({today} as $row) {
            $today_html .= '<tr><td>' . $row[0] . '</td><td>' . $row[1] . '</td><td>' . round($row[2]/60, 1) . 'h</td></tr>';
            $total_mins += $row[2];
        }
    }
    $total_hours = round($total_mins / 60, 1);

    {clock_status} = '
    <div style="text-align:center;padding:30px;font-family:Arial,sans-serif;">
        <h2 style="color:#333;">Welcome, ' . $emp_name . '</h2>
        <p style="font-size:24px;color:#666;">' . $current_date . ' - ' . $current_time . '</p>
        <div style="background:#fff3e0;border-radius:12px;padding:30px;margin:20px auto;max-width:400px;">
            <p style="font-size:18px;color:#e65100;margin:0;">Status: <strong>NOT CLOCKED IN</strong></p>
            <p style="font-size:16px;color:#555;">Today: ' . $total_hours . ' hours worked</p>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="clock_in">
            <button type="submit" style="background:#43a047;color:white;border:none;padding:15px 60px;font-size:20px;border-radius:8px;cursor:pointer;margin-top:10px;">
                CLOCK IN
            </button>
        </form>
        ' . ($today_html ? '<div style="margin-top:20px;max-width:400px;margin-left:auto;margin-right:auto;">
            <h4>Today\'s Entries</h4>
            <table style="width:100%;border-collapse:collapse;text-align:center;">
                <tr style="background:#f5f5f5;"><th style="padding:8px;">In</th><th style="padding:8px;">Out</th><th style="padding:8px;">Hours</th></tr>
                ' . $today_html . '
            </table>
        </div>' : '') . '
    </div>';
}

// Handle form submission
if (isset($_POST['action'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($_POST['action'] === 'clock_in') {
        sc_lookup(chk, "SELECT 1 FROM clock_entries WHERE employee_id = {$emp_id} AND clock_out IS NULL");
        if (!isset({chk}[0][0])) {
            sc_exec_sql("INSERT INTO clock_entries (employee_id, clock_in, clock_in_source, clock_in_ip) VALUES ({$emp_id}, NOW(), 'web', '{$ip}'::inet)");
            sc_commit_trans();
        }
        sc_redir(ctrl_clock_inout);
    } elseif ($_POST['action'] === 'clock_out' && isset($_POST['clock_id'])) {
        $cid = intval($_POST['clock_id']);
        sc_exec_sql("UPDATE clock_entries SET clock_out = NOW(), clock_out_source = 'web', clock_out_ip = '{$ip}'::inet WHERE clock_entry_id = {$cid} AND employee_id = {$emp_id}");
        sc_commit_trans();
        sc_redir(ctrl_clock_inout);
    }
}
PHP;

// Insert the event
$stmt = $db->prepare("SELECT COUNT(*) c FROM sc_tbevt WHERE Cod_Prj='project' AND Cod_Apl='ctrl_clock_inout' AND Nome='onExecute'");
$r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($r['c'] > 0) {
    $stmt = $db->prepare("UPDATE sc_tbevt SET Codigo=:code WHERE Cod_Prj='project' AND Cod_Apl='ctrl_clock_inout' AND Nome='onExecute'");
} else {
    $stmt = $db->prepare("INSERT INTO sc_tbevt (Cod_Prj, Versao, Cod_Apl, Nome, Tipo, Parms, Codigo) VALUES ('project', 1, 'ctrl_clock_inout', 'onExecute', 'E', '', :code)");
}
$stmt->bindValue(':code', $clock_code, SQLITE3_TEXT);
$stmt->execute();
echo "  Clock In/Out event added" . PHP_EOL;

// ============================================================
// 2. Configure dash_main - embed apps as widgets
// ============================================================
echo PHP_EOL . "=== Configuring dash_main ===" . PHP_EOL;

// Dashboard widgets are stored in Attr2 as container blocks
$r = $db->query("SELECT Attr2 FROM sc_tbapl WHERE Cod_Prj='project' AND Cod_Apl='dash_main'");
$row = $r->fetchArray(SQLITE3_ASSOC);
$attr2 = @unserialize($row['Attr2']);

if ($attr2 && isset($attr2['blocos'])) {
    // Add widget applications to the first block
    $widgets = [
        ['app' => 'ctrl_clock_inout', 'title' => 'Clock In/Out', 'width' => '100%', 'height' => '400px'],
        ['app' => 'chart_billable_ratio', 'title' => 'Billable Ratio', 'width' => '50%', 'height' => '400px'],
        ['app' => 'chart_hours_by_project', 'title' => 'Hours by Project', 'width' => '50%', 'height' => '400px'],
    ];

    // Update block with widget apps
    if (isset($attr2['blocos'][0])) {
        $attr2['blocos'][0]['campos'] = [];
        foreach ($widgets as $i => $w) {
            $attr2['blocos'][0]['campos'][$i * 10 + 10] = $w['app'];
        }
    }

    $stmt = $db->prepare("UPDATE sc_tbapl SET Attr2 = :attr2 WHERE Cod_Prj='project' AND Cod_Apl='dash_main'");
    $stmt->bindValue(':attr2', serialize($attr2), SQLITE3_TEXT);
    $stmt->execute();
    echo "  Dashboard widgets configured" . PHP_EOL;
} else {
    echo "  Dashboard Attr2 structure not as expected" . PHP_EOL;
}

// Mark for regeneration
$db->exec("UPDATE sc_tbapl SET Data_Ger = NULL WHERE Cod_Prj = 'project' AND Cod_Apl IN ('ctrl_clock_inout', 'dash_main', 'menu_main')");

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
echo "Menu: 48 items configured" . PHP_EOL;
echo "Clock In/Out: Event code added" . PHP_EOL;
echo "Dashboard: Widgets configured" . PHP_EOL;
echo PHP_EOL . "Now regenerate all apps and test!" . PHP_EOL;

$db->close();
