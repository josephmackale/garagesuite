# GarageSuite (iWebGarage)

GarageSuite is a multi-tenant Garage Management System (GMS) built with Laravel,
designed for real-world automotive operations in emerging markets.

It is architected as a SaaS platform with strict tenant isolation,
deterministic workflow enforcement, and server-driven state control.

---

## 🎯 The Problem

Many independent garages struggle with:

- Disorganized job tracking
- Insurance approval misalignment
- Repair scope drift
- Poor receivables visibility
- Shared systems without data isolation

GarageSuite was built to solve these using structured relational modeling
and enforced workflow gates.

---

## 🏗 Core Architecture

### Multi-Tenant Isolation

All domain tables are scoped by `garage_id` to guarantee
complete tenant separation and prevent cross-garage data leakage.

Tenant context is enforced at the query level.

---

### Deterministic Workflow Engine

Insurance and job workflows follow a strict lifecycle:

Intake → Inspection → Quotation → Approval → Repair → Completion → Settlement

Stages cannot be skipped.
Unlock logic is computed server-side from database truth.

No client-side “flag unlocking” is trusted.

---

### Approval-Pack Integrity Model

Approved quotations generate immutable approval packs.

Repair sessions clone approved pack items 1:1,
ensuring:

- No scope modification during execution
- Traceable decision history
- Enforcement of insurer-approved tasks only

---

### Server-Driven UI Rehydration

UI components are re-rendered from server partials
after workflow transitions to ensure:

- No state drift
- Single source of truth
- Deterministic frontend behavior

---

## 👥 Role Model

### Super Admin
- Manage garages & users
- Configure system-level services (SMS provider)
- Monitor usage
- Impersonate tenants for support

### Garage Owner / Staff
- Manage customers, vehicles, and jobs
- Generate invoices
- Trigger SMS reminders (quota-limited)
- No access to infrastructure or provider credentials

---

## 📩 SMS Architecture

- Provider is system-owned
- Configured centrally (Super Admin only)
- Garages cannot view or modify credentials
- Usage is plan-quota enforced
- All SMS events are logged

This ensures operational security and cost control.

---

## 🛠 Technology Stack

- Laravel (PHP)
- MySQL
- Blade + Alpine.js
- Tailwind CSS
- Laravel Queue (cron/worker)
- Ubuntu VPS deployment

---

## 📌 Project Status

Active development.

Current focus:
- Insurance workflow hardening
- Repair session integrity enforcement
- Receivables engine
- Reporting & operational dashboards

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
