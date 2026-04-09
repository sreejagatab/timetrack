# TimeTrack Administrator Guide

## System Administration

### ScriptCase IDE Access
- **URL**: `http://127.0.0.1:8093/scriptcase/`
- **Credentials**: `admin` / `admin` (IDE login, separate from app login)

### Application Access
- **URL**: `http://127.0.0.1:8093/scriptcase/app/project/menu_main/`
- **Admin login**: `admin` / `admin`

---

## User Management

### Adding a New Employee
1. Go to **Admin → Employees** → Click **New**
2. Fill in: Employee Code, First Name, Last Name, Email, Department, Job Title, Hire Date
3. Select Employment Type (full_time, part_time, contractor, intern)
4. Set Manager (dropdown of existing employees)
5. Set Hourly Rate and Overtime Eligible flag
6. Click **Save**

**Auto-created on save:**
- A security user account is created (`firstname.lastname`)
- Default password: `Welcome@123`
- Employee is assigned to the "Employee" security group
- An entry in `employee_sec_link` maps the employee to the login

### Deactivating an Employee
1. Edit the employee record
2. Set **Is Active** to unchecked
3. Set **Termination Date**
4. The user can no longer log in

### Changing User Roles
1. Go to **Security → Users/Groups**
2. Find the user
3. Add/remove group assignments (Admin, HR_Manager, Dept_Manager, etc.)

### Resetting Passwords
1. Go to **Security → Users**
2. Edit the user
3. Set a new password
4. Or use the "Forgot Password" flow if SMTP is configured

---

## Leave Configuration

### Leave Types
- **Admin → Leave Types** — Add/edit leave categories
- Each type has: Name, Code, Paid/Unpaid, Color (for calendar), Attachment Required

### Leave Policies
- **Admin → Leave Policies** — Define entitlement rules
- Per leave type and employment type:
  - Annual entitlement days
  - Accrual frequency (annual/monthly/biweekly)
  - Max carryover days
  - Max balance cap
  - Waiting period after hire

### Leave Balance Initialization
For each new year, leave balances must be initialized:
1. Use the `leave_balances` table
2. Insert records for each employee × leave type × year
3. Set `entitled_days` from the policy
4. Set `carried_over_days` from previous year's unused balance (up to max_carryover)

### Holiday Management
- **Admin → Holidays** — Add company holidays
- Set **Is Recurring** for annual holidays (New Year's, Independence Day, etc.)
- Optionally limit to specific departments
- **Admin → Holiday Calendar** — Visual calendar view

---

## Timesheet Configuration

### Timesheet Periods
- **Admin → Timesheet Periods** (via Timesheets menu) — Create weekly/biweekly/monthly periods
- Periods must be created before employees can submit timesheets
- Lock periods to prevent further changes

### Overtime Rules
- **Admin → Overtime Rules** — Configure when overtime kicks in
- Daily threshold (e.g., after 8 hours/day)
- Weekly threshold (e.g., after 40 hours/week)
- Multiplier (1.5x, 2.0x for weekends)
- Can vary by employment type

### Activity Types
- **Admin → Activity Types** — Define work categories
- Each has a "Billable Default" flag
- Pre-loaded: Development, Design, Meeting, Admin, Support, Training, Travel, Code Review, Testing/QA, Documentation

---

## System Configuration

**Admin → System Config** manages key-value settings:

| Key | Description | Default |
|-----|-------------|---------|
| `work_week_start` | Day the work week starts | monday |
| `default_period_type` | Default timesheet period | weekly |
| `overtime_calc_method` | How OT is calculated | weekly |
| `timezone` | Default system timezone | Asia/Kolkata |
| `company_name` | Company name for reports | Your Company |
| `work_hours_per_day` | Standard daily hours | 8 |
| `break_minutes_default` | Default break time | 60 |
| `auto_clock_out_hours` | Auto clock-out threshold | 12 |

---

## Database Maintenance

### Backup
```bash
pg_dump -U timetrack_app -h localhost timetrack_db > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
psql -U timetrack_app -h localhost timetrack_db < backup_20260409.sql
```

### Useful Queries
```sql
-- Active employees count by department
SELECT d.department_name, COUNT(*) FROM employees e
JOIN departments d ON d.department_id = e.department_id
WHERE e.is_active = TRUE GROUP BY d.department_name;

-- Current month overtime
SELECT e.first_name || ' ' || e.last_name, SUM(t.total_overtime_hours)
FROM timesheets t JOIN employees e ON e.employee_id = t.employee_id
JOIN timesheet_periods tp ON tp.period_id = t.period_id
WHERE tp.period_start >= date_trunc('month', CURRENT_DATE)
GROUP BY e.first_name, e.last_name HAVING SUM(t.total_overtime_hours) > 0;

-- Leave balance summary
SELECT * FROM v_leave_balance_current ORDER BY employee_name, type_name;
```

---

## Security

### Row-Level Security
Data filtering is enforced via `onScriptInit` events on grids:
- **Admin/HR_Manager**: See all data
- **Dept_Manager**: See own department's data
- **Team_Lead**: See direct reports' data
- **Employee**: See only own data

### Audit Trail
All sensitive operations are logged in `audit_log`:
- Table name, record ID, action (INSERT/UPDATE/DELETE)
- Old and new values (JSONB)
- User who made the change
- Timestamp and IP address

View at: **Admin → Audit Log**

### Session Security
- Session timeout: 30 minutes (configurable)
- HttpOnly cookies enabled
- CSRF protection available
- XSS prevention via ScriptCase's built-in sanitization

---

## Regenerating Applications

After making changes in the ScriptCase IDE:
1. Open the IDE: `http://127.0.0.1:8093/scriptcase/`
2. Open the `project` project
3. Select all applications (checkbox at top)
4. Click **Generate**
5. Wait for "Code generation successful"

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Can't login to app | Wrong credentials or inactive user | Check `sec_users` table |
| Grid empty | No data or wrong WHERE filter | Check data exists in table |
| Chart empty | No data matching query | Load test data or create records |
| "outdated" status | Apps need regeneration | Generate All in IDE |
| SMTP error on user creation | Email not configured | Configure SMTP in Security Module settings |
| Database connection error | PostgreSQL not running | Start PostgreSQL service |
| Apache not responding | Service stopped | Restart `ApacheScriptcase9php82` service |
