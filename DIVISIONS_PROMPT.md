# Reusable implementation prompt

In this Sarkin Mota HQ PHP/PDO workspace, add a Super Admin-only Manage Divisions page for corporate sections such as Agriculture, Estate & Property, Environmental, and Development. Let the Super Admin add and edit division names, unique permanent URL slugs, short descriptions, plain-text page content, publication status, and display order. Add a visible Super Admin sidebar link and enforce authorization on the server for both reads and writes.

Replace the hardcoded Divisions links in the shared desktop and mobile header with the same persisted, ordered list of published divisions. Preserve the four existing divisions and their existing page URLs and designs. New divisions must open a shared public page template without requiring new PHP files. Hidden new divisions and unknown slugs must return HTTP 404. Hide empty menu groups. Explain that hiding a legacy division removes its menu entry while its existing page remains accessible.

Use the existing database settings infrastructure, portal layout, authentication, CSRF protection, and audit logging. Validate input, escape output, use prepared statements, prevent duplicate slugs and stale concurrent saves, and redirect after successful submission. Default new entries to hidden. Preserve form values on validation errors. Match the existing responsive light/dark theme. Run PHP syntax checks and verify Super Admin access, denied access for other roles, creation, editing, publishing, hiding, ordering, desktop/mobile links, existing URLs, and unknown-division 404 responses. Report any checks blocked by unavailable services.

# Using the implemented feature

- Sign in as Super Admin and select **Manage Divisions** in the sidebar, or open `admin/manage-divisions.php`.
- Enter a name, unique slug (for example `renewable-energy`), summary, content, and display order.
- Check **Published in Divisions menu** and save to show the section in both header menus.
- Use **Edit** to update details, change order, or uncheck publication to hide it. Slugs remain permanent.
- Existing division page bodies remain in their original PHP files; the manager changes their menu metadata.

Data is stored under `corporate_divisions` in the existing `system_settings` table. No new migration is required when the existing system-settings migration has already been applied. Until the first save, the four original divisions are supplied as defaults.

# Manual verification

1. As a guest and as a non-Super-Admin user, request the manager and attempt a POST; access must be denied or redirected without saving.
2. As Super Admin, add a hidden division, then publish it and verify its page and both header menus.
3. Edit its name, description, content, and order; verify escaped text and matching desktop/mobile order.
4. Hide it and verify its public URL returns 404; hide all entries and verify neither menu displays an empty Divisions group.
5. Check all four legacy links still reach their original pages.
6. Submit a duplicate slug, invalid order, missing name, and invalid CSRF token; verify errors without saved changes.
7. Open the manager in two tabs, save in one, then submit the stale second form; verify the stale save is rejected.
