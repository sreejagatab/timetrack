# TimeTrack - Enterprise Time Tracking System

A production-ready, full-featured Time Tracking System built with **ScriptCase v9** and **PostgreSQL 14**. Designed for medium-sized organizations (20-100 users) with multi-department, role-based access, and comprehensive reporting.

## Live Demo

- **Application**: [https://timetracker.jagatab.uk](https://timetracker.jagatab.uk/scriptcase/app/project/menu_main/)
- **Login**: [https://timetracker.jagatab.uk/scriptcase/app/project/app_Login/](https://timetracker.jagatab.uk/scriptcase/app/project/app_Login/)
- **Credentials**: `admin` / `admin`

## Documentation

- [User Guide](docs/USER_GUIDE.md) — How to use the system (clock in/out, timesheets, leave, projects)
- [Admin Guide](docs/ADMIN_GUIDE.md) — System administration, user management, configuration

---

## Features

### Clock In/Out & Attendance
- One-click web-based clock in/out
- Real-time "Who's In" dashboard
- Manual entry/correction with audit trail
- Auto-calculation of gross/net work hours via database triggers
- Break time tracking
- IP address logging for each punch

### Weekly Timesheets
- Weekly/biweekly/monthly timesheet periods
- Line-item entry per day/project/task
- Auto-populate from clock entries
- Submit → Approve → Reject → Revision workflow
- Multi-level approval (Manager → HR for extended periods)
- Overtime auto-detection based on configurable rules

### Project-Based Time Tracking
- Client and project management
- Hierarchical task breakdown
- Team member assignment with role-based access
- Billable vs non-billable hour tracking
- Billing rate cascade: Client → Project → Member override
- Budget tracking (hours and amount)

### Leave & Absence Management
- 9 configurable leave types (PTO, Sick, Personal, WFH, Comp Off, etc.)
- Policy engine with accrual, entitlement, and carryover rules
- Balance checking with insufficient-balance prevention
- Overlap detection for conflicting requests
- Multi-level approval workflow
- Visual leave calendar (color-coded by type)
- Holiday calendar management

### Dashboards & Charts
- Main dashboard with KPI widgets
- Manager dashboard for team oversight
- 5 interactive charts:
  - Hours by Project (Bar)
  - Hours by Department (Bar)
  - Overtime Trend (Line)
  - Leave by Type (Pie)
  - Billable vs Non-Billable Ratio (Column)
- Export to PDF, Excel, CSV, XML, JSON

### Security & Access Control
- 7 security roles: Admin, HR Manager, Dept Manager, Team Lead, Employee, Project Manager, Finance
- Row-level data security (employees see only their own data)
- bcrypt password hashing
- Brute force protection
- Session management
- Audit trail on all sensitive operations

### Navigation
- Responsive horizontal menu with 8 sections and 48+ items
- Dashboard, Time, Timesheets, Projects, Leave, Reports, Admin, Security
- User profile menu with password change and logout
- Search functionality across applications

---

## Architecture

```
┌─────────────────────────────────────────────────┐
│                 Browser (Client)                 │
├─────────────────────────────────────────────────┤
│            ScriptCase v9 (PHP 8.2)              │
│  ┌──────────┬──────────┬──────────┬───────────┐ │
│  │  Forms   │  Grids   │  Charts  │ Dashboard │ │
│  │  (22)    │  (28)    │  (5)     │ (2)       │ │
│  ├──────────┼──────────┼──────────┼───────────┤ │
│  │ Calendar │   Menu   │  Blank   │ Security  │ │
│  │  (2)     │  (1)     │  (1)     │ (19)      │ │
│  └──────────┴──────────┴──────────┴───────────┘ │
│            Apache 2.4 (Port 8093)               │
├─────────────────────────────────────────────────┤
│             PostgreSQL 14 Database              │
│  22 Tables │ 6 Views │ 7 Triggers │ Seed Data  │
└─────────────────────────────────────────────────┘
```

### Technology Stack
| Component | Technology | Version |
|-----------|-----------|---------|
| Backend | ScriptCase (PHP RAD) | v9.13.017 |
| Language | PHP | 8.2 |
| Database | PostgreSQL | 14 |
| Web Server | Apache | 2.4 |
| Charts | Chart.js (via ScriptCase) | Built-in |
| Authentication | ScriptCase Security Module | Group-based |

---

## Database Schema

### Tables (22)
| Category | Tables |
|----------|--------|
| **Core** | `departments`, `employees`, `employee_sec_link`, `system_config` |
| **Clock/Timesheet** | `clock_entries`, `timesheet_periods`, `timesheets`, `timesheet_lines`, `activity_types`, `overtime_rules` |
| **Projects** | `clients`, `projects`, `project_tasks`, `project_members` |
| **Leave** | `leave_types`, `leave_policies`, `leave_balances`, `leave_requests`, `leave_approvals`, `holidays` |
| **System** | `audit_log`, `notifications` |
| **Security** | `sec_users`, `sec_groups`, `sec_users_groups`, `sec_apps`, `sec_groups_apps`, `sec_settings` |

### Views (6)
| View | Purpose |
|------|---------|
| `v_employee_directory` | Employee listing with department and manager names |
| `v_timesheet_summary` | Timesheet overview with employee and period info |
| `v_leave_request_full` | Leave requests with employee and leave type details |
| `v_project_hours_summary` | Project hours with billable amounts |
| `v_current_clock_status` | Real-time who's clocked in |
| `v_leave_balance_current` | Current year leave balances |

### Triggers (7)
- `fn_clock_entry_calc` — Auto-computes gross/net minutes on clock entries
- `fn_set_updated_at` — Auto-updates `updated_at` timestamp on 6 tables

---

## Applications (80 total)

| Type | Count | Examples |
|------|-------|---------|
| Forms | 22 | `form_public_employees`, `form_public_timesheets`, `form_public_leave_requests` |
| Grids | 28 | `grid_public_employees`, `grid_public_v_timesheet_summary`, `grid_public_v_employee_directory` |
| Charts | 5 | `chart_hours_by_project`, `chart_billable_ratio`, `chart_overtime_trend` |
| Dashboards | 2 | `dash_main`, `dash_manager` |
| Calendars | 2 | `calendar_public_holidays`, `calendar_public_leave_requests` |
| Menu | 1 | `menu_main` (48 items across 8 sections) |
| Blank | 1 | `ctrl_clock_inout` (Clock In/Out widget) |
| Security | 19 | Login, user management, group management, 2FA, etc. |

---

## Installation

### Prerequisites
- Windows 10/11 or Windows Server
- PostgreSQL 14+ installed and running
- ScriptCase v9.13+ installed (trial or licensed)

### Step 1: Database Setup

```bash
# Connect to PostgreSQL as superuser
psql -U postgres -h localhost

# Create database and user
CREATE USER timetrack_app WITH PASSWORD 'your_password';
CREATE DATABASE timetrack_db OWNER timetrack_app;
\c timetrack_db
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

# Run the DDL script
\i H:/scriptcase/timetrack_ddl.sql

# Load test data (optional)
\i H:/scriptcase/test_data.sql
```

### Step 2: ScriptCase Project Setup

1. Open ScriptCase IDE: `http://127.0.0.1:8093/scriptcase/`
2. Login with IDE credentials
3. Open the `project` project
4. Verify connection `conn_timetrack` points to your PostgreSQL
5. Generate all applications (Select All → Generate)

### Step 3: Access the Application

- **URL**: `http://127.0.0.1:8093/scriptcase/app/project/menu_main/`
- **Default admin**: `admin` / `admin`
- **Test users**: `amit.patel`, `priya.sharma`, `rajesh.kumar`, `ananya.gupta` (password: check sec_users table)

---

## User Guide

### For Employees

#### Clock In/Out
1. Navigate to **Time → Clock In/Out**
2. Click the green **CLOCK IN** button when starting work
3. Click the red **CLOCK OUT** button when leaving
4. View today's entries and total hours below the button

#### Submit Timesheets
1. Go to **Timesheets → New Timesheet**
2. Select the timesheet period
3. Add line items for each day with project, task, and hours
4. Click **Submit for Approval**
5. Track status in **Timesheets → My Timesheets**

#### Request Leave
1. Go to **Leave → Request Leave**
2. Select leave type, start date, end date
3. Check half-day options if applicable
4. Add reason and any required attachments
5. Submit — your manager will be notified

#### View Leave Balance
- **Leave → Leave Balances** shows your current entitlements, used, pending, and available days

### For Managers

#### Approve Timesheets
1. **Timesheets → Timesheet Summary** shows all submitted timesheets
2. Click on a timesheet to review
3. Approve, Reject, or Request Revision

#### Approve Leave
1. **Leave → Leave Approvals** shows pending requests
2. Review dates, reason, and balance
3. Approve or Reject with comments

#### Team Overview
- **Dashboard** shows team KPIs
- **Reports → Employee Directory** for team listing
- **Time → Clock History** for attendance monitoring

### For Admins

#### Manage Employees
1. **Admin → Employees** — Add, edit, deactivate employees
2. When adding an employee, a security user is auto-created
3. Default password: `Welcome@123`

#### Configure Leave Policies
1. **Admin → Leave Types** — Define available leave types
2. **Admin → Leave Policies** — Set entitlements per employment type
3. **Admin → Holidays** — Manage company holidays

#### System Configuration
- **Admin → System Config** — Key/value settings (timezone, work hours, etc.)
- **Admin → Overtime Rules** — Configure daily/weekly thresholds
- **Admin → Audit Log** — View all system changes

---

## Security Roles

| Role | Access Level |
|------|-------------|
| **Admin** | Full access to all applications and data |
| **HR_Manager** | Manage employees, leave policies, view all timesheets and reports |
| **Dept_Manager** | Approve timesheets/leave for own department, view department reports |
| **Team_Lead** | Approve timesheets for direct reports, view team data |
| **Employee** | Own clock in/out, timesheets, leave requests, view own data |
| **Project_Manager** | Manage projects/tasks, view project hours, billing reports |
| **Finance** | View billing reports, project costs, payroll reports (read-only) |

---

---

## Deployment

### Local Development
The system runs on `http://127.0.0.1:8093/` via the ScriptCase built-in Apache server.

### Production Deployment
1. Use ScriptCase's **Deploy** feature to export applications
2. Configure a production Apache/Nginx server with PHP 8.2
3. Set up a production PostgreSQL database
4. Configure HTTPS (SSL/TLS)
5. Set secure session cookies

### Cloudflare Tunnel (for public access)
```bash
cloudflared tunnel --url http://127.0.0.1:8093
# Or with custom domain:
cloudflared tunnel route dns timetrack timetracker.yourdomain.com
```

---

## Troubleshooting

| Issue | Solution |
|-------|---------|
| Login fails | Check `sec_users` table — verify login exists and `active = 'Y'` or `'S'` |
| Grid shows no data | Check database connection in ScriptCase IDE |
| Chart shows empty | Verify test data is loaded (`test_data.sql`) |
| Apps show "outdated" | Select All → Generate in ScriptCase IDE |
| SMTP errors | Configure real SMTP settings in Security Module → Settings |

---

## License

This project was built using ScriptCase (trial version). For production use, a valid ScriptCase license is required. Visit [scriptcase.net](https://www.scriptcase.net/) for licensing.

---

## Credits

Built with:
- [ScriptCase](https://www.scriptcase.net/) — PHP RAD Platform
- [PostgreSQL](https://www.postgresql.org/) — Database
- [Playwright](https://playwright.dev/) — Browser automation for app creation
- AI-assisted development with Claude Code
