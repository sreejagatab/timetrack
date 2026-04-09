# TimeTrack SaaS Platform Plan

## Vision
Transform TimeTrack from a single-tenant ScriptCase application into a multi-tenant SaaS platform at **timetracker.jagatab.uk**, generating recurring revenue from organizations needing time tracking, project billing, and leave management.

---

## Revenue Model

### Pricing Tiers

| Plan | Price/mo | Users | Features |
|------|----------|-------|----------|
| **Free** | $0 | Up to 5 | Clock in/out, basic timesheets, 1 project |
| **Starter** | $29/mo | Up to 15 | + Leave management, 5 projects, basic reports |
| **Professional** | $79/mo | Up to 50 | + Billing, charts, calendars, unlimited projects, API access |
| **Enterprise** | $199/mo | Up to 200 | + Custom roles, audit trail, SSO, priority support, SLA |
| **Custom** | Contact | 200+ | White-label, on-premise option, dedicated support |

### Add-ons
- Extra users: $3/user/month
- API access: $19/month (Starter plan)
- Custom reports: $49/month
- Data export/migration: One-time $199
- White-label: $99/month

### Revenue Projections (Year 1)
| Month | Free | Starter | Pro | Enterprise | MRR |
|-------|------|---------|-----|------------|-----|
| 1-3 | 50 | 5 | 2 | 0 | $303 |
| 4-6 | 150 | 15 | 5 | 1 | $1,039 |
| 7-9 | 300 | 30 | 12 | 3 | $2,463 |
| 10-12 | 500 | 50 | 25 | 5 | $4,420 |
| **Year 1 Total** | | | | | **~$25K ARR** |

---

## Technical Architecture for SaaS

### Multi-Tenancy Approach

**Option A: Database-per-tenant (Recommended for ScriptCase)**
```
┌─────────────────────────────────────┐
│          Load Balancer (Nginx)       │
│         timetracker.jagatab.uk       │
├─────────────────────────────────────┤
│        Tenant Router (PHP)           │
│   Routes by subdomain/URL path      │
├──────┬──────┬──────┬──────┬────────┤
│ T1   │ T2   │ T3   │ ...  │ Tn     │
│ DB   │ DB   │ DB   │      │ DB     │
└──────┴──────┴──────┴──────┴────────┘
│          PostgreSQL Server           │
│   timetrack_tenant1                  │
│   timetrack_tenant2                  │
│   timetrack_tenant3                  │
└──────────────────────────────────────┘
```

**Option B: Shared database with tenant_id column**
- Add `tenant_id` to every table
- Row-level security via PostgreSQL RLS
- More efficient but complex migration

### Recommended: Hybrid Approach
1. **Single ScriptCase deployment** serving all tenants
2. **Dynamic database connection** — each tenant gets their own PostgreSQL database
3. **Tenant management app** — admin panel to create/manage tenants
4. **Subdomain routing** — `acme.timetracker.jagatab.uk`

---

## SaaS Platform Components to Build

### Phase 1: Landing Page & Signup (Week 1-2)
- Marketing landing page at `timetracker.jagatab.uk`
- Pricing page with tier comparison
- Signup form → creates tenant (database + admin user)
- Stripe/Razorpay integration for payments
- Technology: Next.js or simple HTML/CSS + PHP

### Phase 2: Tenant Provisioning (Week 3-4)
- **Tenant Management Database** (separate from app databases)
  ```sql
  CREATE TABLE tenants (
      tenant_id SERIAL PRIMARY KEY,
      company_name VARCHAR(200),
      subdomain VARCHAR(50) UNIQUE,
      plan VARCHAR(20) DEFAULT 'free',
      db_name VARCHAR(100),
      admin_email VARCHAR(200),
      created_at TIMESTAMP DEFAULT NOW(),
      status VARCHAR(20) DEFAULT 'active',
      trial_expires_at TIMESTAMP,
      stripe_customer_id VARCHAR(100)
  );
  ```
- **Auto-provisioning script**: On signup, auto-create:
  - PostgreSQL database from `timetrack_ddl.sql` template
  - Admin user account
  - Default departments, leave types, holidays
  - ScriptCase connection (or dynamic connection switching)
- **14-day free trial** for all plans

### Phase 3: Billing & Subscription (Week 5-6)
- Stripe integration for recurring billing
- Plan upgrade/downgrade
- Usage tracking (active users, projects, storage)
- Invoice generation
- Dunning (failed payment handling)

### Phase 4: Onboarding & Self-Service (Week 7-8)
- Setup wizard for new tenants:
  1. Company info
  2. Department structure
  3. Import employees (CSV upload)
  4. Configure leave policies
  5. Set holidays
- Help center / knowledge base
- In-app tooltips and tour

### Phase 5: Advanced Features (Month 3-6)
- **API**: REST API for integrations (Slack, MS Teams, Zapier)
- **SSO**: SAML/OAuth for enterprise customers
- **Mobile app**: React Native or PWA wrapper
- **Webhooks**: Notify external systems on events
- **Custom reports builder**: Drag-and-drop report creation
- **Multi-language**: i18n support (English, Hindi, Spanish, etc.)
- **Slack bot**: `/clockin`, `/clockout`, `/leave 3 days PTO`

---

## Infrastructure

### Hosting
| Component | Provider | Cost/mo |
|-----------|----------|---------|
| VPS (App Server) | Hetzner/DigitalOcean | $20-50 |
| PostgreSQL | Same VPS or managed (Supabase) | $0-25 |
| Domain | Cloudflare | $10/yr |
| SSL | Cloudflare (free) | $0 |
| CDN | Cloudflare | $0 |
| Email (transactional) | Resend/SendGrid | $0-20 |
| Backup storage | Backblaze B2 | $5 |
| **Total** | | **$25-100/mo** |

### Scaling Strategy
| Stage | Users | Infrastructure |
|-------|-------|----------------|
| MVP | 1-50 tenants | Single VPS + PostgreSQL |
| Growth | 50-200 tenants | 2 VPS + managed PostgreSQL |
| Scale | 200+ tenants | Kubernetes + managed DB + CDN |

---

## Marketing Strategy

### Channels
1. **SEO**: Blog content about time tracking, employee management, billing
2. **Product Hunt**: Launch for initial traction
3. **LinkedIn**: Targeted ads to HR managers and small business owners
4. **Integrations marketplace**: Zapier, Slack App Directory
5. **Referral program**: Free month for both referrer and referee
6. **Comparison pages**: "TimeTrack vs Toggl", "TimeTrack vs Clockify"

### Key Differentiators
- **All-in-one**: Clock, timesheets, projects, leave — not just time tracking
- **Self-hosted option**: For enterprises wanting data control
- **India-first**: Holidays, leave policies, and currency suited for Indian companies
- **Affordable**: Significantly cheaper than Toggl/Harvest/BambooHR
- **Customizable**: Built on ScriptCase — easy to add custom fields/reports

---

## Competitive Analysis

| Feature | TimeTrack | Toggl | Clockify | BambooHR | Harvest |
|---------|-----------|-------|----------|----------|---------|
| Clock In/Out | Yes | Yes | Yes | No | No |
| Timesheets | Yes | Yes | Yes | No | Yes |
| Project Billing | Yes | Yes (Track) | Limited | No | Yes |
| Leave Management | Yes | No | No | Yes | No |
| Employee Management | Yes | No | No | Yes | No |
| Holiday Calendar | Yes | No | No | Yes | No |
| Approval Workflow | Yes | No | Yes | Yes | Yes |
| Self-hosted | Yes | No | No | No | No |
| Free tier | 5 users | 5 users | Unlimited | No | No |
| Starting price | $29/mo | $9/user | $3.99/user | $6/user | $10.80/user |

---

## Legal Requirements

1. **Privacy Policy** — GDPR/CCPA compliant data handling
2. **Terms of Service** — SaaS subscription terms
3. **Data Processing Agreement** — For enterprise customers
4. **Cookie Policy** — For the marketing site
5. **SLA** — 99.9% uptime guarantee for Enterprise plan
6. **Data retention policy** — How long data is kept after account cancellation

---

## Timeline

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| MVP Landing + Signup | 2 weeks | Marketing site, signup flow, auto-provisioning |
| Billing Integration | 2 weeks | Stripe payments, plan management |
| Onboarding Wizard | 2 weeks | Self-service setup for new tenants |
| Beta Launch | — | Invite 10-20 beta customers |
| Public Launch | 1 week | Product Hunt, LinkedIn, SEO |
| API + Integrations | 4 weeks | REST API, Slack bot, Zapier |
| Mobile App | 4 weeks | PWA or React Native |
| Scale | Ongoing | Performance, new features, support |

**Total time to revenue: ~6-8 weeks from today**
