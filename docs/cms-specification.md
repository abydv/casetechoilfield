# CMS Functional Specification — CaseTech Oilfield CMS

Maps the business requirements (master spec §2–§37, §55–§65) onto concrete
admin screens, workflows, and behavior. Backed by database-schema.md and
implemented per architecture.md. Read alongside those two documents.

## 1. Admin shell

- `/admin` login (rate-limited, optional TOTP 2FA), role-based nav.
- Dashboard widgets: content counts (pages/products/services/projects),
  new enquiries (7-day), SEO health summary, System Health summary, Backup
  status, recent activity feed (from `audit_logs`).
- Every destructive action (delete page, delete product, delete media in
  use, revoke API key) requires a confirmation modal naming what will be
  affected.
- List views: search box, status filter, category filter, sortable columns,
  bulk actions (publish/unpublish/delete) where safe, pagination (never an
  unbounded table).

## 2. Pages & Homepage (spec §7–§8)

Workflow: Pages → New Page → enter title/slug → Add Section → pick a block
type from the palette (spec §8 list) → configure block fields in a side
panel → drag to reorder → toggle per-device visibility → Preview (renders
the real frontend template against unsaved draft data via a signed preview
token, no separate preview engine) → Publish (immediate) or Schedule
(publish/unpublish at a future timestamp, applied by the job runner from
`jobs`/cron).

The homepage is `pages.is_homepage = 1` — editing it is identical to editing
any page, so "homepage sections" reuses the whole page-builder UI rather
than being a special case admins must learn separately.

Each section (`page_sections` row): Enable/disable toggle, Duplicate,
Delete, drag handle, and a settings panel exposing exactly the fields
`section_types.config_schema` declares for that block type — the admin UI
is generated from that schema, not hand-built per block, so new block types
only require a developer to add one schema + view, never a new admin form.

## 3. Header, Footer, Menus (spec §9–§11)

**Settings → Header**: logo (media picker) + max display dimensions, menu
selector (which `menus` row renders as the primary nav), phone/email/CTA
button (pulled from `site_settings.general.*` by default, overridable),
social links, announcement bar (enable/disable, message, link, dates),
sticky on/off, mobile menu style.

**Settings → Footer**: logo, description, up to N configurable columns each
bound to a `menus` row or free-form link list, contact block (from
`site_settings.general.*`), social links, certification logos (media
picker, multi), newsletter signup (binds to a `forms` row), legal links
(Privacy/Terms pages), copyright text (supports a `{year}` token resolved
at render time so it never goes stale).

**Menu Builder** (`/admin/menus`): drag-and-drop tree, "Add Item" supports
the `link_type` values in `menu_items` (page, product, category, service,
project, custom content entry, custom URL, external URL). Nested items
render as dropdowns; a "Mega Menu" layout is just a section-type available
on a top-level menu item's config (columns of sub-links + optional featured
image/promo block) — no separate mega-menu subsystem.

## 4. Products (spec §12–§15)

`/admin/products` — full CRUD against the `products` schema in
database-schema.md §4. On Publish:

1. Row becomes visible on `/products` (paginated, filterable by category).
2. `/products/{slug}` detail route resolves automatically — no controller
   change needed per product (architecture.md §4).
3. Appears on its `product_categories` page and in any Product
   Listing/Carousel block configured to include that category.
4. `sitemap.xml` regeneration is flagged (cron picks it up within the
   configured interval, or immediately if triggered synchronously — see
   §12 below).
5. Breadcrumbs render from the category chain automatically.
6. SEO fields (`seo_meta` row) apply to `<title>`, meta description,
   canonical, OG/Twitter tags, and JSON-LD `Product` schema.
7. Included in site search index (§10 below).
8. "Request a Quote" button on the detail page opens the enquiry form
   (writes to `enquiries` with `product_id` set, per spec §15's field list).

**Specification Builder**: "+ Add Specification" on the product edit screen
appends a `product_specifications` row (label/value/order); "Apply
Template" bulk-inserts from a `specification_templates` entry, then the
admin edits values in place — templates never lock the admin out of custom
rows.

**Categories** (`/admin/product-categories`): CRUD, parent/child (self-FK),
drag reorder, image, description, SEO, "Featured" flag (surfaces the
category in a Category Grid block). Category pages auto-generate at
`/products/category/{slug}`.

## 5. Services & Projects (spec §16–§17)

Same CRUD/publish/SEO/breadcrumb pattern as Products, at `/services`,
`/services/{slug}`, `/projects`, `/projects/{slug}`. Projects additionally
carry Challenge/Solution/Results as distinct richtext fields (rendered as a
structured case-study layout, not a single body field) and support
Client/Location/Industry/Date metadata plus related Services/Products
pickers.

## 6. Other content modules (spec §18)

Industries, Clients, Testimonials, Team, Certifications, Blog/News, FAQs,
Downloads, Galleries each get a standard list+edit admin screen following
the same CRUD/status/sort-order pattern; several are pure "components" only
ever displayed via page-builder blocks (e.g. Testimonials, Clients, FAQs)
rather than having their own public listing route — the block references
them by category/tag, not the reverse.

## 7. Custom Content Type Builder (spec §19–§20)

`/admin/content-types` (Super Admin / Administrator only): "New Content
Type" → name, slug, icon, toggle categories/SEO/revisions → "Add Field" per
the `custom_fields.field_type` enum in database-schema.md §3, with
drag-reorder and required/validation settings. Once saved, a new
`/admin/content/{type_slug}` list screen and public
`/{type_slug}`/`/{type_slug}/{entry_slug}` routes exist immediately — this
is the mechanism spec §19 asks for ("generate the necessary structure ...
without requiring manual PHP development"), implemented via the generic
`ContentTypeController` (architecture.md §5), never via code generation or
dynamic SQL DDL. Field type "Relationship" stores a foreign
`content_entry_id`/`product_id`/etc. reference in `value_json`; "Repeater"
stores an ordered JSON array of sub-field value sets in `value_json`.

## 8. Media Library (spec §21)

`/admin/media` — grid/list toggle, folder tree (`media_folders`), drag-drop
upload (single + bulk), search by filename/alt text, filter by type/folder,
rename, replace-in-place (keeps the same `media_id` so every reference
site-wide updates automatically), delete (blocked with a clear message if
the file is still referenced anywhere — the system checks
`product_images`, `page_sections.config`, etc. before allowing delete).
Each file's edit panel: alt text, caption, description, read-only
dimensions/size/mime.

On upload: original is stored as-is (resized down only if it exceeds a
configurable max dimension, to cap storage); exactly the variants declared
in `media_variants` policy (thumb + medium + webp) are generated once and
never regenerated — this is the concrete mechanism behind architecture.md
§7 rule 1. Frontend `<picture>` output always requests the smallest variant
that satisfies the layout, with native lazy-loading (`loading="lazy"`) on
every non-hero image.

## 9. Form Builder & Leads (spec §22–§23)

`/admin/forms` — "New Form" → add fields from the `form_fields.field_type`
enum with drag reorder and validation → Settings tab: recipient email(s),
SMTP (site-wide, see §11), auto-response (subject/body with `{field_key}`
tokens), CAPTCHA provider toggle, success message vs. redirect URL,
store-in-DB toggle. A form is embedded by referencing its slug from a
Contact Form block anywhere in the page builder, or by binding it as the
homepage/footer newsletter form.

`/admin/enquiries` (unifies `form_submissions` for generic forms and
`enquiries` for product/service quote requests into one Leads view filtered
by source): status pipeline (New → Contacted → Qualified → Quoted → Won /
Lost / Spam / Closed), search/filter, assign to user, notes with
timestamps, follow-up date, CSV export, file attachment download, and
direct links to the related product/service record.

## 10. Site Search & Admin Search (spec §55)

Public search (`/search?q=`) queries `pages`, `products`, `services`,
`projects`, `blog_posts`, `downloads` via indexed `LIKE`/`MATCH AGAINST`
(MySQL fulltext index on title/description columns) — no external search
service, keeping it shared-hosting friendly. Admin global search
additionally covers `media` (filename/alt), `enquiries`, and users the
current role can see, using the same fulltext approach.

## 11. SEO System & Health (spec §26–§28)

Every content edit screen has an "SEO" tab bound to that record's
`seo_meta` row: SEO title (with live character-count/preview), meta
description, slug, canonical override, robots directives, focus keyword,
OG/Twitter fields (falls back to the main title/description/image when
left blank), schema JSON (auto-generated per content type — `Product`,
`Article`, `Organization`, `BreadcrumbList` — with an advanced override).

`/admin/seo/health` runs scheduled + on-demand checks: missing title/
description, duplicate titles across records, images missing alt text,
missing canonical, pages set `noindex`, sitemap generation errors, and
cross-references `not_found_logs` for real 404s worth redirecting.

`/admin/redirects`: add `from_path -> to_path` (301/302), hit counter,
enable/disable. `not_found_logs` auto-populates from real 404 responses
(deduped, hit-counted) so the admin can turn frequent 404s into redirects
with one click. `sitemap.xml`/`robots.txt` are generated routes (not static
files) reading live `status='published'` rows across all sitemap-eligible
tables, cached and invalidated on publish/unpublish.

## 12. Drafts, Scheduling, Revisions (spec §30–§31)

Standard status enum (`draft, published, scheduled, unpublished`) on every
major content table. `scheduled_publish_at`/`scheduled_unpublish_at` are
applied by a per-minute cron job (`spark content:apply-schedule`), not by
request-time checks alone (so scheduled changes go live even with no
traffic). Every save to a revision-enabled type writes a
`revisions` snapshot (database-schema.md §15); the edit screen's "History"
tab lists revisions with Preview and Restore (restore itself creates a new
revision first, so it's never destructive).

## 13. Global Settings & Theme Customizer (spec §32–§33)

`/admin/settings/general`: company name, logo, favicon, phone, email,
address, WhatsApp, social links, business hours, copyright text, default
SEO template, Analytics ID, Search Console verification — all
`site_settings` rows consumed by a single `Settings` service/helper so
every template reads `setting('general.phone')` instead of hard-coding
values; this is the literal mechanism behind "change the phone number once,
it updates everywhere" (spec §70).

`/admin/settings/theme`: color pickers (primary/secondary/accent/text/
background/button), font selectors (heading/body, from a whitelisted
Google Fonts subset self-hosted per performance rules), spacing/radius
sliders — all validated server-side against safe ranges/enums before
writing to `theme_settings`, then compiled into a small CSS custom-
properties block injected in `<head>` (no arbitrary CSS injection, per spec
§33's "do not allow arbitrary settings to break the frontend").

## 14. Popups (spec §34)

`/admin/popups` — CRUD against the `popups` table; page targeting (all
pages or a specific list), delay, date range, frequency
(always/once-per-session/once-per-day via a small localStorage flag read
client-side, no server session bloat), device targeting.

## 15. Users & Roles (spec §35)

`/admin/users` — invite/create user, assign one or more roles. `/admin/roles`
(Super Admin only) — manage the `permissions` matrix per role; the six
seeded roles from spec §35 ship with sensible default permission sets
(e.g. Product Manager: full CRUD on products/categories, read-only
elsewhere; SEO Manager: SEO tabs + redirects + sitemap, no content CRUD).

## 16. Security, Audit, System Health (spec §36–§37, §62)

Covered mechanically in architecture.md §8; the admin-facing pieces are:
`/admin/settings/security` (session timeout, login attempt limits, 2FA
enforcement toggle per role, security headers status), `/admin/audit-log`
(searchable/filterable view over `audit_logs`), `/admin/system-health`
(PHP/CI4/MySQL versions, disk/inode estimates from a cached filesystem
scan, writable/upload/cache/log sizes, backup status, Google Drive status,
SMTP status, CAPTCHA status, SSL status via a live cert check, cron
heartbeat — last time the scheduled runner actually executed, written to
`site_settings['system.cron_last_run']` by the cron entrypoint itself so a
stalled cron is visible, not silent).

## 17. SMTP & CAPTCHA (spec §24–§25)

`/admin/settings/smtp`: host/port/encryption/username/password (stored
encrypted via `site_settings.is_secret`)/from name/from email/reply-to +
"Send Test Email" button (queues a `jobs` row of type `email.test`, result
surfaced via a status poll, not a synchronous request that can time out on
shared hosting).

`/admin/settings/captcha`: enable/configure Cloudflare Turnstile and/or
Google reCAPTCHA independently; site key is safe to expose to the frontend
via config injection, secret key stored encrypted and used only
server-side during form verification.

## 18. 404, Legal Pages, Cookies (spec §64–§65)

The 404 page is a normal `pages` record (`slug = '404'`, flagged as the
system 404 template) so its message/search box/suggested pages/contact CTA
are fully CMS-editable rather than a hard-coded view. Privacy Policy,
Terms, Cookie Policy are ordinary Pages (per the audit's finding that the
live site has no existing legal copy — see current-site-audit.md §13) with
a lightweight cookie-notice popup (`popups` type) linking to them.

## 19. Import/Export (spec §56)

`/admin/{module}/import` accepts CSV for Products, Services, Projects,
Clients, FAQs, Testimonials; processed in chunks via the `jobs` queue
(never a single long synchronous request), with a per-row validation
report the admin can download and a "retry failed rows only" action.
Export mirrors this as a synchronous streamed CSV download for reasonably
sized tables (paginated query, not a full in-memory load).

## 20. Definition of done for this module

Per spec §67's acceptance criteria: each item in this document is "done"
only when (a) the admin screen exists and is usable without touching code,
(b) the corresponding public route/render path works end-to-end, and
(c) an automated test exercises the core CRUD + publish flow (see
docs/architecture.md and the `tests/` tree once implemented).
