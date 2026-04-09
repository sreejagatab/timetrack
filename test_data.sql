-- ============================================================
-- Time Tracking System - Test Data
-- ============================================================

-- Departments
INSERT INTO departments (department_name, cost_center_code) VALUES
    ('Executive', 'CC-100'),
    ('Engineering', 'CC-200'),
    ('Design', 'CC-300'),
    ('Human Resources', 'CC-400'),
    ('Finance', 'CC-500'),
    ('Marketing', 'CC-600')
ON CONFLICT (department_name) DO NOTHING;

-- Set parent departments
UPDATE departments SET parent_department_id = (SELECT department_id FROM departments WHERE department_name = 'Executive')
WHERE department_name IN ('Engineering', 'Design', 'Human Resources', 'Finance', 'Marketing')
AND parent_department_id IS NULL;

-- Employees
INSERT INTO employees (employee_code, first_name, last_name, email, phone, department_id, job_title, employment_type, hire_date, hourly_rate, overtime_eligible)
VALUES
    ('EMP001', 'Rajesh', 'Kumar', 'rajesh.kumar@company.com', '+91-9876543210',
     (SELECT department_id FROM departments WHERE department_name = 'Executive'),
     'CEO', 'full_time', '2020-01-15', 150.00, FALSE),
    ('EMP002', 'Priya', 'Sharma', 'priya.sharma@company.com', '+91-9876543211',
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     'Engineering Manager', 'full_time', '2020-03-01', 100.00, FALSE),
    ('EMP003', 'Amit', 'Patel', 'amit.patel@company.com', '+91-9876543212',
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     'Senior Developer', 'full_time', '2021-06-15', 75.00, TRUE),
    ('EMP004', 'Sneha', 'Reddy', 'sneha.reddy@company.com', '+91-9876543213',
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     'Developer', 'full_time', '2022-01-10', 60.00, TRUE),
    ('EMP005', 'Vikram', 'Singh', 'vikram.singh@company.com', '+91-9876543214',
     (SELECT department_id FROM departments WHERE department_name = 'Design'),
     'Design Lead', 'full_time', '2021-02-01', 80.00, TRUE),
    ('EMP006', 'Ananya', 'Gupta', 'ananya.gupta@company.com', '+91-9876543215',
     (SELECT department_id FROM departments WHERE department_name = 'Human Resources'),
     'HR Manager', 'full_time', '2020-06-01', 85.00, FALSE),
    ('EMP007', 'Rahul', 'Mehta', 'rahul.mehta@company.com', '+91-9876543216',
     (SELECT department_id FROM departments WHERE department_name = 'Finance'),
     'Finance Manager', 'full_time', '2020-04-15', 90.00, FALSE),
    ('EMP008', 'Deepa', 'Nair', 'deepa.nair@company.com', '+91-9876543217',
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     'Junior Developer', 'full_time', '2023-07-01', 45.00, TRUE),
    ('EMP009', 'Karthik', 'Iyer', 'karthik.iyer@company.com', '+91-9876543218',
     (SELECT department_id FROM departments WHERE department_name = 'Marketing'),
     'Marketing Manager', 'full_time', '2021-09-01', 70.00, FALSE),
    ('EMP010', 'Meera', 'Joshi', 'meera.joshi@company.com', '+91-9876543219',
     (SELECT department_id FROM departments WHERE department_name = 'Design'),
     'UI Designer', 'full_time', '2022-11-15', 55.00, TRUE)
ON CONFLICT (employee_code) DO NOTHING;

-- Set managers
UPDATE employees SET manager_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP001')
WHERE employee_code IN ('EMP002', 'EMP006', 'EMP007', 'EMP009');

UPDATE employees SET manager_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP002')
WHERE employee_code IN ('EMP003', 'EMP004', 'EMP008');

UPDATE employees SET manager_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP005')
WHERE employee_code = 'EMP010';

UPDATE employees SET manager_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP002')
WHERE employee_code = 'EMP005';

-- Set department managers
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP001')
WHERE department_name = 'Executive';
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP002')
WHERE department_name = 'Engineering';
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP005')
WHERE department_name = 'Design';
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP006')
WHERE department_name = 'Human Resources';
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP007')
WHERE department_name = 'Finance';
UPDATE departments SET manager_user_id = (SELECT employee_id FROM employees WHERE employee_code = 'EMP009')
WHERE department_name = 'Marketing';

-- Clients
INSERT INTO clients (client_name, client_code, contact_name, contact_email, default_billing_rate) VALUES
    ('Acme Corporation', 'ACME', 'John Smith', 'john@acme.com', 120.00),
    ('TechStart Inc', 'TECH', 'Sarah Lee', 'sarah@techstart.com', 150.00),
    ('Global Services', 'GLOB', 'Mike Chen', 'mike@globalservices.com', 100.00)
ON CONFLICT (client_code) DO NOTHING;

-- Projects
INSERT INTO projects (project_code, project_name, client_id, department_id, project_manager_id, status, start_date, end_date, budget_hours, budget_amount, default_billing_rate, is_billable)
VALUES
    ('PRJ-001', 'Website Redesign', (SELECT client_id FROM clients WHERE client_code = 'ACME'),
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     (SELECT employee_id FROM employees WHERE employee_code = 'EMP002'),
     'active', '2026-01-01', '2026-06-30', 500, 60000, 120.00, TRUE),
    ('PRJ-002', 'Mobile App Development', (SELECT client_id FROM clients WHERE client_code = 'TECH'),
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     (SELECT employee_id FROM employees WHERE employee_code = 'EMP002'),
     'active', '2026-02-01', '2026-08-31', 800, 120000, 150.00, TRUE),
    ('PRJ-003', 'Brand Identity', (SELECT client_id FROM clients WHERE client_code = 'GLOB'),
     (SELECT department_id FROM departments WHERE department_name = 'Design'),
     (SELECT employee_id FROM employees WHERE employee_code = 'EMP005'),
     'active', '2026-03-01', '2026-05-31', 200, 20000, 100.00, TRUE),
    ('PRJ-INT', 'Internal Tools', NULL,
     (SELECT department_id FROM departments WHERE department_name = 'Engineering'),
     (SELECT employee_id FROM employees WHERE employee_code = 'EMP002'),
     'active', '2026-01-01', NULL, NULL, NULL, NULL, FALSE)
ON CONFLICT (project_code) DO NOTHING;

-- Project Tasks
INSERT INTO project_tasks (project_id, task_name, task_code, status, estimated_hours) VALUES
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), 'UI Design', 'T-001', 'in_progress', 80),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), 'Frontend Development', 'T-002', 'open', 120),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), 'Backend API', 'T-003', 'open', 100),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), 'Testing', 'T-004', 'open', 60),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), 'App Architecture', 'T-005', 'completed', 40),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), 'iOS Development', 'T-006', 'in_progress', 200),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), 'Android Development', 'T-007', 'in_progress', 200),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-003'), 'Logo Design', 'T-008', 'in_progress', 40),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-003'), 'Style Guide', 'T-009', 'open', 60);

-- Project Members
INSERT INTO project_members (project_id, employee_id, role) VALUES
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP002'), 'manager'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP003'), 'lead'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP004'), 'member'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-001'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP005'), 'member'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP003'), 'lead'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP004'), 'member'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-002'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP008'), 'member'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-003'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP005'), 'manager'),
    ((SELECT project_id FROM projects WHERE project_code = 'PRJ-003'), (SELECT employee_id FROM employees WHERE employee_code = 'EMP010'), 'member')
ON CONFLICT DO NOTHING;

-- Timesheet Periods (weekly for current month)
INSERT INTO timesheet_periods (period_start, period_end, period_type) VALUES
    ('2026-03-30', '2026-04-05', 'weekly'),
    ('2026-04-06', '2026-04-12', 'weekly'),
    ('2026-04-13', '2026-04-19', 'weekly'),
    ('2026-04-20', '2026-04-26', 'weekly')
ON CONFLICT DO NOTHING;

-- Clock Entries (sample for current week)
INSERT INTO clock_entries (employee_id, clock_in, clock_out, clock_in_source) VALUES
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP003'), '2026-04-07 09:00:00', '2026-04-07 18:00:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP003'), '2026-04-08 08:45:00', '2026-04-08 17:30:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP004'), '2026-04-07 09:15:00', '2026-04-07 18:15:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP004'), '2026-04-08 09:00:00', '2026-04-08 17:45:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP005'), '2026-04-07 10:00:00', '2026-04-07 19:00:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP008'), '2026-04-07 09:30:00', '2026-04-07 18:30:00', 'web'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP010'), '2026-04-07 09:00:00', '2026-04-07 17:00:00', 'web');

-- Timesheets
INSERT INTO timesheets (employee_id, period_id, status, total_regular_hours, total_overtime_hours)
VALUES
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP003'),
     (SELECT period_id FROM timesheet_periods WHERE period_start = '2026-04-06'), 'submitted', 40, 2),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP004'),
     (SELECT period_id FROM timesheet_periods WHERE period_start = '2026-04-06'), 'draft', 36, 0),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP005'),
     (SELECT period_id FROM timesheet_periods WHERE period_start = '2026-04-06'), 'approved', 40, 0)
ON CONFLICT DO NOTHING;

-- Timesheet Lines
INSERT INTO timesheet_lines (timesheet_id, line_date, project_id, task_id, activity_type_id, hours, is_billable, billing_rate, description)
SELECT t.timesheet_id, '2026-04-07',
       (SELECT project_id FROM projects WHERE project_code = 'PRJ-001'),
       (SELECT task_id FROM project_tasks WHERE task_code = 'T-002'),
       (SELECT activity_type_id FROM activity_types WHERE type_name = 'Development'),
       8.0, TRUE, 75.00, 'Frontend component development'
FROM timesheets t JOIN employees e ON e.employee_id = t.employee_id WHERE e.employee_code = 'EMP003'
ON CONFLICT DO NOTHING;

INSERT INTO timesheet_lines (timesheet_id, line_date, project_id, task_id, activity_type_id, hours, is_billable, billing_rate, description)
SELECT t.timesheet_id, '2026-04-08',
       (SELECT project_id FROM projects WHERE project_code = 'PRJ-002'),
       (SELECT task_id FROM project_tasks WHERE task_code = 'T-006'),
       (SELECT activity_type_id FROM activity_types WHERE type_name = 'Development'),
       8.0, TRUE, 75.00, 'iOS module implementation'
FROM timesheets t JOIN employees e ON e.employee_id = t.employee_id WHERE e.employee_code = 'EMP003'
ON CONFLICT DO NOTHING;

-- Leave Policies
INSERT INTO leave_policies (leave_type_id, employment_type, annual_entitlement_days, accrual_frequency, max_carryover_days, effective_from) VALUES
    ((SELECT leave_type_id FROM leave_types WHERE type_code = 'PTO'), 'full_time', 15, 'annual', 5, '2026-01-01'),
    ((SELECT leave_type_id FROM leave_types WHERE type_code = 'SICK'), 'full_time', 10, 'annual', 0, '2026-01-01'),
    ((SELECT leave_type_id FROM leave_types WHERE type_code = 'PERS'), 'full_time', 3, 'annual', 0, '2026-01-01'),
    ((SELECT leave_type_id FROM leave_types WHERE type_code = 'WFH'), 'full_time', 24, 'annual', 0, '2026-01-01')
ON CONFLICT DO NOTHING;

-- Leave Balances (2026) for all employees
INSERT INTO leave_balances (employee_id, leave_type_id, year, entitled_days, accrued_days)
SELECT e.employee_id, lt.leave_type_id, 2026, lp.annual_entitlement_days, lp.annual_entitlement_days
FROM employees e
CROSS JOIN leave_policies lp
JOIN leave_types lt ON lt.leave_type_id = lp.leave_type_id
WHERE e.is_active = TRUE AND lp.employment_type = 'full_time'
ON CONFLICT DO NOTHING;

-- Sample Leave Requests
INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, status, reason, submitted_at) VALUES
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP003'),
     (SELECT leave_type_id FROM leave_types WHERE type_code = 'PTO'),
     '2026-04-20', '2026-04-22', 3, 'submitted', 'Family vacation', NOW()),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP004'),
     (SELECT leave_type_id FROM leave_types WHERE type_code = 'SICK'),
     '2026-04-10', '2026-04-10', 1, 'approved', 'Not feeling well', NOW()),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP010'),
     (SELECT leave_type_id FROM leave_types WHERE type_code = 'WFH'),
     '2026-04-14', '2026-04-16', 3, 'submitted', 'Internet installation at new flat', NOW());

-- Security user links
INSERT INTO sec_users (login, pswd, name, email, active) VALUES
    ('rajesh.kumar', '$2y$10$YWVhN2MzOGIzMjE3MjY0YuKHHJ.TGz4Gw7WBLC4BDxcVqRHT5j5K', 'Rajesh Kumar', 'rajesh.kumar@company.com', 'S'),
    ('priya.sharma', '$2y$10$YWVhN2MzOGIzMjE3MjY0YuKHHJ.TGz4Gw7WBLC4BDxcVqRHT5j5K', 'Priya Sharma', 'priya.sharma@company.com', 'S'),
    ('amit.patel', '$2y$10$YWVhN2MzOGIzMjE3MjY0YuKHHJ.TGz4Gw7WBLC4BDxcVqRHT5j5K', 'Amit Patel', 'amit.patel@company.com', 'S'),
    ('ananya.gupta', '$2y$10$YWVhN2MzOGIzMjE3MjY0YuKHHJ.TGz4Gw7WBLC4BDxcVqRHT5j5K', 'Ananya Gupta', 'ananya.gupta@company.com', 'S')
ON CONFLICT DO NOTHING;

-- Link employees to sec_users
INSERT INTO employee_sec_link (employee_id, sec_login) VALUES
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP001'), 'rajesh.kumar'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP002'), 'priya.sharma'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP003'), 'amit.patel'),
    ((SELECT employee_id FROM employees WHERE employee_code = 'EMP006'), 'ananya.gupta')
ON CONFLICT DO NOTHING;

-- Assign security groups
INSERT INTO sec_users_groups (login, group_id) VALUES
    ('rajesh.kumar', (SELECT group_id FROM sec_groups WHERE description = 'Admin')),
    ('priya.sharma', (SELECT group_id FROM sec_groups WHERE description = 'Dept_Manager')),
    ('amit.patel', (SELECT group_id FROM sec_groups WHERE description = 'Employee')),
    ('ananya.gupta', (SELECT group_id FROM sec_groups WHERE description = 'HR_Manager')),
    ('admin', (SELECT group_id FROM sec_groups WHERE description = 'Admin'))
ON CONFLICT DO NOTHING;
