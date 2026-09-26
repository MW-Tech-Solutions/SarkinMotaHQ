Bootstrap Icons standardization is complete for hardcoded application interface symbols. The full PHP, HTML, JavaScript, CSS, SQL, and documentation scan found interface emojis in the shared theme controls and receipt print action. Decorative bullets, arrows, the chat status dot, and the chat close symbol were also standardized. Database content was not modified. Copyright text, degree notation, CLI test/migration checkmarks, and the property illustration remain appropriate non-interface-icon content.

| Component | Replacement / improvement |
| --- | --- |
| Public, mobile, and portal appearance controls | `bi-sun`, `bi-moon-stars`, `bi-circle-half`; visible Light/Dark/Auto labels, synchronized `aria-pressed`, contrasting active state |
| Receipt print / PDF action | `bi-printer` with retained action text |
| Service lists and footer division links | `bi-dot` |
| Chat status and close control | `bi-circle-fill` with accessible Online label; `bi-x-lg` with Close AI assistant label |
| Directional links | `bi-arrow-left`, `bi-arrow-right` |
| Shared flash messages | `bi-check-circle`, `bi-x-circle`, `bi-exclamation-triangle`, `bi-info-circle`; theme-aware colors |
| Text action buttons | Consistent trash, pencil, eye, plus-circle, download, send, display, and phone icons where corresponding simple action controls exist |
| Invalid icon names | `bi-flask` to `bi-eyedropper`, `bi-handshake` to `bi-people`, `bi-ruler` to `bi-rulers` |
| Existing icon accessibility | Decorative icons hidden from screen readers; carousel previous/next controls explicitly labeled |

Bootstrap Icons **1.11.2** CSS and both font formats are hosted locally, with their license. `includes/icon_assets.php` is the single asset declaration, included once by the shared header or standalone receipt head. All existing role dashboards use the shared header. Nested error-page asset paths were corrected. No competing icon-library imports were found. The obsolete SVG theme implementation was removed from `main.js`; duplicated fragments that prevented that script from parsing were resolved, leaving `theme.js` as the theme controller.

Verification completed:

- `node tests/check-icons.cjs`: 57 UI source files scanned; 115 static icon classes valid against the bundled CSS; dynamic flash icon names valid; font signatures valid; no hardcoded UI emojis or unlabeled directly icon-only controls found.
- The same check exercises all nine theme buttons, selected states, explicit Light/Dark preference, and Auto response to OS changes.
- All 62 PHP files passed syntax checks. Both application JavaScript files passed Node syntax checks.
- Headless Chrome component smoke check using the actual public theme-button markup, shared icon styles, local fonts, and theme engine passed at 320, 768, and 1440 pixels. No missing icon content, font failures, component overflow, or JavaScript errors occurred. Light/Dark/Auto and OS changes passed.
- Full live-page and authenticated role walkthroughs were **not** performed: the local website was unavailable. The browser check validates the components in isolation, not every complete page layout. Database-backed business tests were not run for this interface change.

Files changed or added are listed below. Existing pages listed here also receive decorative-icon accessibility attributes and/or directional-link standardization.

- [ICON_STANDARDIZATION_REPORT.md](ICON_STANDARDIZATION_REPORT.md)
- [about.php](about.php)
- [admin/manage-branding.php](admin/manage-branding.php)
- [admin/manage-careers.php](admin/manage-careers.php)
- [admin/manage-news.php](admin/manage-news.php)
- [admin/manage-projects.php](admin/manage-projects.php)
- [admin/manage-properties.php](admin/manage-properties.php)
- [admin/view-messages.php](admin/view-messages.php)
- [assets/css/icons.css](assets/css/icons.css)
- [assets/js/main.js](assets/js/main.js)
- [assets/js/theme.js](assets/js/theme.js)
- [assets/vendor/bootstrap-icons/LICENSE](assets/vendor/bootstrap-icons/LICENSE)
- [assets/vendor/bootstrap-icons/bootstrap-icons.min.css](assets/vendor/bootstrap-icons/bootstrap-icons.min.css)
- [assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff](assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff)
- [assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2](assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2)
- [auth/dashboard.php](auth/dashboard.php)
- [auth/forgot-password.php](auth/forgot-password.php)
- [auth/login.php](auth/login.php)
- [auth/reset-password.php](auth/reset-password.php)
- [careers.php](careers.php)
- [contact.php](contact.php)
- [divisions/agriculture.php](divisions/agriculture.php)
- [divisions/development.php](divisions/development.php)
- [divisions/environmental.php](divisions/environmental.php)
- [divisions/estate-management.php](divisions/estate-management.php)
- [download-receipt.php](download-receipt.php)
- [error_pages/403.php](error_pages/403.php)
- [error_pages/404.php](error_pages/404.php)
- [error_pages/500.php](error_pages/500.php)
- [includes/admin_sidebar.php](includes/admin_sidebar.php)
- [includes/footer.php](includes/footer.php)
- [includes/functions.php](includes/functions.php)
- [includes/header.php](includes/header.php)
- [includes/icon_assets.php](includes/icon_assets.php)
- [index.php](index.php)
- [news-detail.php](news-detail.php)
- [news.php](news.php)
- [projects.php](projects.php)
- [properties.php](properties.php)
- [property-detail.php](property-detail.php)
- [services.php](services.php)
- [tests/check-icons.cjs](tests/check-icons.cjs)
