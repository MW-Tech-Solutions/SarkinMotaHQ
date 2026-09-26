# Complete Enterprise Upgrade Report

**Status: upgrade incomplete; review and verification performed.**

Review date: 26 September 2026. The user changed the instruction to “continue without changing any codes.” No further application code edits were issued after that instruction. Earlier edits remain in place. Subsequent writes were review documents, test evidence, screenshots, and synthetic test data. This report does **not** certify the application as production ready.

## Executive Summary

Sarkin Mota HQ is a procedural PHP real-estate, consulting, property-management and recruitment application. Its attractive public website and extensive portal hide incomplete workflows, inconsistent schema usage, weak role boundaries, and misleading payment behavior. The existing README overstates both coverage and readiness.

The inventory contains **56 PHP endpoints, ten authenticated roles, 59 source-level forms, and 16 dashboard tabs**. Every endpoint was attempted in Chrome or through its HTTP response. The staff directory cannot render successfully. The interface review includes actual screenshots, not just CSS inspection.

The most consequential unresolved problems are payment verification bypasses, unauthorized candidate access, broken HR pages, fake utility recharge tokens, publicly embedded demo-password behavior, and a production CSS build that does not generate utilities. Fixes are documented rather than implemented following the user's restriction.

Some files changed outside this review's tool calls while verification was running. Results therefore describe the observed workspace at the time of each check; the changed-file inventory is not a reliable attribution of every edit to this session. No Git repository was present, so Git diffs and history checks were unavailable.

## Architecture Improvements and Current Architecture

| Component | Discovered implementation |
|---|---|
| Runtime | PHP 8.2.12 locally; procedural PHP entry points |
| Frontend | Server-rendered HTML, Tailwind runtime CDN, custom CSS/JavaScript, Bootstrap Icons |
| Backend | Page controllers mixed with queries, validation and HTML; one PaymentService |
| Database | MariaDB/MySQL through PDO; no ORM |
| Package manager | npm for frontend tooling; no Composer dependency manifest |
| Authentication | Password hashes, PHP cookie sessions, database session token, inactivity timeout |
| Authorization | Legacy users.role plus roles, permissions and mapping tables; helpers and inline checks |
| Roles | super_admin, admin, staff, hr_manager, hr_officer, department_manager, interviewer, landlord, tenant, client |
| API | Properties bearer endpoint, payment webhook, theme-preference endpoint |
| Storage | Public uploads and branding images; private documents under storage with Apache deny rules |
| Payments | Paystack/Flutterwave verification plus manual-payment statuses; no complete hosted checkout flow |
| Email | Custom socket SMTP and native mail fallback; synchronous dispatch and mail_logs |
| Notifications | Flash messages and email; chatbot returns a fixed response |
| Jobs/cache | No queue worker or shared cache found; settings cache is per request |
| Deployment | XAMPP/Apache assumptions and hardcoded /SarkinMota paths; no container definition found |
| CI | GitHub Actions PHP/MySQL workflow; not deployed or remotely executed during review |
| Tests | Custom PHP suite using assert; browser/axe tooling installed before code freeze |
| Logging | SQL audit tables, PHP error_log and an incompletely integrated Logger/exception handler |
| Environment | .env loader and example; active configuration still enables debug |
| Design system | Shared header/sidebar/footer, brand CSS variables and utility classes |

Earlier edits introduced shared HTTP responses and request controls, removed several configuration secret fallbacks, corrected several authorization/session/input issues, removed private-download fallback paths, and replaced the base schema with definitions without account data. Broad controller/service separation has not been implemented. No framework replacement was attempted.

## Priority Findings

| ID | Severity | Finding and evidence | Required correction |
|---|---|---|---|
| F01 | Critical | PaymentService accepts a special provider reference as successful verification in testing or with test-key prefixes. An isolated transaction was marked paid without any gateway request. | Remove runtime success shortcuts; inject a fake gateway only in tests. |
| F02 | High | Tenant rent processing verifies immediately without checkout, and reports success for pending reconciliation. Utility processing manufactures a recharge token with mt_rand. | Implement actual provider initialization, callbacks and reconciliation; do not issue utility tokens without a utility provider. |
| F03 | High | An unassigned interviewer received candidate email/details from hr/applicants.php?id=1 (HTTP 200). Department scope is absent too. | Enforce assignment/department ownership in every list, detail and mutation query. |
| F04 | High | Invalid webhook signature caused HTTP 500. api/payment-webhook.php calls sanitize_input without including the helper. | Correct bootstrap, validate provider/event/signature and return a controlled response. |
| F05 | High | Gateway verification does not comprehensively bind returned reference, owner and exact amount/currency. Flutterwave uses the API secret as its webhook secret. | Follow the gateway's verification contract and configured webhook secret/signature scheme; atomically reconcile idempotently. |
| F06 | High | auth/login.php contains demo-password assignment logic; the legacy SQL dump includes user credential/session rows. | Remove demo credentials from delivered source; rotate exposed credentials and session tokens after owner review. Do not import the legacy dump into production. |
| F07 | High | A stale session revoked a newer database session token in an isolated rollback test. revoke_current_session updates by user ID only. | Revoke only the matching session token. |
| F08 | High | hr/directory.php returned HTTP 500 and queries nonexistent full_name, is_active, job_designation and updated_at columns. | Align reads and lifecycle writes with the real schema and retest each action. |
| F09 | High | hr/download-offer-pdf.php returns plain error text with HTTP 200 for a valid offer fixture. Its candidate_profile_id/applicant_* columns do not exist. | Join applications using application_id; enforce expiry, authorization and compensation visibility. |
| F10 | High | Offer creation writes issued directly despite separate approval permissions; vacancy creation accepts a published status without the approval check used by status updates. | Enforce approvals on creation as well as transitions. |
| F11 | High | HR provisioning can replace an existing account's role and exposes activation tokens in flash/table output. Token consumption is not atomic. | Protect privileged existing accounts, deliver single-use invitations privately, lock/conditionally consume tokens. |
| F12 | Medium | Past inspection dates were accepted with a success redirect. Several forms rely on enums/database errors instead of explicit input validation. | Validate dates, times, status transitions, bounds and referenced records before writing. |
| F13 | Medium | Portal theme save requests go to /auth/api/theme-preference.php and receive 404; the client also lacks the new CSRF header. | Use an application-root endpoint and send a valid CSRF token; report persistence errors. |
| F14 | Medium | The CSS build succeeds but contains no Tailwind flex utility: style.css has no @tailwind directives. HR/error templates are absent from content configuration. | Build and serve complete local production assets; retain branding tokens without runtime CDN dependence. |
| F15 | Medium | Tablet portal content visibly extends past the viewport while overflow-x:clip conceals page overflow. The fixed sidebar consumes too much space. | Use a tablet drawer/compact layout; fix content widths rather than clipping them. |
| F16 | Medium | Escape does not close the public navigation, portal drawer or department modal in browser interaction tests. | Implement keyboard dismissal, focus management, focus return and appropriate dialog semantics. |
| F17 | Medium | All 51 completed axe page scans reported at least one accessibility rule violation; contrast and missing form labels recur. | Correct contrast, programmatic labels and control names, then re-run automated and manual keyboard checks. |
| F18 | Medium | Maintenance submissions only write audit text. The helpdesk form has no matching durable support workflow. | Persist support/maintenance records and provide their status and handling workflow. |
| F19 | Medium | HR/admin lists frequently fetchAll without pagination; some permissions and sidebar links disagree with backend guards. | Bound lists and align route, navigation and action permissions. |
| F20 | Medium | Error handling is not consistently bootstrapped, configuration permits debug disclosure, and several errors use die with HTTP 200. | Use one safe bootstrap, controlled status responses and redacted operational logs. |
| F21 | Medium | Private documents are inside the web root and rely on Apache configuration; uploads lack a dedicated execution-deny rule. | Verify deployment-level protection and preferably move private storage outside the served root. |
| F22 | Medium | Public pages show dead legal links, app-store alert placeholders, “Bank-Grade Encryption,” and a different brand in the supplied logo. | Supply approved destinations/content and correct unsupported claims and branding. |
| F23 | Medium | The test runner uses the configured application database, does not report assertion counts, and includes an email delivery side effect. Some tests repeat assumptions rather than exercise handlers. | Require an isolated test database and gateway/mail fakes; assert actual authorization and workflow outcomes. |
| F24 | Medium | CI lacks a complete frontend build, dependency audit, HTTP/browser tests and explicit isolated configuration; legacy migration syntax assumes MariaDB. | Make CI reproducible on the chosen database/runtime and fail on each quality gate. |

The payment review used the official [Paystack verification documentation](https://paystack.com/docs/payments/verify-payments/), [Paystack webhook documentation](https://paystack.com/docs/payments/webhooks/), [Flutterwave transaction verification documentation](https://developer.flutterwave.com/docs/transaction-verification) and [Flutterwave webhook documentation](https://developer.flutterwave.com/docs/webhooks). No live charge, real payment verification or external email was performed.

## Security Improvements

Applied before the freeze: environment variables take precedence over .env; nonempty hardcoded APP/API/payment secret fallbacks were removed; .gitignore excludes local secrets and dumps; shared request checks reject nested parameters and invalid CSRF; headers and a database rate-limit mechanism were added; logout GET now displays a confirmation form; several raw exception displays were replaced; private downloads no longer fall back to arbitrary legacy paths; image handling rejects excessive dimensions and removes the un-reencoded fallback.

These are partial improvements. F01–F24 remain relevant. The new request limiter requires the migration table in the configured database. It was exercised in the isolated database; this review did not apply migrations to production/application data. Other workspace activity introduced an automatic table-creation fallback that was not authored by this review.

## Authentication

All ten synthetic role accounts logged in successfully. A forged super_admin public registration became a client, confirmed directly in the isolated database. Password-reset matching now uses only the hashed token and a conditional expiry update. Reset/activation screens were rendered with valid synthetic tokens, but complete delivery, expiration and concurrency flows were not all exercised. Demo password behavior, stale-session revocation, invitation exposure and inconsistent password rules prevent a security pass. No MFA/OAuth flow was found.

## Authorization

275 guest/role/route response observations are saved. Customer access to the property administration page is denied; an unrelated customer's receipt request is redirected. The missing-parameter offer-download route returns a public HTTP 200 error and must not be counted as authorized private-data access. Candidate assignment boundaries fail. Interviewers also receive 403 from their interview screen because it requires management permission instead of evaluation permission. HR department boundaries and privileged-user management need further correction.

## Database and Migrations

A uniquely named sarkinmota_quality_* database was created from schema definitions only, populated with synthetic example.invalid accounts and test records, and migrated successfully. The main application rows were not copied or used as fixtures. The original setup attempt had a fixture-column mismatch in careers; the fixture was corrected without changing source.

database/schema.sql now contains DDL without account rows. database/migrate.php now records applied versions and no longer creates a known-password HR account or overwrites sample property coordinates. database/migrations/05_request_limits.sql adds the limiter table. This was validated on local MariaDB; MySQL compatibility, upgrades from every historic schema, rollback/restore and production migration plans are **not verified**. Existing HR queries still contradict the schema. Many lists and reference relationships require additional indexes/constraints and pagination after workload analysis.

## API

Missing/invalid bearer credentials are rejected. Invalid nested query parameters return 400. GET on the webhook/theme endpoints returns 405. Invalid webhook POST fails with 500. Theme persistence fails from nested portal paths. Complete authorized API contract, load, rate-limit boundary, webhook retry and failure-recovery coverage is incomplete.

## Payments

Not ready for real-money use. The bypass was independently reproduced in an isolated transaction and rolled back. The existing payment suite's pass is evidence of that shortcut, not successful gateway integration. Exact reference/amount/owner matching, concurrent idempotency, receipt consistency and real checkout require implementation and provider-sandbox verification. Utility vending has no actual provider integration.

## Uploads / Storage

The source includes extension/MIME/size checks and randomized filenames; image re-encoding and pixel limits are partly hardened. Private-document routing requires authentication/permission. The missing-file case was tested. Successful authenticated streaming, valid/invalid multipart upload coverage, MIME/extension consistency, legacy file migration and production web-server isolation remain unverified. Apache deny rules exist for storage; the PHP development server used for testing does not honor .htaccess and must never be treated as deployment validation.

## Testing

| Check | Actual result |
|---|---|
| PHP syntax | 91 files checked, 0 syntax failures at initial check |
| Existing PHP suites, assertions enabled | Tests: 8 suites; Passed: 7; Failed: 1; Skipped: 0; Assertions: not reported by runner |
| Existing suite failure | HrTest attempts socket email; outbound mail functions deliberately disabled; no external messages sent |
| Targeted HTTP functional/security checks | 15 checks: 10 passed, 5 failed |
| Missing-CSRF checks | 34 form routes: 34 returned 403 |
| Additional isolated security probes | 2 checks failed: payment shortcut and stale-session revocation |
| Keyboard/theme interactions | 4 checks failed |
| Role-route observations | 275 responses across guest plus ten roles; observations, not 275 passing authorization assertions |
| Initial route traversal | 56 routes attempted; HR directory fails; API/error/missing-parameter responses recorded separately |
| Responsive geometry | 816 measurements on 51 rendered routes at 16 widths; zero root-scroll overflow flags; visible clipping still fails tablet review |
| Axe | 51 pages scanned; 71 page/rule violation groups, 886 node instances; repeated shared components are counted per page |
| npm dependency check | Install audit and offline audit reported zero known package vulnerabilities; not a PHP/source-code security certification |
| CSS build | Completed into storage/quality/build.css; missing utilities confirmed |
| Migration | Fresh isolated MariaDB setup succeeded; production deployment not attempted |

The targeted assertions total **55: 44 passed and 11 failed**, excluding the existing suite count, login observations, browser route enumeration and viewport measurements. The existing suites do not expose an assertion count. Browser harness issues were separated from product defects: an HR departments form named action shadowed the DOM form.action property; that measurement was retried using getAttribute and completed. An initial mobile-menu test used the wrong viewport and was retried with a mobile browser context.

## CI/CD and Secure Deployment

No deployment, merge or publication occurred. Existing CI was inspected but not executed remotely. It needs environment isolation, consistent MariaDB/MySQL support, frontend compilation, reliable lint exit propagation, meaningful assertions and browser/security checks. No deployment secrets are reproduced here. Production HTTPS, reverse-proxy headers, backups, restore drills and least-privilege database credentials require deployment verification.

## Performance and Observability

The hero video is approximately 4.1 MB. Runtime Tailwind, external fonts/images, duplicated font loading and unbounded HR/admin queries are avoidable costs. No realistic load test or database query-plan benchmark was run. There is no established queue or shared cache. SQL audit tables exist, but some HR audit inserts omit required fields and suppress their failures. The exception handler exists without universal wiring. No operational health-check or alerting pipeline was verified.

## UI/UX Improvements and Public Interfaces

No visual source edits were made after the freeze. Actual images were reviewed for the public pages, portal pages and error states at 390, 820 and 1440 pixels. Public layouts generally stack coherently and use bounded desktop containers, but muted text/amber links fail contrast, legal links are placeholders, authentication pages expose demo-login controls, and the supplied logo does not match the product name. Invalid activation/download links render bare text rather than a professional error state. The chatbot does not contact an advisor or implement AI.

## User and Admin Interfaces

Role dashboards render with synthetic data. Some access-denied flash messages in screenshots are artifacts of the deliberate preceding role-denial tests, not spontaneous dashboard errors. Admin tables/forms are visually organized, but lists lack consistent pagination, tiny labels and row actions impair readability, and many mobile tables require horizontal scrolling. The helpdesk/maintenance/payment representations exceed the implemented behavior. The staff directory and offer-letter route fail functionally.

## Mobile, Tablet and Desktop

Mobile: forms usually stack; headers, small touch targets, table actions and drawer keyboard behavior need work. Tablet: fixed 288-pixel sidebar plus inflexible workspace/card layouts visibly clips content at 820 pixels. Desktop: layouts are generally legible, but low-contrast text and unsupported content remain. Root overflow-x:clip makes a “no horizontal scroll” assertion insufficient. Initial viewport snapshots do not certify every offscreen section or every dialog state.

## Accessibility and Responsive Verification

Widths exercised: mobile 320, 360, 375, 390, 412, 430; tablet 600, 768, 820, 912, 1024; desktop 1280, 1366, 1440, 1600, 1920. Screenshots were captured at 390/820/1440, with separate tab/token-state captures. Fifty-one routes have the full geometry series; the staff-directory 500 prevents its normal interface review. API/XML endpoints are not visual layouts. No WCAG conformance claim is supported. Full screen-reader testing, every modal, all tablet orientations and cross-browser coverage remain incomplete.

## Files Changed and Created

Before the restriction, edits affected config/app.php; includes/functions.php, auth_helper.php and rbac_helper.php; auth/logout.php, forgot-password.php, reset-password.php and landlord-dashboard.php; api/properties.php and theme-preference.php; download.php; admin/manage-properties.php; database/schema.sql and migrate.php; and selected auth/admin/HR exception displays or direct session startup blocks. package.json/package-lock.json were updated by installing the browser/axe/icon dependencies. Some scripted intended replacements did not match, including the session-token conditional revocation; the report marks the remaining defect instead of claiming it fixed.

Created before the restriction: .gitignore; includes/http.php; includes/request_security.php; database/migrations/05_request_limits.sql; tools/inventory.cjs, core-upgrade.cjs and probe.php; the initial route inventory. The tools are one-off local review/migration helpers, not application routes; do not rerun the one-off core-upgrade script as a deployment step.

Created after the restriction: this report, generated matrices and requirement coverage, screenshots and JSON/log/build evidence under storage/quality, and synthetic database fixtures. No application code was subsequently edited by this review. The observed changed-file list in storage/quality/changed-files.json also includes changes made outside these tool calls, so it must not be treated as an authored patch list.

## Remaining Limitations

```text
INCOMPLETE REQUIREMENT:
Requirement: Fix all discovered code, security, workflow and interface defects.
Reason: The user's latest instruction prohibits further code changes.
Attempted solution: Earlier shared hardening, followed by source review and isolated verification.
Fallback implemented: Prioritized findings, route/interface/security matrices and reproducible evidence.
Files involved: Application controllers/helpers/services and the files identified in F01-F24.
What is still required: Implement corrections when code edits are authorized, then rerun affected tests.
Verification status: Incomplete; multiple confirmed failures remain.
```

```text
INCOMPLETE REQUIREMENT:
Requirement: Verify every form, destructive action, upload, modal and complete business workflow.
Reason: Some underlying routes/workflows are broken, and complete mutation coverage has not been implemented.
Attempted solution: Synthetic accounts/data, route traversal, invalid-CSRF checks, selected valid/invalid forms, role checks and UI interactions.
Fallback implemented: Each route/form is inventoried; unverified behaviors are explicitly marked below.
Files involved: docs/route-inventory.json; storage/quality/*-results.json; all form controllers.
What is still required: Data-driven end-to-end fixtures for every action, ownership/CSRF/validation/failure/concurrency assertions and all modal states.
Verification status: Partial; no blanket success claim.
```

```text
INCOMPLETE REQUIREMENT:
Requirement: Validate live email, payment gateways, utility vending and production deployment.
Reason: No safe provider sandbox/delivery target or production deployment verification was established; utility vending is absent.
Attempted solution: Official gateway documentation, isolated payment probes, SMTP-disabled existing suites and deployment source review.
Fallback implemented: No external messages or real charges; missing functionality and required configuration documented.
Files involved: app/Services/PaymentService.php, includes/mail_helper.php, auth/tenant-dashboard.php, .github/workflows/ci.yml.
What is still required: Proper integration implementation, sandbox credentials, controlled delivery sinks and deployment tests.
Verification status: Not verified end-to-end.
```

```text
INCOMPLETE REQUIREMENT:
Requirement: Fully validate visual/accessibility behavior across every device and state.
Reason: The HR directory cannot render; screenshots cover selected viewport states and axe cannot establish full conformance.
Attempted solution: All discovered endpoints visited, 16 width checks where renderable, screenshots, axe and selected keyboard tests.
Fallback implemented: Failure evidence and per-page status, with no visual pass inferred from CSS alone.
Files involved: hr/directory.php; includes/header.php, admin_sidebar.php and footer.php; all page templates.
What is still required: Fix failures, check all dialogs/offscreen content/orientations with keyboard and screen readers, and repeat in other browsers.
Verification status: Partial visual verification; accessibility failed.
```

## Re-score

These are provisional engineering judgments, not certification or a claim of remediation.

| Area | Score |
|---|---:|
| Architecture | 4/10 |
| Code Quality | 4/10 |
| Frontend Architecture | 4/10 |
| Backend Architecture | 4/10 |
| Database | 4/10 |
| Security | 2/10 |
| Authentication | 4/10 |
| Authorization | 3/10 |
| API | 3/10 |
| Testing | 3/10 |
| Performance | 4/10 |
| UI Design | 6/10 |
| UX | 4/10 |
| Mobile Design | 4/10 |
| Tablet Design | 3/10 |
| Desktop Design | 6/10 |
| Accessibility | 3/10 |
| DevOps | 3/10 |
| Observability | 2/10 |
| Documentation | 5/10 |
| Maintainability | 4/10 |
| Scalability | 3/10 |
| Production Readiness | 2/10 |
| Enterprise Readiness | 2/10 |

**Overall Enterprise Readiness: 36/100 — Early MVP.** The low security/workflow scores are release blockers regardless of the arithmetic average.

## Evidence and Detailed Matrices

- [Route inventory](route-inventory.md), with source forms/guards/tab names in [JSON](route-inventory.json).
- [Route, interface, security, responsive and per-page matrices](verification-matrices.md).
- [All 73 requested requirements accounted for](requirement-coverage.md).
- Raw browser evidence: storage/quality/browser-results.json, role-results.json, csrf-results.json, functional-results.json, interaction-results.json, variant-results.json and tab-results.json.
- Screenshots: storage/quality/screenshots; overview sheets review-sheet-0.png through review-sheet-12.png; separate HR departments and modal captures.
- Existing suite log: storage/quality/existing-tests.log. Test-server logs and generated build output are local evidence, not deployment assets.
