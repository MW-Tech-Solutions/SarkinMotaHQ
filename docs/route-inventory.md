# Discovered routes (source inventory)

Each PHP entry point and its forms is listed before interface changes. Tab variants are included in the JSON inventory. Internal includes, CLI tools, tests, database and scratch scripts are not user routes and must be denied by the web server.

| Route | Guards | Forms | POST actions |
|---|---|---:|---|
| /about.php | Public / inline checks | 0 |  |
| /activate-account.php | Public / inline checks | 1 | submit_activation |
| /admin/manage-branding.php | require_super_admin() | 2 | reset_branding, save_branding, allow_theme_switching |
| /admin/manage-careers.php | require_role(['admin', 'staff']); require_permission('careers.manage') | 2 | add_vacancy, delete_vacancy |
| /admin/manage-consultations.php | require_permission('consultations.manage') | 1 | update_consultation_status |
| /admin/manage-divisions.php | require_super_admin() | 2 | active |
| /admin/manage-inspections.php | require_permission('inspections.manage') | 1 | update_inspection_status |
| /admin/manage-news.php | require_role(['admin', 'staff']); require_permission('news.manage') | 3 | add_article, update_article, delete_article |
| /admin/manage-projects.php | require_role(['admin', 'staff']); require_permission('projects.manage') | 3 | add_project, update_project, delete_project |
| /admin/manage-properties.php | require_role(['admin', 'staff']); require_permission('properties.view'); require_permission('properties.create'); require_permission('properties.delete') | 3 | add_property, update_property, delete_property |
| /admin/manage-team.php | require_login() | 3 | is_active, is_active |
| /admin/send-mail.php | require_login() | 1 |  |
| /admin/view-messages.php | require_role(['admin', 'staff']) | 2 | toggle_status, delete_message |
| /api/payment-webhook.php | Public / inline checks | 0 |  |
| /api/properties.php | Public / inline checks | 0 |  |
| /api/theme-preference.php | Public / inline checks | 0 |  |
| /auth/admin-dashboard.php | require_login() | 1 | admin_add_user |
| /auth/client-dashboard.php | require_login() | 1 |  |
| /auth/dashboard.php | require_login() | 0 |  |
| /auth/forgot-password.php | Public / inline checks | 1 |  |
| /auth/landlord-dashboard.php | require_login() | 1 | landlord_upload |
| /auth/login.php | Public / inline checks | 1 |  |
| /auth/logout.php | Public / inline checks | 0 |  |
| /auth/register.php | Public / inline checks | 1 |  |
| /auth/reset-password.php | Public / inline checks | 1 |  |
| /auth/tenant-dashboard.php | require_login() | 4 | pay_rent_submit, pay_electricity_submit, submit_maintenance |
| /careers.php | Public / inline checks | 1 | apply_job |
| /contact.php | Public / inline checks | 1 |  |
| /divisions/agriculture.php | Public / inline checks | 0 |  |
| /divisions/development.php | Public / inline checks | 0 |  |
| /divisions/environmental.php | Public / inline checks | 0 |  |
| /divisions/estate-management.php | Public / inline checks | 0 |  |
| /divisions/view.php | Public / inline checks | 0 |  |
| /download-receipt.php | require_login() | 0 |  |
| /download.php | require_login() | 0 |  |
| /error_pages/403.php | Public / inline checks | 0 |  |
| /error_pages/404.php | Public / inline checks | 0 |  |
| /error_pages/500.php | Public / inline checks | 0 |  |
| /hr/applicants.php | require_login(); require_permission('hr.applicants.view') | 2 | update_applicant |
| /hr/departments.php | require_login() | 3 |  |
| /hr/directory.php | require_login() | 5 |  |
| /hr/download-offer-pdf.php | require_login() | 0 |  |
| /hr/index.php | require_login(); require_permission('hr.view_dashboard') | 0 |  |
| /hr/interviews.php | require_login(); require_permission('hr.interviews.manage') | 2 | schedule_interview, submit_evaluation |
| /hr/offers.php | require_login(); require_permission('hr.offers.create') | 1 | create_offer, approve_offer |
| /hr/onboarding.php | require_login(); require_permission('hr.onboarding.manage') | 2 | toggle_checklist, provision_staff_account |
| /hr/vacancies.php | require_login(); require_permission('hr.vacancies.manage') | 2 | save_vacancy, update_status |
| /index.php | Public / inline checks | 0 |  |
| /news-detail.php | Public / inline checks | 0 |  |
| /news.php | Public / inline checks | 1 |  |
| /offer-response.php | Public / inline checks | 1 | submit_offer_response |
| /projects.php | Public / inline checks | 0 |  |
| /properties.php | Public / inline checks | 1 |  |
| /property-detail.php | Public / inline checks | 1 | book_inspection |
| /services.php | Public / inline checks | 1 | request_consultation |
| /sitemap.php | Public / inline checks | 0 |  |
