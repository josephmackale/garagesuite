# GarageSuite (iWebGarage)

GarageSuite is a multi-tenant Garage Management System (GMS) built with Laravel,
designed to model real-world automotive and insurance repair workflows.

It is implemented as a SaaS-style platform focused on deterministic business processes,
strict tenant isolation, and server-enforced operational rules.

---

## 🚗 Why GarageSuite Exists

Many independent garages operate with fragmented tools:

- Job tracking lives in notebooks or spreadsheets
- Insurance approvals lose alignment with actual repairs
- Scope changes happen without traceability
- Receivables and follow-ups are difficult to monitor
- Shared systems risk cross-customer data exposure

GarageSuite solves this by translating operational reality into structured,
enforceable software workflows.

---

## 🏗 Key Engineering Concepts

### Multi-Tenant Data Isolation

All domain tables are scoped by `garage_id`, ensuring complete tenant separation.

Every query executes within tenant context to prevent cross-garage data access —
a core requirement for SaaS safety.

---

### Deterministic Workflow Engine

Jobs follow a strict lifecycle:

**Intake → Inspection → Quotation → Approval → Repair → Completion → Settlement**

Stages cannot be skipped.

Workflow state is computed exclusively from persisted database truth —
no client-side flags are trusted.

This prevents premature transitions and guarantees auditability.

---

### Approval-Pack Integrity Model

Approved quotations generate immutable **Approval Packs**.

Repair sessions clone approved items 1:1, ensuring:

- No execution outside insurer-approved scope
- Traceable decision history
- Locked repair authorization boundaries

---

### Server-Driven UI Rehydration

After workflow transitions, UI components are re-rendered from server partials to ensure:

- No frontend state drift
- Single source of truth
- Predictable behavior after refresh or async actions

---

## 👥 Role Model

### Super Admin
- Manages garages and users
- Configures infrastructure services (e.g., SMS provider)
- Monitors usage and supports tenants via impersonation

### Garage Staff
- Manage customers, vehicles, and jobs
- Execute workflow stages
- Generate invoices and reminders
- No access to infrastructure credentials

---

## 📩 Centralized SMS Architecture

SMS providers are configured at system level:

- Credentials never exposed to tenants
- Usage is quota-controlled per garage
- All SMS activity logged for audit and cost governance

---

## 🛠 Technology Stack

- Laravel (PHP)
- MySQL (relational workflow modeling)
- Blade + Alpine.js
- Tailwind CSS
- Laravel Queues / Cron Workers
- Ubuntu VPS deployment

---

## 📌 Current Development Focus

- Insurance workflow hardening
- Repair-session enforcement
- Receivables tracking engine
- Operational reporting dashboards

---

## ⚙ Local Setup

```bash
git clone https://github.com/josephmackale/garagesuite.git
cd garagesuite
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
