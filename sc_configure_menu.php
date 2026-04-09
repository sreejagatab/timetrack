<?php
/**
 * Configure menu_main with all Time Tracking menu items
 * Injects menu structure into navmenu_itens JSON in Attr2
 */

$DB_PATH = 'C:/Program Files/NetMake/v9-php82/wwwroot/scriptcase/devel/conf/scriptcase/nm_scriptcase.db';
$db = new SQLite3($DB_PATH);
$db->busyTimeout(10000);

// Build menu item helper
function mi($text, $app, $icon, $id, $children = null) {
    $item = [
        'text' => $text,
        'app' => $app,
        'icon' => $icon,
        'icon_check' => 'S',
        'id' => $id,
        'hint' => '',
        'link_target' => 'self',
        'fav_check' => 'N',
        'mega_check' => 'N',
        'itree' => ['a' => ['attributes' => []], 'icon' => false, 'li' => ['attributes' => []]],
    ];
    if ($children) {
        $item['children'] = $children;
    }
    return $item;
}

// Define menu structure
$menu_items = [
    mi('Dashboard', 'dash_main', 'fas fa-tachometer-alt', 'item_1'),

    mi('Time', '', 'fas fa-clock', 'item_2', [
        mi('Clock In/Out', 'ctrl_clock_inout', 'fas fa-sign-in-alt', 'item_3'),
        mi('Clock History', 'grid_public_clock_entries', 'fas fa-history', 'item_4'),
    ]),

    mi('Timesheets', '', 'fas fa-calendar-check', 'item_10', [
        mi('My Timesheets', 'grid_public_timesheets', 'fas fa-list', 'item_11'),
        mi('New Timesheet', 'form_public_timesheets', 'fas fa-plus', 'item_12'),
        mi('Timesheet Summary', 'grid_public_v_timesheet_summary', 'fas fa-chart-bar', 'item_13'),
        mi('Timesheet Periods', 'grid_public_timesheet_periods', 'fas fa-calendar', 'item_14'),
    ]),

    mi('Projects', '', 'fas fa-project-diagram', 'item_20', [
        mi('Projects', 'grid_public_projects', 'fas fa-folder-open', 'item_21'),
        mi('Clients', 'grid_public_clients', 'fas fa-building', 'item_22'),
        mi('Tasks', 'grid_public_project_tasks', 'fas fa-tasks', 'item_23'),
        mi('Team Members', 'grid_public_project_members', 'fas fa-users', 'item_24'),
        mi('Project Hours', 'grid_public_v_project_hours_summary', 'fas fa-chart-pie', 'item_25'),
    ]),

    mi('Leave', '', 'fas fa-plane-departure', 'item_30', [
        mi('My Leave Requests', 'grid_public_leave_requests', 'fas fa-list-alt', 'item_31'),
        mi('Request Leave', 'form_public_leave_requests', 'fas fa-plus-circle', 'item_32'),
        mi('Leave Calendar', 'calendar_public_leave_requests', 'fas fa-calendar-alt', 'item_33'),
        mi('Leave Balances', 'grid_public_v_leave_balance_current', 'fas fa-balance-scale', 'item_34'),
        mi('Leave Approvals', 'grid_public_leave_approvals', 'fas fa-check-double', 'item_35'),
    ]),

    mi('Reports', '', 'fas fa-chart-line', 'item_40', [
        mi('Employee Directory', 'grid_public_v_employee_directory', 'fas fa-address-book', 'item_41'),
        mi('Timesheet Summary', 'grid_public_v_timesheet_summary', 'fas fa-file-alt', 'item_42'),
        mi('Project Hours', 'grid_public_v_project_hours_summary', 'fas fa-file-invoice', 'item_43'),
        mi('Leave Requests', 'grid_public_v_leave_request_full', 'fas fa-file-medical', 'item_44'),
        mi('Hours by Project', 'chart_hours_by_project', 'fas fa-chart-bar', 'item_45'),
        mi('Hours by Dept', 'chart_hours_by_dept', 'fas fa-chart-bar', 'item_46'),
        mi('Overtime Trend', 'chart_overtime_trend', 'fas fa-chart-line', 'item_47'),
        mi('Leave by Type', 'chart_leave_by_type', 'fas fa-chart-pie', 'item_48'),
        mi('Billable Ratio', 'chart_billable_ratio', 'fas fa-percentage', 'item_49'),
    ]),

    mi('Admin', '', 'fas fa-cogs', 'item_50', [
        mi('Employees', 'grid_public_employees', 'fas fa-user-tie', 'item_51'),
        mi('Departments', 'grid_public_departments', 'fas fa-sitemap', 'item_52'),
        mi('Holidays', 'grid_public_holidays', 'fas fa-umbrella-beach', 'item_53'),
        mi('Holiday Calendar', 'calendar_public_holidays', 'fas fa-calendar-day', 'item_54'),
        mi('Leave Types', 'grid_public_leave_types', 'fas fa-tags', 'item_55'),
        mi('Leave Policies', 'grid_public_leave_policies', 'fas fa-file-contract', 'item_56'),
        mi('Activity Types', 'grid_public_activity_types', 'fas fa-tag', 'item_57'),
        mi('Overtime Rules', 'grid_public_overtime_rules', 'fas fa-gavel', 'item_58'),
        mi('System Config', 'grid_public_system_config', 'fas fa-sliders-h', 'item_59'),
        mi('Audit Log', 'grid_public_audit_log', 'fas fa-clipboard-list', 'item_60'),
        mi('Notifications', 'grid_public_notifications', 'fas fa-bell', 'item_61'),
    ]),

    mi('Security', '', 'fas fa-shield-alt', 'item_70', [
        mi('Users', 'app_grid_sec_users', 'fas fa-users-cog', 'item_71'),
        mi('Groups', 'app_grid_sec_groups', 'fas fa-user-friends', 'item_72'),
        mi('Users/Groups', 'app_grid_sec_users_groups', 'fas fa-user-tag', 'item_73'),
        mi('Sync Apps', 'app_sync_apps', 'fas fa-sync', 'item_74'),
    ]),
];

// User menu items
$user_items = [
    mi('My Profile', 'app_my_info', 'fas fa-user', 'item_user_1'),
    mi('Change Password', 'app_change_pswd', 'fas fa-key', 'item_user_2'),
    mi('Logout', 'app_Login', 'fas fa-sign-out-alt', 'item_user_3'),
];
$user_items[2]['link_target'] = 'parente';

// Toolbar items
$tb_items = [
    ['text' => 'Search', 'app' => 'search', 'icon' => 'fas fa-search', 'icon_check' => 'S', 'display' => 'S', 'id' => 'item_tb_1',
     'itree' => ['a' => ['attributes' => []], 'icon' => false, 'li' => ['attributes' => []]]],
    ['text' => 'Themes', 'app' => 'themes', 'icon' => 'fas fa-paint-roller', 'icon_check' => 'S', 'display' => 'S', 'id' => 'item_tb_2',
     'itree' => ['a' => ['attributes' => []], 'icon' => false, 'li' => ['attributes' => []]]],
];

// Build the full navmenu JSON
$navmenu = [
    'theme' => 'midnight',
    'layout' => 'H',
    'header_string' => 'TimeTrack',
    'header_string_pos' => 'H',
    'font_string' => 'Open Sans',
    'check_split' => 'N',
    'check_toolbar' => 'S',
    'check_show_search_path' => 'S',
    'check_shortcut_label' => 'S',
    'check_start_expanded' => 'N',
    'check_use_loader' => 'S',
    'should_reload' => 'S',
    'layout_usr_pos' => 'out',
    'usercheck' => 'S',
    'username' => '[usr_name]',
    'userimage' => '[usr_picture]',
    'userdesc' => '[usr_login] / [usr_email]',
    'logo' => '',
    'logo_compact' => '',
    'pick_themes' => ['dark-cobalt', 'dark-coffee', 'dark-midnight', 'light-gray', 'monochromatic-blue'],
    'shortcuts' => [],
    'items' => $menu_items,
    'user_items' => $user_items,
    'tb_items' => $tb_items,
    'notif_login_var' => '[usr_login]',
    'notif_data' => [
        'notif_table' => '', 'notif_id' => '', 'notif_title' => '', 'notif_message' => '',
        'notif_dtexpire' => '', 'notif_login_sender' => '', 'notif_categ' => '',
        'notif_type' => '', 'notif_link' => '', 'inbox_table' => '',
        'inbox_field_notif_id' => '', 'inbox_field_ontop' => '', 'inbox_field_userid' => '',
        'inbox_field_isread' => '', 'inbox_field_read_date' => '', 'inbox_field_sent_date' => '',
        'inbox_field_tag' => '', 'inbox_field_important' => '', 'user_table' => '',
        'user_login' => '', 'user_fullname' => '', 'user_image' => '', 'notif_connection' => '',
    ],
    'notif_open_all_app' => '',
    'notif_refresh_interval' => '10000',
    'notif_limit' => '10',
];

$navmenu_json = json_encode($navmenu, JSON_UNESCAPED_UNICODE);

// Get current Attr2
$r = $db->query("SELECT Attr2 FROM sc_tbapl WHERE Cod_Prj = 'project' AND Cod_Apl = 'menu_main'");
$row = $r->fetchArray(SQLITE3_ASSOC);
$attr2 = unserialize($row['Attr2']);

// Update navmenu_itens
$attr2['navmenu_itens'] = $navmenu_json;

// Save back
$attr2_s = serialize($attr2);
$stmt = $db->prepare("UPDATE sc_tbapl SET Attr2 = :attr2, Data_Ger = NULL WHERE Cod_Prj = 'project' AND Cod_Apl = 'menu_main'");
$stmt->bindValue(':attr2', $attr2_s, SQLITE3_TEXT);
$stmt->execute();

echo "Menu configured with " . count($menu_items) . " top-level items" . PHP_EOL;
echo "Total menu entries: " . count($menu_items);
$count_children = function($items) use (&$count_children) {
    $c = 0;
    foreach ($items as $item) {
        $c++;
        if (isset($item['children'])) $c += $count_children($item['children']);
    }
    return $c;
};
echo " (" . $count_children($menu_items) . " total including children)" . PHP_EOL;
echo "User menu: " . count($user_items) . " items" . PHP_EOL;

// Also set menu_main as the security module's initial app
echo PHP_EOL . "Setting menu_main as login redirect..." . PHP_EOL;
$db->exec("UPDATE sc_tbapl SET Data_Ger = NULL WHERE Cod_Prj = 'project' AND Cod_Apl = 'menu_main'");

echo "Done! Re-generate menu_main to apply." . PHP_EOL;
$db->close();
