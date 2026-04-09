-- ============================================================
-- Time Tracking System - Complete DDL Script
-- Database: timetrack_db | PostgreSQL 14+
-- ============================================================

-- ============================================================
-- 1. CORE REFERENCE TABLES
-- ============================================================

CREATE TABLE departments (
    department_id       SERIAL PRIMARY KEY,
    department_name     VARCHAR(100) NOT NULL UNIQUE,
    parent_department_id INTEGER REFERENCES departments(department_id),
    manager_user_id     INTEGER,  -- FK added after employees table
    cost_center_code    VARCHAR(20),
    is_active           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_dept_parent ON departments(parent_department_id);

CREATE TABLE employees (
    employee_id             SERIAL PRIMARY KEY,
    employee_code           VARCHAR(20) NOT NULL UNIQUE,
    first_name              VARCHAR(60) NOT NULL,
    last_name               VARCHAR(60) NOT NULL,
    email                   VARCHAR(120) NOT NULL UNIQUE,
    phone                   VARCHAR(30),
    department_id           INTEGER NOT NULL REFERENCES departments(department_id),
    job_title               VARCHAR(100),
    manager_id              INTEGER REFERENCES employees(employee_id),
    employment_type         VARCHAR(20) NOT NULL DEFAULT 'full_time'
                            CHECK (employment_type IN ('full_time','part_time','contractor','intern')),
    hire_date               DATE NOT NULL,
    termination_date        DATE,
    standard_hours_per_week NUMERIC(5,2) NOT NULL DEFAULT 40.00,
    hourly_rate             NUMERIC(10,2),
    overtime_eligible       BOOLEAN NOT NULL DEFAULT TRUE,
    timezone                VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
    is_active               BOOLEAN NOT NULL DEFAULT TRUE,
    photo                   BYTEA,
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_emp_dept ON employees(department_id);
CREATE INDEX idx_emp_manager ON employees(manager_id);
CREATE INDEX idx_emp_active ON employees(is_active) WHERE is_active = TRUE;

-- Now add the FK from departments to employees
ALTER TABLE departments
    ADD CONSTRAINT fk_dept_manager
    FOREIGN KEY (manager_user_id) REFERENCES employees(employee_id);

CREATE INDEX idx_dept_manager ON departments(manager_user_id);

CREATE TABLE employee_sec_link (
    employee_id INTEGER PRIMARY KEY REFERENCES employees(employee_id),
    sec_login   VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE system_config (
    config_key   VARCHAR(60) PRIMARY KEY,
    config_value TEXT NOT NULL,
    description  TEXT,
    updated_at   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 2. CLOCK IN/OUT AND TIMESHEETS
-- ============================================================

CREATE TABLE activity_types (
    activity_type_id    SERIAL PRIMARY KEY,
    type_name           VARCHAR(60) NOT NULL UNIQUE,
    is_billable_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active           BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE overtime_rules (
    rule_id                     SERIAL PRIMARY KEY,
    rule_name                   VARCHAR(60) NOT NULL,
    daily_threshold_hours       NUMERIC(4,2),
    weekly_threshold_hours      NUMERIC(5,2),
    multiplier                  NUMERIC(3,2) NOT NULL DEFAULT 1.50,
    applies_to_employment_type  VARCHAR(20),
    effective_from              DATE NOT NULL,
    effective_to                DATE,
    is_active                   BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE clock_entries (
    clock_entry_id  BIGSERIAL PRIMARY KEY,
    employee_id     INTEGER NOT NULL REFERENCES employees(employee_id),
    clock_in        TIMESTAMP NOT NULL,
    clock_out       TIMESTAMP,
    clock_in_source VARCHAR(20) NOT NULL DEFAULT 'web'
                    CHECK (clock_in_source IN ('web','mobile','kiosk','manual','import')),
    clock_out_source VARCHAR(20)
                    CHECK (clock_out_source IS NULL OR clock_out_source IN ('web','mobile','kiosk','manual','import')),
    clock_in_ip     INET,
    clock_out_ip    INET,
    clock_in_note   TEXT,
    clock_out_note  TEXT,
    is_manual_entry BOOLEAN NOT NULL DEFAULT FALSE,
    break_minutes   INTEGER NOT NULL DEFAULT 0 CHECK (break_minutes >= 0),
    gross_minutes   INTEGER,
    net_minutes     INTEGER,
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_clock_out_after_in CHECK (clock_out IS NULL OR clock_out > clock_in)
);

CREATE INDEX idx_clock_emp_date ON clock_entries(employee_id, clock_in DESC);
CREATE INDEX idx_clock_open ON clock_entries(employee_id) WHERE clock_out IS NULL;

-- Trigger to auto-compute gross/net minutes
CREATE OR REPLACE FUNCTION fn_clock_entry_calc() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.clock_out IS NOT NULL THEN
        NEW.gross_minutes := EXTRACT(EPOCH FROM (NEW.clock_out - NEW.clock_in))::INTEGER / 60;
        NEW.net_minutes := NEW.gross_minutes - COALESCE(NEW.break_minutes, 0);
    ELSE
        NEW.gross_minutes := NULL;
        NEW.net_minutes := NULL;
    END IF;
    NEW.updated_at := NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_clock_entry_calc
    BEFORE INSERT OR UPDATE ON clock_entries
    FOR EACH ROW EXECUTE FUNCTION fn_clock_entry_calc();

CREATE TABLE timesheet_periods (
    period_id    SERIAL PRIMARY KEY,
    period_start DATE NOT NULL,
    period_end   DATE NOT NULL,
    period_type  VARCHAR(10) NOT NULL DEFAULT 'weekly'
                 CHECK (period_type IN ('weekly','biweekly','monthly')),
    is_locked    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at   TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_period UNIQUE (period_start, period_end, period_type),
    CONSTRAINT chk_period_dates CHECK (period_end >= period_start)
);

CREATE TABLE timesheets (
    timesheet_id         BIGSERIAL PRIMARY KEY,
    employee_id          INTEGER NOT NULL REFERENCES employees(employee_id),
    period_id            INTEGER NOT NULL REFERENCES timesheet_periods(period_id),
    status               VARCHAR(20) NOT NULL DEFAULT 'draft'
                         CHECK (status IN ('draft','submitted','approved','rejected','revision_requested')),
    total_regular_hours  NUMERIC(6,2) NOT NULL DEFAULT 0,
    total_overtime_hours NUMERIC(6,2) NOT NULL DEFAULT 0,
    total_break_hours    NUMERIC(6,2) NOT NULL DEFAULT 0,
    submitted_at         TIMESTAMP,
    submitted_note       TEXT,
    approved_by          INTEGER REFERENCES employees(employee_id),
    approved_at          TIMESTAMP,
    approval_note        TEXT,
    rejection_reason     TEXT,
    created_at           TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_emp_period UNIQUE (employee_id, period_id)
);

CREATE INDEX idx_ts_status ON timesheets(status);
CREATE INDEX idx_ts_approver ON timesheets(approved_by);

-- ============================================================
-- 3. PROJECT-BASED TIME TRACKING
-- ============================================================

CREATE TABLE clients (
    client_id            SERIAL PRIMARY KEY,
    client_name          VARCHAR(120) NOT NULL,
    client_code          VARCHAR(20) NOT NULL UNIQUE,
    contact_name         VARCHAR(120),
    contact_email        VARCHAR(120),
    contact_phone        VARCHAR(30),
    billing_address      TEXT,
    default_billing_rate NUMERIC(10,2),
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_at           TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE projects (
    project_id           SERIAL PRIMARY KEY,
    project_code         VARCHAR(30) NOT NULL UNIQUE,
    project_name         VARCHAR(150) NOT NULL,
    client_id            INTEGER REFERENCES clients(client_id),
    department_id        INTEGER REFERENCES departments(department_id),
    project_manager_id   INTEGER REFERENCES employees(employee_id),
    status               VARCHAR(20) NOT NULL DEFAULT 'active'
                         CHECK (status IN ('planning','active','on_hold','completed','cancelled')),
    start_date           DATE,
    end_date             DATE,
    budget_hours         NUMERIC(10,2),
    budget_amount        NUMERIC(12,2),
    default_billing_rate NUMERIC(10,2),
    is_billable          BOOLEAN NOT NULL DEFAULT TRUE,
    description          TEXT,
    is_active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_at           TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_proj_client ON projects(client_id);
CREATE INDEX idx_proj_dept ON projects(department_id);
CREATE INDEX idx_proj_pm ON projects(project_manager_id);
CREATE INDEX idx_proj_status ON projects(status);

CREATE TABLE project_tasks (
    task_id        SERIAL PRIMARY KEY,
    project_id     INTEGER NOT NULL REFERENCES projects(project_id),
    task_name      VARCHAR(150) NOT NULL,
    task_code      VARCHAR(30),
    parent_task_id INTEGER REFERENCES project_tasks(task_id),
    assigned_to    INTEGER REFERENCES employees(employee_id),
    status         VARCHAR(20) NOT NULL DEFAULT 'open'
                   CHECK (status IN ('open','in_progress','completed','cancelled')),
    estimated_hours NUMERIC(8,2),
    start_date     DATE,
    due_date       DATE,
    is_billable    BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    is_active      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_task_project ON project_tasks(project_id);
CREATE INDEX idx_task_parent ON project_tasks(parent_task_id);
CREATE INDEX idx_task_assigned ON project_tasks(assigned_to);

CREATE TABLE project_members (
    project_id            INTEGER NOT NULL REFERENCES projects(project_id),
    employee_id           INTEGER NOT NULL REFERENCES employees(employee_id),
    role                  VARCHAR(30) NOT NULL DEFAULT 'member'
                          CHECK (role IN ('manager','lead','member','viewer')),
    billing_rate_override NUMERIC(10,2),
    joined_at             TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (project_id, employee_id)
);

-- Now create timesheet_lines (depends on projects and project_tasks)
CREATE TABLE timesheet_lines (
    line_id          BIGSERIAL PRIMARY KEY,
    timesheet_id     BIGINT NOT NULL REFERENCES timesheets(timesheet_id) ON DELETE CASCADE,
    line_date        DATE NOT NULL,
    project_id       INTEGER REFERENCES projects(project_id),
    task_id          INTEGER REFERENCES project_tasks(task_id),
    activity_type_id INTEGER REFERENCES activity_types(activity_type_id),
    hours            NUMERIC(5,2) NOT NULL CHECK (hours >= 0 AND hours <= 24),
    is_overtime      BOOLEAN NOT NULL DEFAULT FALSE,
    is_billable      BOOLEAN NOT NULL DEFAULT FALSE,
    billing_rate     NUMERIC(10,2),
    description      TEXT,
    created_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_ts_line UNIQUE (timesheet_id, line_date, project_id, task_id)
);

CREATE INDEX idx_tsl_ts ON timesheet_lines(timesheet_id);
CREATE INDEX idx_tsl_date ON timesheet_lines(line_date);
CREATE INDEX idx_tsl_project ON timesheet_lines(project_id);

-- ============================================================
-- 4. LEAVE / ABSENCE MANAGEMENT
-- ============================================================

CREATE TABLE leave_types (
    leave_type_id       SERIAL PRIMARY KEY,
    type_name           VARCHAR(60) NOT NULL UNIQUE,
    type_code           VARCHAR(10) NOT NULL UNIQUE,
    is_paid             BOOLEAN NOT NULL DEFAULT TRUE,
    requires_approval   BOOLEAN NOT NULL DEFAULT TRUE,
    max_consecutive_days INTEGER,
    requires_attachment BOOLEAN NOT NULL DEFAULT FALSE,
    color_hex           VARCHAR(7) NOT NULL DEFAULT '#3498db',
    is_active           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE leave_policies (
    policy_id               SERIAL PRIMARY KEY,
    leave_type_id           INTEGER NOT NULL REFERENCES leave_types(leave_type_id),
    employment_type         VARCHAR(20),
    annual_entitlement_days NUMERIC(5,2) NOT NULL,
    accrual_frequency       VARCHAR(15) NOT NULL DEFAULT 'annual'
                            CHECK (accrual_frequency IN ('annual','monthly','biweekly','none')),
    max_carryover_days      NUMERIC(5,2) NOT NULL DEFAULT 0,
    max_balance_days        NUMERIC(5,2),
    waiting_period_days     INTEGER NOT NULL DEFAULT 0,
    effective_from          DATE NOT NULL,
    effective_to            DATE,
    is_active               BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE INDEX idx_lp_type ON leave_policies(leave_type_id);

CREATE TABLE leave_balances (
    balance_id       BIGSERIAL PRIMARY KEY,
    employee_id      INTEGER NOT NULL REFERENCES employees(employee_id),
    leave_type_id    INTEGER NOT NULL REFERENCES leave_types(leave_type_id),
    year             INTEGER NOT NULL,
    entitled_days    NUMERIC(5,2) NOT NULL DEFAULT 0,
    carried_over_days NUMERIC(5,2) NOT NULL DEFAULT 0,
    accrued_days     NUMERIC(5,2) NOT NULL DEFAULT 0,
    used_days        NUMERIC(5,2) NOT NULL DEFAULT 0,
    pending_days     NUMERIC(5,2) NOT NULL DEFAULT 0,
    adjusted_days    NUMERIC(5,2) NOT NULL DEFAULT 0,
    available_days   NUMERIC(5,2) GENERATED ALWAYS AS
                     (entitled_days + carried_over_days + accrued_days + adjusted_days - used_days - pending_days) STORED,
    CONSTRAINT uq_emp_leave_year UNIQUE (employee_id, leave_type_id, year)
);

CREATE INDEX idx_lb_emp_year ON leave_balances(employee_id, year);

CREATE TABLE leave_requests (
    request_id          BIGSERIAL PRIMARY KEY,
    employee_id         INTEGER NOT NULL REFERENCES employees(employee_id),
    leave_type_id       INTEGER NOT NULL REFERENCES leave_types(leave_type_id),
    start_date          DATE NOT NULL,
    end_date            DATE NOT NULL,
    start_half_day      BOOLEAN NOT NULL DEFAULT FALSE,
    end_half_day        BOOLEAN NOT NULL DEFAULT FALSE,
    total_days          NUMERIC(5,2) NOT NULL,
    status              VARCHAR(20) NOT NULL DEFAULT 'draft'
                        CHECK (status IN ('draft','submitted','approved','rejected','cancelled')),
    reason              TEXT,
    attachment          BYTEA,
    attachment_filename VARCHAR(255),
    submitted_at        TIMESTAMP,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_leave_dates CHECK (end_date >= start_date)
);

CREATE INDEX idx_lr_emp ON leave_requests(employee_id);
CREATE INDEX idx_lr_status ON leave_requests(status);
CREATE INDEX idx_lr_dates ON leave_requests(start_date, end_date);

CREATE TABLE leave_approvals (
    approval_id    BIGSERIAL PRIMARY KEY,
    request_id     BIGINT NOT NULL REFERENCES leave_requests(request_id),
    approval_level SMALLINT NOT NULL DEFAULT 1,
    approver_id    INTEGER NOT NULL REFERENCES employees(employee_id),
    status         VARCHAR(20) NOT NULL DEFAULT 'pending'
                   CHECK (status IN ('pending','approved','rejected')),
    decision_at    TIMESTAMP,
    comment        TEXT,
    created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_approval_level UNIQUE (request_id, approval_level)
);

CREATE INDEX idx_la_approver_status ON leave_approvals(approver_id, status);

CREATE TABLE holidays (
    holiday_id               SERIAL PRIMARY KEY,
    holiday_name             VARCHAR(100) NOT NULL,
    holiday_date             DATE NOT NULL,
    is_recurring             BOOLEAN NOT NULL DEFAULT FALSE,
    applies_to_department_id INTEGER REFERENCES departments(department_id),
    is_active                BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_holiday UNIQUE (holiday_date, applies_to_department_id)
);

CREATE INDEX idx_holiday_date ON holidays(holiday_date);

-- ============================================================
-- 5. AUDIT AND NOTIFICATIONS
-- ============================================================

CREATE TABLE audit_log (
    audit_id         BIGSERIAL PRIMARY KEY,
    table_name       VARCHAR(60) NOT NULL,
    record_id        BIGINT NOT NULL,
    action           VARCHAR(10) NOT NULL CHECK (action IN ('INSERT','UPDATE','DELETE')),
    old_values       JSONB,
    new_values       JSONB,
    changed_by_login VARCHAR(60) NOT NULL,
    changed_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    ip_address       INET
);

CREATE INDEX idx_audit_table_record ON audit_log(table_name, record_id);
CREATE INDEX idx_audit_user ON audit_log(changed_by_login);
CREATE INDEX idx_audit_date ON audit_log(changed_at DESC);

CREATE TABLE notifications (
    notification_id       BIGSERIAL PRIMARY KEY,
    recipient_employee_id INTEGER NOT NULL REFERENCES employees(employee_id),
    notification_type     VARCHAR(30) NOT NULL,
    title                 VARCHAR(200) NOT NULL,
    message               TEXT NOT NULL,
    reference_table       VARCHAR(60),
    reference_id          BIGINT,
    is_read               BOOLEAN NOT NULL DEFAULT FALSE,
    is_email_sent         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at            TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_notif_recipient_unread ON notifications(recipient_employee_id) WHERE is_read = FALSE;

-- ============================================================
-- 6. DATABASE VIEWS
-- ============================================================

CREATE VIEW v_employee_directory AS
SELECT e.employee_id, e.employee_code,
       e.first_name || ' ' || e.last_name AS full_name,
       e.first_name, e.last_name,
       e.email, e.phone, e.job_title,
       e.department_id, d.department_name,
       e.manager_id,
       m.first_name || ' ' || m.last_name AS manager_name,
       e.employment_type, e.hire_date, e.is_active
FROM employees e
JOIN departments d ON d.department_id = e.department_id
LEFT JOIN employees m ON m.employee_id = e.manager_id;

CREATE VIEW v_timesheet_summary AS
SELECT t.timesheet_id, t.employee_id,
       e.first_name || ' ' || e.last_name AS employee_name,
       e.department_id, d.department_name,
       tp.period_id, tp.period_start, tp.period_end, tp.period_type,
       t.status, t.total_regular_hours, t.total_overtime_hours, t.total_break_hours,
       t.submitted_at, t.approved_at,
       t.approved_by,
       a.first_name || ' ' || a.last_name AS approved_by_name,
       t.rejection_reason, t.created_at, t.updated_at
FROM timesheets t
JOIN employees e ON e.employee_id = t.employee_id
JOIN departments d ON d.department_id = e.department_id
JOIN timesheet_periods tp ON tp.period_id = t.period_id
LEFT JOIN employees a ON a.employee_id = t.approved_by;

CREATE VIEW v_leave_request_full AS
SELECT lr.request_id, lr.employee_id,
       e.first_name || ' ' || e.last_name AS employee_name,
       e.department_id, d.department_name,
       lr.leave_type_id, lt.type_name AS leave_type, lt.color_hex,
       lr.start_date, lr.end_date, lr.total_days,
       lr.start_half_day, lr.end_half_day,
       lr.status, lr.reason, lr.submitted_at,
       lr.created_at, lr.updated_at
FROM leave_requests lr
JOIN employees e ON e.employee_id = lr.employee_id
JOIN departments d ON d.department_id = e.department_id
JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id;

CREATE VIEW v_project_hours_summary AS
SELECT p.project_id, p.project_code, p.project_name,
       p.client_id, c.client_name,
       p.status AS project_status,
       p.budget_hours, p.budget_amount,
       COALESCE(SUM(tl.hours), 0) AS total_hours,
       COALESCE(SUM(CASE WHEN tl.is_billable THEN tl.hours ELSE 0 END), 0) AS billable_hours,
       COALESCE(SUM(CASE WHEN NOT tl.is_billable THEN tl.hours ELSE 0 END), 0) AS non_billable_hours,
       COALESCE(SUM(CASE WHEN tl.is_billable
           THEN tl.hours * COALESCE(tl.billing_rate, p.default_billing_rate, 0)
           ELSE 0 END), 0) AS billable_amount
FROM projects p
LEFT JOIN clients c ON c.client_id = p.client_id
LEFT JOIN timesheet_lines tl ON tl.project_id = p.project_id
GROUP BY p.project_id, p.project_code, p.project_name, p.client_id,
         c.client_name, p.status, p.budget_hours, p.budget_amount;

CREATE VIEW v_current_clock_status AS
SELECT e.employee_id,
       e.first_name || ' ' || e.last_name AS full_name,
       e.department_id, d.department_name,
       ce.clock_entry_id, ce.clock_in,
       ROUND(EXTRACT(EPOCH FROM (NOW() - ce.clock_in))/3600, 2) AS hours_elapsed
FROM clock_entries ce
JOIN employees e ON e.employee_id = ce.employee_id
JOIN departments d ON d.department_id = e.department_id
WHERE ce.clock_out IS NULL;

CREATE VIEW v_leave_balance_current AS
SELECT lb.balance_id, lb.employee_id,
       e.first_name || ' ' || e.last_name AS employee_name,
       e.department_id, d.department_name,
       lb.leave_type_id, lt.type_name,
       lb.entitled_days, lb.carried_over_days, lb.accrued_days,
       lb.used_days, lb.pending_days, lb.adjusted_days,
       lb.available_days, lb.year
FROM leave_balances lb
JOIN employees e ON e.employee_id = lb.employee_id
JOIN departments d ON d.department_id = e.department_id
JOIN leave_types lt ON lt.leave_type_id = lb.leave_type_id
WHERE lb.year = EXTRACT(YEAR FROM CURRENT_DATE);

-- ============================================================
-- 7. SEED DATA
-- ============================================================

-- Activity Types
INSERT INTO activity_types (type_name, is_billable_default) VALUES
    ('Development', TRUE),
    ('Design', TRUE),
    ('Meeting', FALSE),
    ('Administration', FALSE),
    ('Support', TRUE),
    ('Training', FALSE),
    ('Travel', FALSE),
    ('Code Review', TRUE),
    ('Testing/QA', TRUE),
    ('Documentation', FALSE);

-- Leave Types
INSERT INTO leave_types (type_name, type_code, is_paid, requires_approval, max_consecutive_days, requires_attachment, color_hex) VALUES
    ('Paid Time Off',    'PTO',   TRUE,  TRUE,  15, FALSE, '#2ecc71'),
    ('Sick Leave',       'SICK',  TRUE,  TRUE,  NULL, TRUE,  '#e74c3c'),
    ('Personal Day',     'PERS',  TRUE,  TRUE,  2,   FALSE, '#9b59b6'),
    ('Bereavement',      'BRV',   TRUE,  TRUE,  5,   FALSE, '#34495e'),
    ('Jury Duty',        'JURY',  TRUE,  TRUE,  NULL, TRUE,  '#f39c12'),
    ('Parental Leave',   'PARNT', TRUE,  TRUE,  NULL, TRUE,  '#1abc9c'),
    ('Unpaid Leave',     'UNPD',  FALSE, TRUE,  NULL, FALSE, '#95a5a6'),
    ('Work From Home',   'WFH',   TRUE,  TRUE,  NULL, FALSE, '#3498db'),
    ('Compensatory Off', 'COMP',  TRUE,  TRUE,  NULL, FALSE, '#e67e22');

-- Overtime Rules
INSERT INTO overtime_rules (rule_name, daily_threshold_hours, weekly_threshold_hours, multiplier, effective_from) VALUES
    ('Standard Weekly OT', NULL, 40.00, 1.50, '2024-01-01'),
    ('Standard Daily OT',  8.00, NULL,  1.50, '2024-01-01'),
    ('Weekend Double',      NULL, NULL,  2.00, '2024-01-01');

-- System Configuration
INSERT INTO system_config (config_key, config_value, description) VALUES
    ('work_week_start',         'monday',        'Day the work week starts'),
    ('default_period_type',     'weekly',         'Default timesheet period type'),
    ('overtime_calc_method',    'weekly',         'How overtime is calculated: weekly or daily'),
    ('fiscal_year_start_month', '1',              'Fiscal year start month (1=January)'),
    ('timezone',                'Asia/Kolkata',   'Default system timezone'),
    ('company_name',            'Your Company',   'Company name for reports'),
    ('work_hours_per_day',      '8',              'Standard work hours per day'),
    ('break_minutes_default',   '60',             'Default break time in minutes'),
    ('auto_clock_out_hours',    '12',             'Auto clock-out after N hours if forgotten'),
    ('leave_year_start_month',  '1',              'Leave year start month');

-- Holidays (India 2026 - sample)
INSERT INTO holidays (holiday_name, holiday_date, is_recurring) VALUES
    ('New Year''s Day',       '2026-01-01', TRUE),
    ('Republic Day',          '2026-01-26', TRUE),
    ('Holi',                  '2026-03-10', FALSE),
    ('Good Friday',           '2026-04-03', FALSE),
    ('May Day',               '2026-05-01', TRUE),
    ('Independence Day',      '2026-08-15', TRUE),
    ('Gandhi Jayanti',        '2026-10-02', TRUE),
    ('Dussehra',              '2026-10-20', FALSE),
    ('Diwali',                '2026-11-08', FALSE),
    ('Christmas',             '2026-12-25', TRUE);

-- ============================================================
-- 8. UPDATED_AT TRIGGER (generic for all tables with updated_at)
-- ============================================================

CREATE OR REPLACE FUNCTION fn_set_updated_at() RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_departments_updated BEFORE UPDATE ON departments
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_employees_updated BEFORE UPDATE ON employees
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_projects_updated BEFORE UPDATE ON projects
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_timesheets_updated BEFORE UPDATE ON timesheets
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_leave_requests_updated BEFORE UPDATE ON leave_requests
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_system_config_updated BEFORE UPDATE ON system_config
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();
