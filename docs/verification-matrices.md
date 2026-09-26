# Verification Matrices and Page-by-Page Report

This is an evidence matrix, not a blanket pass. FAIL means at least one known issue remains; NOT VERIFIED means coverage cannot establish full correctness. All initial 56 endpoints are accounted for. Authenticated fixtures contain synthetic data only.

## Route verification matrix

| Route/Page | Role tested | Functional | Security | Mobile | Tablet | Desktop | Accessibility | Result |
|---|---|---|---|---|---|---|---|---|
| /about.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /activate-account.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: document-title, html-has-lang | FAIL |
| /admin/manage-branding.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label, select-name | FAIL |
| /admin/manage-careers.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label | FAIL |
| /admin/manage-consultations.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-divisions.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-inspections.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-news.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-projects.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-properties.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/manage-team.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /admin/send-mail.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, select-name | FAIL |
| /admin/view-messages.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /api/payment-webhook.php | guest | HTTP 405; partial workflow coverage | See findings | N/A or unavailable | N/A or unavailable | N/A or unavailable | Not assessed / unavailable | FAIL |
| /api/properties.php | guest | HTTP 401; partial workflow coverage | See findings | N/A or unavailable | N/A or unavailable | N/A or unavailable | Not assessed / unavailable | NOT VERIFIED |
| /api/theme-preference.php | guest | HTTP 405; partial workflow coverage | See findings | N/A or unavailable | N/A or unavailable | N/A or unavailable | Not assessed / unavailable | FAIL |
| /auth/admin-dashboard.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/client-dashboard.php | client | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/dashboard.php | super_admin | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/forgot-password.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/landlord-dashboard.php | landlord | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/login.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/logout.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/register.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/reset-password.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /auth/tenant-dashboard.php | tenant | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /careers.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /contact.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast, frame-title | FAIL |
| /divisions/agriculture.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /divisions/development.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /divisions/environmental.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /divisions/estate-management.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /divisions/view.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /download-receipt.php | super_admin | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: document-title, html-has-lang | FAIL |
| /download.php | super_admin | HTTP 404; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: document-title, html-has-lang | NOT VERIFIED |
| /error_pages/403.php | guest | HTTP 403; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /error_pages/404.php | guest | HTTP 404; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /error_pages/500.php | guest | HTTP 500; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /hr/applicants.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label, select-name | FAIL |
| /hr/departments.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /hr/directory.php | super_admin | HTTP 500 confirmed; partial workflow coverage | Missing CSRF rejected; other controls partial | N/A or unavailable | N/A or unavailable | N/A or unavailable | Not assessed / unavailable | FAIL |
| /hr/download-offer-pdf.php | super_admin | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: document-title, html-has-lang | FAIL |
| /hr/index.php | super_admin | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /hr/interviews.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label, select-name | FAIL |
| /hr/offers.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label, select-name | FAIL |
| /hr/onboarding.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast | FAIL |
| /hr/vacancies.php | super_admin | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | FAIL: clipped workspace | Captured; partial | FAIL: color-contrast, label, select-name | FAIL |
| /index.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast, frame-title | FAIL |
| /news-detail.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /news.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /offer-response.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /projects.php | guest | HTTP 200; partial workflow coverage | See findings | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /properties.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast, select-name | FAIL |
| /property-detail.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast, frame-title | FAIL |
| /services.php | guest | HTTP 200; partial workflow coverage | Missing CSRF rejected; other controls partial | Captured; issues remain | Captured; partial | Captured; partial | FAIL: color-contrast | FAIL |
| /sitemap.php | guest | HTTP 200; partial workflow coverage | See findings | N/A or unavailable | N/A or unavailable | N/A or unavailable | Not assessed / unavailable | FAIL |

## Interface verification matrix

| Page | Header/Nav | Layout | Forms | Tables | Mobile | Tablet | Desktop | UX Status |
|---|---|---|---|---|---|---|---|---|
| about.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| activate-account.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| admin/manage-branding.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | N/A | Captured | Clipping failure | Captured | FAIL |
| admin/manage-careers.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-consultations.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-divisions.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | N/A | Captured | Clipping failure | Captured | FAIL |
| admin/manage-inspections.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-news.php | Shared / captured where available | Screenshot reviewed | 3 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-projects.php | Shared / captured where available | Screenshot reviewed | 3 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-properties.php | Shared / captured where available | Screenshot reviewed | 3 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/manage-team.php | Shared / captured where available | Screenshot reviewed | 3 in initial source; incomplete outcomes | N/A | Captured | Clipping failure | Captured | FAIL |
| admin/send-mail.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| admin/view-messages.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | N/A | Captured | Clipping failure | Captured | FAIL |
| api/payment-webhook.php | N/A | Not renderable / response only | 0 in initial source; incomplete outcomes | N/A | N/A or unavailable | N/A or unavailable | N/A or unavailable | FAIL |
| api/properties.php | N/A | Not renderable / response only | 0 in initial source; incomplete outcomes | N/A | N/A or unavailable | N/A or unavailable | N/A or unavailable | NOT VERIFIED |
| api/theme-preference.php | N/A | Not renderable / response only | 0 in initial source; incomplete outcomes | N/A | N/A or unavailable | N/A or unavailable | N/A or unavailable | FAIL |
| auth/admin-dashboard.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| auth/client-dashboard.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| auth/dashboard.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Clipping failure | Captured | FAIL |
| auth/forgot-password.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| auth/landlord-dashboard.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| auth/login.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| auth/logout.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| auth/register.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| auth/reset-password.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| auth/tenant-dashboard.php | Shared / captured where available | Screenshot reviewed | 4 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| careers.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| contact.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| divisions/agriculture.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| divisions/development.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| divisions/environmental.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| divisions/estate-management.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| divisions/view.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| download-receipt.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| download.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | NOT VERIFIED |
| error_pages/403.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| error_pages/404.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| error_pages/500.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| hr/applicants.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/departments.php | Shared / captured where available | Screenshot reviewed | 3 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/directory.php | Shared / captured where available | Not renderable / response only | 5 in initial source; incomplete outcomes | Present; small fixture only | N/A or unavailable | N/A or unavailable | N/A or unavailable | FAIL |
| hr/download-offer-pdf.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/index.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/interviews.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/offers.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/onboarding.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| hr/vacancies.php | Shared / captured where available | Screenshot reviewed | 2 in initial source; incomplete outcomes | Present; small fixture only | Captured | Clipping failure | Captured | FAIL |
| index.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| news-detail.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| news.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| offer-response.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| projects.php | Shared / captured where available | Screenshot reviewed | 0 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| properties.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| property-detail.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| services.php | Shared / captured where available | Screenshot reviewed | 1 in initial source; incomplete outcomes | N/A | Captured | Captured | Captured | FAIL |
| sitemap.php | N/A | Not renderable / response only | 0 in initial source; incomplete outcomes | N/A | N/A or unavailable | N/A or unavailable | N/A or unavailable | FAIL |

## Security verification matrix

| Security Requirement | Status | Evidence |
|---|---|---|
| Secrets protected | FAIL | Demo password behavior and legacy credential dump remain; fallbacks removed; .env ignored. |
| Authentication secure | FAIL | Ten fixture logins work; stale-session revocation independently fails; demo credentials remain. |
| Authorization | FAIL | Unassigned interviewer sees candidate details; approval creation and HR role constraints inconsistent. |
| Ownership/IDOR | FAIL | Candidate ownership test fails; customer receipt denial succeeds. |
| CSRF | PASS (tested subset) | 34 form routes reject missing/invalid tokens; complete action coverage not established. |
| Upload security | FAIL (incomplete assurance) | Several controls exist; successful multipart paths and production execution-denial not fully verified. |
| Rate limiting | FAIL (not fully verified) | Database limiter exists; distributed behavior, expiry and 429 boundary not exercised. |
| Session security | FAIL | Old session can revoke new token; cookies/rotation alone do not establish security. |
| Payments | FAIL | Paid state reproduced without provider call; webhook POST fails; utility token fabricated. |
| Audit logs | FAIL | Some HR inserts omit required username; payment/action transitions not universally transactional. |

## Responsive test matrix

Geometry checks used 900px height and the widths below. No root overflow flags were raised, but screenshot review confirmed clipped portal content. This is a failed tablet UX result. Landscape orientation and all offscreen states are not certified.

| Width | Class | Rendered routes measured | Root overflow flags | Visual scope |
|---:|---|---:|---:|---|
| 320 | Mobile | 51 | 0 | Automated geometry only |
| 360 | Mobile | 51 | 0 | Automated geometry only |
| 375 | Mobile | 51 | 0 | Automated geometry only |
| 390 | Mobile | 51 | 0 | Actual captures reviewed; defects remain |
| 412 | Mobile | 51 | 0 | Automated geometry only |
| 430 | Mobile | 51 | 0 | Automated geometry only |
| 600 | Tablet | 51 | 0 | Automated geometry only |
| 768 | Tablet | 51 | 0 | Automated geometry only |
| 820 | Tablet | 51 | 0 | Actual captures reviewed; defects remain |
| 912 | Tablet | 51 | 0 | Automated geometry only |
| 1024 | Tablet | 51 | 0 | Automated geometry only |
| 1280 | Desktop | 51 | 0 | Automated geometry only |
| 1366 | Desktop | 51 | 0 | Automated geometry only |
| 1440 | Desktop | 51 | 0 | Actual captures reviewed; defects remain |
| 1600 | Desktop | 51 | 0 | Automated geometry only |
| 1920 | Desktop | 51 | 0 | Automated geometry only |

## Page-by-page final report

### about.php

Page: about.php

Route: /about.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (20 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Contrast failures and branding/content verification outstanding.

Status: FAIL

### activate-account.php

Page: activate-account.php

Route: /activate-account.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: document-title (1 nodes); html-has-lang (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Missing token produces plain HTTP 200 text; valid fixture renders; single-use concurrency and delivery not verified.

Status: FAIL

### admin/manage-branding.php

Page: admin/manage-branding.php

Route: /admin/manage-branding.php

Role: super_admin; guards: require_super_admin().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (26 nodes); label (34 nodes); select-name (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Missing labels/select names; palette/form previews clip on tablet; upload and reset paths not exhaustively exercised.

Status: FAIL

### admin/manage-careers.php

Page: admin/manage-careers.php

Route: /admin/manage-careers.php

Role: super_admin; guards: require_role(['admin', 'staff']); require_permission('careers.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (19 nodes); label (3 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Legacy role gate blocks HR roles despite careers permission; labels/contrast; unbounded vacancies/applications.

Status: FAIL

### admin/manage-consultations.php

Page: admin/manage-consultations.php

Route: /admin/manage-consultations.php

Role: super_admin; guards: require_permission('consultations.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (18 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Unbounded table; tablet/mobile column clipping; status actions not exhaustively verified.

Status: FAIL

### admin/manage-divisions.php

Page: admin/manage-divisions.php

Route: /admin/manage-divisions.php

Role: super_admin; guards: require_super_admin().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (32 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Tablet editor/list clipping; concurrent-delete revision logic needs review; custom slug variants not exhaustively exercised.

Status: FAIL

### admin/manage-inspections.php

Page: admin/manage-inspections.php

Route: /admin/manage-inspections.php

Role: super_admin; guards: require_permission('inspections.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (18 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Unbounded table; update guard present; lifecycle/concurrent update coverage incomplete.

Status: FAIL

### admin/manage-news.php

Page: admin/manage-news.php

Route: /admin/manage-news.php

Role: super_admin; guards: require_role(['admin', 'staff']); require_permission('news.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (26 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Unbounded listing; all CRUD/upload/modal outcomes not verified.

Status: FAIL

### admin/manage-projects.php

Page: admin/manage-projects.php

Route: /admin/manage-projects.php

Role: super_admin; guards: require_role(['admin', 'staff']); require_permission('projects.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (23 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Empty list renders; CRUD/upload/modal outcomes not exhaustively verified.

Status: FAIL

### admin/manage-properties.php

Page: admin/manage-properties.php

Route: /admin/manage-properties.php

Role: super_admin; guards: require_role(['admin', 'staff']); require_permission('properties.view'); require_permission('properties.create'); require_permission('properties.delete').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (30 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Update permission added before freeze; tablet table/editor clipping; CRUD/image paths need full regression.

Status: FAIL

### admin/manage-team.php

Page: admin/manage-team.php

Route: /admin/manage-team.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (18 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Empty state renders; unbounded listing and CRUD/upload coverage incomplete.

Status: FAIL

### admin/send-mail.php

Page: admin/send-mail.php

Route: /admin/send-mail.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (29 nodes); select-name (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Broad hr.view_dashboard permission grants dispatcher access; missing select name; external dispatch deliberately disabled.

Status: FAIL

### admin/view-messages.php

Page: admin/view-messages.php

Route: /admin/view-messages.php

Role: super_admin; guards: require_role(['admin', 'staff']).

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (10 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: List renders; role guard present; unread/read/delete lifecycle not exhaustively verified.

Status: FAIL

### api/payment-webhook.php

Page: api/payment-webhook.php

Route: /api/payment-webhook.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 405; only the specific tests documented in the main report establish workflow behavior.

Desktop: Not visually verified as a normal interface.

Tablet: Not visually verified as a normal interface.

Mobile: Not visually verified as a normal interface.

Security: See security matrix and page findings; not certified.

Accessibility: Not assessed / normal page unavailable.

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: GET correctly returns 405; invalid-signature POST returns 500; gateway matching/signature weaknesses.

Status: FAIL

### api/properties.php

Page: api/properties.php

Route: /api/properties.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 401; only the specific tests documented in the main report establish workflow behavior.

Desktop: Not visually verified as a normal interface.

Tablet: Not visually verified as a normal interface.

Mobile: Not visually verified as a normal interface.

Security: See security matrix and page findings; not certified.

Accessibility: Not assessed / normal page unavailable.

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Invalid/missing bearer returns 401; complete authorized contract/rate-limit/load verification incomplete.

Status: NOT VERIFIED

### api/theme-preference.php

Page: api/theme-preference.php

Route: /api/theme-preference.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 405; only the specific tests documented in the main report establish workflow behavior.

Desktop: Not visually verified as a normal interface.

Tablet: Not visually verified as a normal interface.

Mobile: Not visually verified as a normal interface.

Security: See security matrix and page findings; not certified.

Accessibility: Not assessed / normal page unavailable.

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: GET returns 405; portal client posts to wrong relative URL and does not send CSRF header.

Status: FAIL

### auth/admin-dashboard.php

Page: auth/admin-dashboard.php

Route: /auth/admin-dashboard.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (16 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Tablet clipping; generic document title; audit query not guarded by audit.view; role-sensitive quick links disagree with guards.

Status: FAIL

### auth/client-dashboard.php

Page: auth/client-dashboard.php

Route: /auth/client-dashboard.php

Role: client; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (9 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Email-based OR ownership can link unverified emails to requests; unbounded lists; tablet clipping.

Status: FAIL

### auth/dashboard.php

Page: auth/dashboard.php

Route: /auth/dashboard.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (16 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Role dispatch works; invalid tab names lack useful fallback; destination issues remain.

Status: FAIL

### auth/forgot-password.php

Page: auth/forgot-password.php

Route: /auth/forgot-password.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (7 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Form renders; reset URL uses configured origin after earlier change; external mail/token-delivery flow unverified.

Status: FAIL

### auth/landlord-dashboard.php

Page: auth/landlord-dashboard.php

Route: /auth/landlord-dashboard.php

Role: landlord; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (22 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Exact landlord filtering added; property type/category fields/defaults need end-to-end review; tablet clipping.

Status: FAIL

### auth/login.php

Page: auth/login.php

Route: /auth/login.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (14 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: All ten fixtures authenticate; demo-password assignment remains; no password visibility control; contrast failures.

Status: FAIL

### auth/logout.php

Page: auth/logout.php

Route: /auth/logout.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (5 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: GET confirmation added; POST protection present; stale session can revoke a newer login.

Status: FAIL

### auth/register.php

Page: auth/register.php

Route: /auth/register.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (8 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Forged privileged role becomes client; email verification absent; complete validation/duplicate/race behavior unverified.

Status: FAIL

### auth/reset-password.php

Page: auth/reset-password.php

Route: /auth/reset-password.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (5 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Hashed token and conditional consumption added; valid/invalid screens render; full token lifecycle unverified.

Status: FAIL

### auth/tenant-dashboard.php

Page: auth/tenant-dashboard.php

Route: /auth/tenant-dashboard.php

Role: tenant; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (10 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: No actual checkout; fake utility token; maintenance only audit log; helpdesk persistence absent; tablet clipping.

Status: FAIL

### careers.php

Page: careers.php

Route: /careers.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (14 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Application form renders; no safe external mail integration test; vacancy state/closing date validation and successful upload coverage incomplete.

Status: FAIL

### contact.php

Page: contact.php

Route: /contact.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (8 nodes); frame-title (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid input saved and missing/invalid fields rejected; label/contrast issues; submitted values not consistently preserved.

Status: FAIL

### divisions/agriculture.php

Page: divisions/agriculture.php

Route: /divisions/agriculture.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (10 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Public division renders; contrast/shared navigation/footer defects.

Status: FAIL

### divisions/development.php

Page: divisions/development.php

Route: /divisions/development.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (9 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Public division renders; contrast/shared navigation/footer defects.

Status: FAIL

### divisions/environmental.php

Page: divisions/environmental.php

Route: /divisions/environmental.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (10 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Public division renders; contrast/shared navigation/footer defects.

Status: FAIL

### divisions/estate-management.php

Page: divisions/estate-management.php

Route: /divisions/estate-management.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (9 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Public division renders; contrast/shared navigation/footer defects.

Status: FAIL

### divisions/view.php

Page: divisions/view.php

Route: /divisions/view.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (9 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Agriculture slug fixture renders; missing/inactive/custom slug cases not exhaustively verified.

Status: FAIL

### download-receipt.php

Page: download-receipt.php

Route: /download-receipt.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: document-title (1 nodes); html-has-lang (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid paid fixture renders; unrelated client denied; missing parameter is plain HTTP 200; actual provider verification remains broken.

Status: FAIL

### download.php

Page: download.php

Route: /download.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 404; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: document-title (1 nodes); html-has-lang (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Missing synthetic file returns 404; private legacy fallback removed; successful byte stream and deployment protection unverified.

Status: NOT VERIFIED

### error_pages/403.php

Page: error_pages/403.php

Route: /error_pages/403.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 403; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (5 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: 403 layout renders; contrast failure; shared footer placeholders.

Status: FAIL

### error_pages/404.php

Page: error_pages/404.php

Route: /error_pages/404.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 404; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (5 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: 404 layout renders; contrast failure; routing of real missing URLs in Apache unverified.

Status: FAIL

### error_pages/500.php

Page: error_pages/500.php

Route: /error_pages/500.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 500; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (5 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: 500 layout renders; contrast failure; not consistently wired to uncaught exceptions.

Status: FAIL

### hr/applicants.php

Page: hr/applicants.php

Route: /hr/applicants.php

Role: super_admin; guards: require_login(); require_permission('hr.applicants.view').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (26 nodes); label (1 nodes); select-name (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Unassigned interviewer can read candidate detail; missing department scope; labels/select names and clipped tables.

Status: FAIL

### hr/departments.php

Page: hr/departments.php

Route: /hr/departments.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (53 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Render completed after harness retry; tablet clipping; modal Escape fails; HR audit inserts can omit username.

Status: FAIL

### hr/directory.php

Page: hr/directory.php

Route: /hr/directory.php

Role: super_admin; guards: require_login().

Functionality: Confirmed HTTP 500; normal page unavailable.

Desktop: Not visually verified as a normal interface.

Tablet: Not visually verified as a normal interface.

Mobile: Not visually verified as a normal interface.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: Not assessed / normal page unavailable.

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: HTTP 500 from nonexistent columns; normal interface cannot be visually verified.

Status: FAIL

### hr/download-offer-pdf.php

Page: hr/download-offer-pdf.php

Route: /hr/download-offer-pdf.php

Role: super_admin; guards: require_login().

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: document-title (1 nodes); html-has-lang (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid offer returns plain error text with HTTP 200 due to nonexistent schema columns; token expiry/scope needs correction.

Status: FAIL

### hr/index.php

Page: hr/index.php

Route: /hr/index.php

Role: super_admin; guards: require_login(); require_permission('hr.view_dashboard').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (25 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Dashboard renders; its directory link leads to a 500; counts and lists need scoped authorization.

Status: FAIL

### hr/interviews.php

Page: hr/interviews.php

Route: /hr/interviews.php

Role: super_admin; guards: require_login(); require_permission('hr.interviews.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (23 nodes); label (3 nodes); select-name (2 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Interviewer role denied despite evaluation permission; update query lacks assignment ownership; missing labels/select names.

Status: FAIL

### hr/offers.php

Page: hr/offers.php

Route: /hr/offers.php

Role: super_admin; guards: require_login(); require_permission('hr.offers.create').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (28 nodes); label (2 nodes); select-name (4 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Creation issues offers directly without approval; compensation/access controls and all transitions require correction; missing labels/select names.

Status: FAIL

### hr/onboarding.php

Page: hr/onboarding.php

Route: /hr/onboarding.php

Role: super_admin; guards: require_login(); require_permission('hr.onboarding.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (22 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Empty state renders; provisioning can alter existing roles and exposes invitation tokens; full onboarding flow unverified.

Status: FAIL

### hr/vacancies.php

Page: hr/vacancies.php

Route: /hr/vacancies.php

Role: super_admin; guards: require_login(); require_permission('hr.vacancies.manage').

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot shows portal-layout limitations; 600/768/820/912/1024 measured.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (32 nodes); label (2 nodes); select-name (4 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Creation can bypass publishing approval; unbounded list; labels/select names and tablet clipping.

Status: FAIL

### index.php

Page: index.php

Route: /index.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (41 nodes); frame-title (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Unsupported encryption/app-store claims; fixed-response chatbot; blank map seen in full-page capture; contrast failures.

Status: FAIL

### news-detail.php

Page: news-detail.php

Route: /news-detail.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (8 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Synthetic published article renders; unpublished/unknown record behavior needs additional checks; contrast/shared footer defects.

Status: FAIL

### news.php

Page: news.php

Route: /news.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (8 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Article list/search interface renders; full filter/pagination combinations not verified; shared contrast/footer defects.

Status: FAIL

### offer-response.php

Page: offer-response.php

Route: /offer-response.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (18 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid offer renders; download link fails; GET can update expiry; atomic expiry/state transition protection incomplete.

Status: FAIL

### projects.php

Page: projects.php

Route: /projects.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: See security matrix and page findings; not certified.

Accessibility: color-contrast (6 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Empty state and filters render; shared contrast/footer defects; populated variant not verified.

Status: FAIL

### properties.php

Page: properties.php

Route: /properties.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (11 nodes); select-name (3 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Synthetic listing/filter interface renders; missing select names; all filter/pagination combinations not verified.

Status: FAIL

### property-detail.php

Page: property-detail.php

Route: /property-detail.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (31 nodes); frame-title (1 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid inspection persisted; past inspection accepted; authenticated non-admin code path references unavailable has_permission helper; mock fallback can misrepresent missing data.

Status: FAIL

### services.php

Page: services.php

Route: /services.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: 1440px screenshot reviewed; 1280/1366/1440/1600/1920 geometry checked.

Tablet: 820px screenshot reviewed; 600/768/820/912/1024 measured. Shared accessibility defects remain.

Mobile: 390px screenshot reviewed; 320/360/375/390/412/430 geometry checked. This does not establish all control/scroll states.

Security: Invalid-CSRF request rejected (403). Other guarantees depend on page-specific findings.

Accessibility: color-contrast (17 nodes)

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: Valid consultation persisted; date/status/input boundary coverage incomplete; contrast/shared footer defects.

Status: FAIL

### sitemap.php

Page: sitemap.php

Route: /sitemap.php

Role: guest; guards: Public/inline authorization; see source.

Functionality: Observed HTTP 200; only the specific tests documented in the main report establish workflow behavior.

Desktop: Not visually verified as a normal interface.

Tablet: Not visually verified as a normal interface.

Mobile: Not visually verified as a normal interface.

Security: See security matrix and page findings; not certified.

Accessibility: Not assessed / normal page unavailable.

Issues Fixed: Only applicable earlier shared changes listed in the main report; no page is claimed fully fixed.

Remaining Issue: XML responds; canonical host is hardcoded and does not follow isolated APP_URL; database detail URL coverage needs review.

Status: FAIL

## Role access evidence

Each of ten roles plus guest was checked against 25 protected/dispatcher entry points (275 observations). A 200 missing-parameter error is not proof of private access. Raw response status and redirects are in storage/quality/role-results.json.

| Role | 200 | 302 | 403 | 500 | Other |
|---|---:|---:|---:|---:|---:|
| guest | 1 | 24 | 0 | 0 | 0 |
| admin | 21 | 1 | 2 | 1 | 0 |
| client | 2 | 8 | 15 | 0 | 0 |
| department_manager | 9 | 5 | 10 | 1 | 0 |
| hr_manager | 11 | 4 | 9 | 1 | 0 |
| hr_officer | 10 | 5 | 9 | 1 | 0 |
| interviewer | 4 | 7 | 14 | 0 | 0 |
| landlord | 3 | 7 | 15 | 0 | 0 |
| staff | 7 | 7 | 11 | 0 | 0 |
| super_admin | 23 | 1 | 0 | 1 | 0 |
| tenant | 3 | 7 | 15 | 0 | 0 |

## Dashboard tab verification

These checks verify a 200 response and exactly one visible tab panel; they do not certify the tab's business actions. Screenshots exist at 390, 820 and 1440 pixels.

| Route | Tab | Role | HTTP | Visible panels | Result |
|---|---|---|---:|---:|---|
| auth/admin-dashboard.php | quick | super_admin | 200 | 1 | PASS (visibility only) |
| auth/admin-dashboard.php | users | super_admin | 200 | 1 | PASS (visibility only) |
| auth/admin-dashboard.php | payments | super_admin | 200 | 1 | PASS (visibility only) |
| auth/admin-dashboard.php | audits | super_admin | 200 | 1 | PASS (visibility only) |
| auth/client-dashboard.php | overview | client | 200 | 1 | PASS (visibility only) |
| auth/client-dashboard.php | inspections | client | 200 | 1 | PASS (visibility only) |
| auth/client-dashboard.php | consultations | client | 200 | 1 | PASS (visibility only) |
| auth/landlord-dashboard.php | overview | landlord | 200 | 1 | PASS (visibility only) |
| auth/landlord-dashboard.php | roster | landlord | 200 | 1 | PASS (visibility only) |
| auth/landlord-dashboard.php | upload | landlord | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | overview | tenant | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | pay_rent | tenant | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | pay_electricity | tenant | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | receipts | tenant | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | maintenance | tenant | 200 | 1 | PASS (visibility only) |
| auth/tenant-dashboard.php | helpdesk | tenant | 200 | 1 | PASS (visibility only) |
