# Gateway Education Services - Supabase Migration Complete ✅

Your Gateway Education Services website has been migrated to **Supabase**!

---

## 📋 Project Summary & Credentials

| Setting | Value |
|---|---|
| **Project Name** | `Gateway-Education` |
| **Project ID / Reference** | `qtvmyjgmgmizeglrunjn` |
| **Project URL** | `https://qtvmyjgmgmizeglrunjn.supabase.co` |
| **API Key (Publishable)** | `sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr` |
| **Database Host (Direct / Pooler)** | `db.qtvmyjgmgmizeglrunjn.supabase.co` |
| **Database Port** | `5432` |
| **Database Name** | `postgres` |
| **Database User** | `postgres` |
| **Database Password** | `GatewayEdu2026!SecureDb#` |
| **Dashboard Link** | [Open Supabase Dashboard](https://supabase.com/dashboard/project/qtvmyjgmgmizeglrunjn) |

---

## 🗄️ Database Tables Created

### 1. `admissions` Table
Stores student enrollment applications from [`addmission.html`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/addmission.html):
- `id` (Primary Key, Auto-increment)
- `surname`, `firstname`, `dateofbirth`, `age`, `classapplied`
- `formerschool`, `parentname`, `nationality`, `phone`, `email`, `message`
- `submission_date`, `created_at`

### 2. `contacts` Table
Stores contact inquiries from [`contactus.html`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/contactus.html):
- `id` (Primary Key, Auto-increment)
- `name`, `email` *(Bug fixed: email is now properly stored!)*, `phone`, `subject`, `message`
- `submission_date`, `created_at`

### 3. `newsletters` Table
Stores newsletter subscriber emails from the website footer:
- `id` (Primary Key, Auto-increment)
- `email` (Unique, Lowercase)
- `status` (`'subscribed'`)
- `subscribed_at`, `created_at`

All tables have **Row Level Security (RLS)** enabled:
- **Public / Website visitors**: Can submit applications, contact messages, and newsletter signups (`INSERT`).
- **Admin**: Can view, export, and manage records in the Supabase Table Editor (`SELECT`).

---

## 📁 File Structure in Your Workspace

- [`supabase_rest_config.php`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/supabase_rest_config.php) — Configured with your live Supabase URL and API Key.
- [`addmission.php`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/addmission.php) — **ACTIVE**: Processes admission applications into Supabase.
- [`contact.php`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/contact.php) — **ACTIVE**: Processes contact messages into Supabase (email bug fixed).
- [`newsletter.php`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/newsletter.php) — **ACTIVE**: Processes newsletter subscriptions into Supabase with local backup logging.
- [`Supabase_Schema.sql`](file:///c:/Users/TALEMWA%20RICHARD/Desktop/mftp_zip_2025_07_16_16_36_20/Gateway/Supabase_Schema.sql) — Full PostgreSQL schema and RLS policies.

---

## 🧪 Live Verification Results

Live test inserts have been executed and verified directly against Supabase:
- `contacts`: Verified (HTTP `201 Created`)
- `admissions`: Verified (HTTP `201 Created`)
- `newsletters`: Verified (HTTP `200/201`, seamless Supabase sync with backup storage)

You can view the received test submissions right now in your [Supabase Table Editor](https://supabase.com/dashboard/project/qtvmyjgmgmizeglrunjn/editor).
