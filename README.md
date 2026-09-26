# Sarkin Mota HQ — Enterprise Platform (v2.0)

[![CI/CD Pipeline](https://github.com/sarkinmotahq/platform/actions/workflows/ci.yml/badge.svg)](https://github.com/sarkinmotahq/platform/actions)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-gold.svg)](LICENSE)
[![PHP: 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net)
[![Compliance: WCAG 2.2 AA](https://img.shields.io/badge/Compliance-WCAG%202.2%20AA-emerald.svg)](https://www.w3.org/TR/WCAG22/)

---

## 1. Executive Overview

**Sarkin Mota HQ** is a multidisciplinary corporate real estate, agronomy asset valuation, environmental impact advisory, and municipal development firm headquartered in **Maitama Extension, Abuja** and **Jimeta-Yola, Adamawa State, Nigeria**.

This enterprise-grade system upgrade transforms the Sarkin Mota HQ digital platform into a production-ready, secure, accessible, and scalable system designed for real clients, tenants, landlords, corporate staff, and administrators.

---

## 2. Enterprise System Architecture

```
Sarkin Mota HQ/
├── app/                        # Enterprise Business Logic & Services
│   ├── Services/
│   │   ├── PaymentService.php  # Server-side payment verification & webhooks
│   │   ├── DashboardService.php
│   │   └── Validator.php
│   ├── Models/
│   └── Middleware/
├── admin/                      # Enterprise Admin & Portal Management
│   ├── manage-inspections.php  # Site inspection tour scheduling
│   ├── manage-consultations.php# Corporate advisory requests
│   ├── manage-properties.php   # Real estate catalog & GIS coords
│   ├── manage-careers.php      # Candidate management & private CV downloads
│   ├── manage-news.php         # Blog CMS & thought leadership
│   ├── manage-projects.php     # Division infrastructure projects
│   └── view-messages.php       # Inquiries inbox
├── api/                        # REST API & Webhooks
│   ├── properties.php          # Bearer auth, paginated JSON API
│   └── payment-webhook.php     # Signature-verified gateway listener
├── auth/                       # Authentication & Session Gateway
│   ├── login.php
│   ├── register.php            # Restricted customer-facing registration
│   ├── forgot-password.php
│   ├── reset-password.php
│   ├── dashboard.php           # Role-isolated paginated workspace
│   └── logout.php
├── config/                     # Environment & Database Configuration
│   ├── app.php                 # .env parser & application settings
│   └── db.php                  # PDO connection manager
├── database/                   # Schema Migrations & CLI Scripts
│   ├── migrate.php             # CLI migration executor
│   └── migrations/
│       └── 01_schema_v2.sql    # RBAC & relational entity schemas
├── error_pages/                # Custom HTTP Error Interfaces (403, 404, 500)
├── includes/                   # Reusable Helpers & UI Framework
│   ├── auth_helper.php         # DB session validation & session tokens
│   ├── rbac_helper.php         # Granular permission engine
│   ├── pagination_helper.php   # Server-side paged query builder
│   ├── functions.php           # Image re-encoding & private file uploads
│   ├── error_handler.php       # Uncaught exception logger
│   └── logger.php              # Structured file logging service
├── storage/                    # Protected Private Storage & Logs
│   ├── uploads/
│   │   ├── public/             # Public web images
│   │   └── private/            # Protected candidate resumes & documents
│   └── logs/
│       └── app.log             # Enterprise application log
├── tests/                      # Automated Test Suite
│   ├── run_tests.php           # Master test runner
│   ├── AuthTest.php
│   ├── RbacTest.php
│   ├── PaymentTest.php
│   ├── UploadTest.php
│   ├── InspectionTest.php
│   └── ApiTest.php
├── download.php                # Authorized private file stream controller
├── download-receipt.php        # Verified official PDF/HTML receipt generator
├── index.php                   # Public Homepage
├── properties.php              # Real estate catalog with filters & pagination
├── property-detail.php         # Detailed specs, Leaflet GIS map, inspection form
├── services.php                # Corporate advisory services & booking form
├── careers.php                 # Vacancies & private resume application form
├── news.php                    # Published articles catalog
└── sitemap.php                 # Dynamic XML sitemap generator
```

---

## 3. Installation & Local Development Setup

### Prerequisites
* **PHP**: `8.1` or higher with extensions: `pdo`, `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `curl`.
* **Database**: MySQL `8.0+` or MariaDB `10.4+` running on port `3308` (or configured port).
* **Web Server**: Apache / Nginx / XAMPP.

### Step-by-Step Setup
1. **Clone/Place Codebase**:
   Ensure project directory is placed in web root (e.g. `C:\xampp\htdocs\SarkinMota`).

2. **Environment Configuration**:
   Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
   Update `.env` values (`DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `PAYSTACK_SECRET_KEY`, `API_SECRET_KEY`).

3. **Execute Database Migrations**:
   Run the CLI migration script to apply database schemas and seed default RBAC roles and permissions:
   ```powershell
   php database/migrate.php
   ```

4. **Verify Frontend Assets**:
   Compile production stylesheet or run CSS build:
   ```bash
   npm run build:css
   ```

5. **Access Application**:
   Open browser at: `http://localhost/SarkinMota`

---

## 4. Security & Role-Based Access Control (RBAC) Matrix

### Public Registration Policy (F01 Resolution)
Public self-registration strictly allows only customer-facing account roles:
* `client`: Buyer/Investor
* `tenant`: Property Renting Account
* `landlord`: Property Owner Account

*Staff, Admin, and Super Admin accounts CANNOT be created publicly.* They must be provisioned internally by an authorized administrator.

### Role & Permission Boundaries (F07 & F08 Resolution)
| Role | Permissions | System Capabilities |
| :--- | :--- | :--- |
| **Super Admin** | `*` (All permissions) | Full system administration, Super Admin account management. Protects last active Super Admin. |
| **Admin** | `users.*`, `properties.*`, `payments.*`, `careers.*`, `cms.*`, etc. | Full platform operational management. Cannot create or delete Super Admins. |
| **Staff** | `properties.view`, `inquiries.manage`, `inspections.manage`, `applications.view` | Customer service, tour scheduling, and candidate review. |
| **Landlord** | `properties.create`, `tenants.view`, `payments.view` (Owned properties only) | Property management, tenant tracking, rent ledger. |
| **Tenant** | Profile, own lease, own payments, own tour requests | Rent payment initiation, receipt access, tour scheduling. |
| **Client** | Profile, own inquiries, own tour requests | Property catalog browsing, inspection request submission. |

---

## 5. Verified Payment Architecture (Phase 2 & F05)

Payment submissions undergo server-side validation:
1. Client initiates payment transaction -> system creates record with status `pending` and unique reference `KDT-YYYYMMDDHHMMSS-XXXXXXXX`.
2. Verification API executes server-to-server HTTP check with Paystack / Flutterwave gateway.
3. Transaction status updates to `paid` only after trusted verification.
4. Webhook listener `api/payment-webhook.php` verifies HMAC signatures and prevents replay.
5. Official receipts (`download-receipt.php`) are generated **ONLY** for verified `paid` transactions.

---

## 6. Private File Upload & Document Stream Security (Phase 7 & F10)

* **Public Images**: Uploaded via `handle_image_upload()`. Validated using `finfo` MIME check (`image/jpeg`, `image/png`, `image/webp`), signature check, SVG disallowance, image re-encoding, and stored with random filenames in `uploads/`.
* **Private Documents**: Candidate resumes and property contracts are stored outside public web root in `storage/uploads/private/`.
* **Authorized Stream Controller**: Private files are accessed strictly via `download.php?type=resume&id=X`, which verifies user session and `applications.view` permission before streaming headers.

---

## 7. Master Automated Testing Suite (Phase 28 & F22)

Execute master automated CLI test suite:
```powershell
php tests/run_tests.php
```

### Test Coverage
* `AuthTest`: Tests registration role restrictions, password complexity, session invalidation.
* `RbacTest`: Tests permission matrix enforcement and Super Admin privilege boundaries.
* `PaymentTest`: Tests transaction initiation, verification, and receipt access logic.
* `UploadTest`: Tests MIME validation, image re-encoding, and private document security.
* `InspectionTest`: Tests property tour request creation and status workflow.
* `ApiTest`: Tests API Bearer header authorization, pagination meta, and JSON response structure.

---

## 8. Enterprise REST API Contract (Phase 22 & F24)

### Endpoint: `GET /api/properties.php`

**Headers**:
```http
Authorization: Bearer <API_SECRET_KEY>
Content-Type: application/json
```

**Success Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "The Sarkin Mota HQ Oasis Estate",
      "price": "180000000.00",
      "location": "Maitama Extension, Abuja, Nigeria",
      "latitude": 9.07650000,
      "longitude": 7.39860000,
      "beds": 5,
      "baths": 6,
      "type": "sale",
      "category": "residential",
      "listing_status": "active"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 4,
    "last_page": 1
  }
}
```

---

## 9. Observability & Logging

All uncaught exceptions and security events (logins, role changes, payment verifications, document access) are logged to:
`storage/logs/app.log`

---

## 10. Licensing & Corporate Custody

**Sarkin Mota HQ**  
ADSYP 32, ATM Road, Off Jawa Street, Kofare-Agric, Jimeta-Yola, Adamawa State, Nigeria.  
Website: [https://www.sarkinmotahq.com](https://www.sarkinmotahq.com)

