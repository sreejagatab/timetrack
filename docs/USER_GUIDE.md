# TimeTrack User Guide

## Getting Started

### Login
1. Open your browser and go to the TimeTrack URL
2. Enter your username and password
3. Click **Login**

Your administrator will provide your credentials. Default password for new accounts is `Welcome@123` — change it immediately after first login via the user menu (top-right) → **Change Password**.

---

## Navigation

The main menu has 8 sections:

| Menu | Description |
|------|-------------|
| **Dashboard** | Overview with charts and KPIs |
| **Time** | Clock In/Out, Clock History |
| **Timesheets** | Create, submit, and view timesheets |
| **Projects** | Projects, clients, tasks, team members |
| **Leave** | Request leave, view balance, leave calendar |
| **Reports** | Employee directory, project hours, charts |
| **Admin** | Employee management, system configuration (admin only) |
| **Security** | User/group management (admin only) |

---

## Clock In/Out

### Clock In
1. Go to **Time → Clock In/Out**
2. Your current status is displayed (Clocked In / Not Clocked In)
3. Click the green **CLOCK IN** button
4. The system records the current time and your IP address

### Clock Out
1. Go to **Time → Clock In/Out**
2. Click the red **CLOCK OUT** button
3. Your work hours are automatically calculated

### View Clock History
- **Time → Clock History** shows all your clock entries
- You can filter by date range
- Export to Excel/PDF using the Export button

### Notes
- You cannot clock in if already clocked in (double-punch prevention)
- Manual corrections can be made by your manager or HR
- All manual edits are recorded in the audit log

---

## Timesheets

### Create a Timesheet
1. Go to **Timesheets → New Timesheet**
2. Select the **Period** (weekly/biweekly)
3. The form shows the timesheet header

### Add Time Entries
For each day:
1. Select the **Project** (dropdown shows projects you're assigned to)
2. Select the **Task** (filtered by selected project)
3. Select the **Activity Type** (Development, Meeting, etc.)
4. Enter **Hours** worked
5. Check **Billable** if applicable
6. Add a **Description** of work done

### Submit for Approval
1. Review all entries
2. Click **Submit for Approval**
3. Your manager receives a notification
4. Track status: Draft → Submitted → Approved/Rejected

### Auto-Populate from Clock Entries
If you use Clock In/Out daily, you can auto-populate timesheet lines:
1. Open your timesheet
2. Click **Auto-populate from Clock**
3. The system creates line items from your clock entries

### Timesheet Statuses
| Status | Meaning |
|--------|---------|
| **Draft** | Not yet submitted — you can edit freely |
| **Submitted** | Waiting for manager approval |
| **Approved** | Approved — locked for editing |
| **Rejected** | Rejected with reason — needs correction |
| **Revision Requested** | Manager requests changes — edit and resubmit |

---

## Leave Management

### Check Your Leave Balance
1. Go to **Leave → Leave Balances**
2. View entitled, used, pending, and available days for each leave type

### Request Leave
1. Go to **Leave → Request Leave**
2. Select **Leave Type** (PTO, Sick Leave, WFH, etc.)
3. Set **Start Date** and **End Date**
4. Check **Half Day** options if starting or ending mid-day
5. Enter **Reason**
6. Attach documents if required (e.g., doctor's note for sick leave)
7. Click **Save** (creates as Draft), then **Submit**

### Leave Request Workflow
1. **Draft** — You can edit or cancel
2. **Submitted** — Manager is notified
3. **Approved** — Balance updated (pending → used)
4. **Rejected** — Balance restored, reason provided
5. **Cancelled** — You can cancel before approval

### View Leave Calendar
- **Leave → Leave Calendar** shows all approved and pending leave
- Color-coded by leave type
- Helps check team availability before requesting

### Leave Types
| Type | Code | Paid | Requires Approval |
|------|------|------|-------------------|
| Paid Time Off | PTO | Yes | Yes |
| Sick Leave | SICK | Yes | Yes (attachment may be required) |
| Personal Day | PERS | Yes | Yes |
| Bereavement | BRV | Yes | Yes |
| Jury Duty | JURY | Yes | Yes |
| Parental Leave | PARNT | Yes | Yes |
| Unpaid Leave | UNPD | No | Yes |
| Work From Home | WFH | Yes | Yes |
| Compensatory Off | COMP | Yes | Yes |

---

## Projects

### View Projects
- **Projects → Projects List** shows all projects you have access to
- Click a project to view details, tasks, and team members

### Log Time to Projects
- When creating timesheet entries, select the Project and Task
- Hours are tracked against the project budget
- Billable hours contribute to client billing reports

### View Project Hours
- **Reports → Project Hours** shows total hours per project
- Includes billable vs non-billable breakdown
- Budget vs actual comparison

---

## Reports & Charts

### Available Views
| Report | Location | Description |
|--------|----------|-------------|
| Employee Directory | Reports → Employee Directory | All employees with department and contact info |
| Timesheet Summary | Reports → Timesheet Summary | All timesheets with status and hours |
| Project Hours | Reports → Project Hours | Hours and billing per project |
| Leave Requests | Reports → Leave Requests | All leave requests with status |

### Charts
| Chart | Location | Description |
|-------|----------|-------------|
| Hours by Project | Reports → Hours by Project | Bar chart of total hours per project |
| Hours by Department | Reports → Hours by Dept | Bar chart of hours per department |
| Overtime Trend | Reports → Overtime Trend | Line chart of overtime over time |
| Leave by Type | Reports → Leave by Type | Pie chart of leave distribution |
| Billable Ratio | Reports → Billable Ratio | Billable vs non-billable split |

### Exporting Data
Every grid supports export to:
- **PDF** — Formatted printable document
- **Excel** — Spreadsheet with data
- **CSV** — Comma-separated values
- **XML** — Structured data format
- **JSON** — API-friendly format

Click the **Export** dropdown in any grid toolbar.

---

## Tips & Best Practices

1. **Clock in every day** — It helps auto-populate timesheets
2. **Submit timesheets weekly** — Don't let them pile up
3. **Check leave balance before requesting** — Prevents rejection
4. **Use meaningful descriptions** — Helps in reporting and billing
5. **Review the audit log** — If something looks wrong, check who changed what

---

## Getting Help

Contact your system administrator for:
- Password reset
- Role changes
- Leave balance corrections
- New project/client setup
- System configuration changes
