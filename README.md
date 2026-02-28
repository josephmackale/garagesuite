# GarageSuite (iWebGarage)

GarageSuite is a multi-tenant Garage Management System (GMS) built on Laravel.
It is designed for real-world garage operations in Kenya and similar markets, with
a strong focus on simplicity, reliability, and operational clarity.

---

## 🔒 Deployment Information

**Production**

**Development**

**Server (VPS)**
46.62.255.138

---

## 🧠 System Overview

GarageSuite enables garages to manage daily operations efficiently:

- Multi-tenant garage architecture (garage-scoped data)
- Customers & vehicles management
- Jobs / work orders lifecycle
- Billing & invoicing
- SMS notifications (system-owned provider)
- Super Admin control panel
- Garage-level user roles

The system is built as a **SaaS**, with clear separation between:
- **Super Admin** (system owner)
- **Garage Owners & Staff** (tenants)

---

## 👥 User Roles

### Super Admin
- Manage garages & users
- Control system-wide settings
- Configure SMS provider
- Monitor usage & activity
- Impersonate garages (support/debug)

### Garage Owner / Staff
- Manage customers, vehicles, and jobs
- Generate invoices
- Trigger SMS reminders (quota-limited)
- No access to system infrastructure or APIs

---

## 📩 SMS Architecture (IMPORTANT)

- SMS provider is **system-owned**
- Configured in **Super Admin → Settings**
- Garages do **not** manage SMS APIs or credits
- SMS usage is limited by **plan quotas**
- All SMS messages are fully logged
- Sender ID & provider credentials are hidden from garages

This design ensures:
- Security
- Cost control
- Predictable delivery
- Minimal support overhead

---

## 🧾 Billing & Plans (High Level)

- Garages subscribe to plans
- Plans define limits (e.g. SMS quota)
- Payments are handled externally
- System enforces limits automatically

---

## 🛠 Technology Stack

- **Backend:** Laravel (PHP)
- **Frontend:** Blade + Tailwind CSS
- **Database:** MySQL / MariaDB
- **Queue:** Laravel queue (cron/worker)
- **Notifications:** SMS Provider API
- **Server:** Ubuntu VPS

---

## 🚀 Development Workflow

### Recommended
- Always work on **Development** before Production
- Use VS Code **Remote SSH** for live editing
- Keep system-level changes minimal and deliberate

### Useful commands
```bash
php artisan view:clear
php artisan route:clear
php artisan cache:clear
php artisan config:clear
