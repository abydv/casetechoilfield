# Database Schema — CaseTech Oilfield CMS

Engine: InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci` on every
table. All `id` columns are `BIGINT UNSIGNED AUTO_INCREMENT`. All tables have
`created_at`/`updated_at` (`DATETIME`, nullable, managed by CI4 timestamps)
unless noted. `deleted_at` is added only where soft-delete is genuinely
useful (content the admin might want to restore); everything else hard-deletes
to avoid unbounded table growth on shared hosting.

This document is the reference for the migrations under
`app/Database/Migrations/`. Column lists below are authoritative; migrations
must not silently diverge from this file — update both together.

## 1. Auth & Access

**users**
`id, name, email (unique), password_hash, status (enum: active, disabled),
totp_secret (nullable), totp_enabled (bool), last_login_at, last_login_ip,
avatar_media_id (nullable FK media), created_at, updated_at`

**roles**
`id, name (unique), slug (unique), is_system (bool)` — seeded: Super Admin,
Administrator, Editor, Product Manager, SEO Manager, Sales Manager.

**permissions**
`id, name (unique key, e.g. products.publish), module, description`

**role_permissions**
`role_id FK, permission_id FK` — composite PK.

**user_roles**
`user_id FK, role_id FK` — composite PK (a user may hold >1 role).

**login_attempts**
`id, email, ip_address, success (bool), created_at` — indexed on
`(email, created_at)` and `(ip_address, created_at)` for throttling; rows
older than 30 days purged by cron.

## 2. Pages & Page Builder

**pages**
`id, title, slug (unique), is_homepage (bool), status (enum: draft,
published, scheduled, unpublished), published_at, scheduled_publish_at,
scheduled_unpublish_at, template (varchar, default 'default'),
seo_meta_id FK -> seo_meta (nullable), created_by FK users, updated_by FK
users, created_at, updated_at, deleted_at`

**page_sections**
`id, page_id FK, section_type (varchar, matches a known block enum),
config (JSON), sort_order (int), enabled (bool), visible_desktop (bool),
visible_tablet (bool), visible_mobile (bool), custom_class (varchar,
nullable), created_at, updated_at`
Index: `(page_id, sort_order)`.

**section_types** (reference/lookup table so new block types can be
registered without a schema change to `page_sections`)
`id, key (unique, e.g. 'hero_slider'), label, view_path, config_schema
(JSON, for admin-form generation), is_active (bool)`

## 3. Custom Content Type Builder (spec §19–20)

**content_types**
`id, name, slug (unique), icon, has_categories (bool), has_seo (bool),
supports_revisions (bool), created_at, updated_at`

**custom_fields**
`id, content_type_id FK (nullable — null means "reusable field usable across
types"), field_key (varchar), label, field_type (enum: text, textarea,
richtext, number, email, phone, url, date, time, image, gallery, video, pdf,
file, select, multiselect, checkbox, radio, color, icon, relationship,
repeater), options (JSON — select choices, relationship target type, repeater
sub-fields), validation_rules (varchar, CI4 validation string), sort_order,
is_required (bool)`
Unique: `(content_type_id, field_key)`.

**content_entries**
`id, content_type_id FK, title, slug, status (enum: draft, published,
scheduled, unpublished), published_at, seo_meta_id FK nullable,
sort_order, created_by, updated_by, created_at, updated_at, deleted_at`
Unique: `(content_type_id, slug)`.

**custom_field_values**
`id, content_entry_id FK, custom_field_id FK, value_text, value_int,
value_decimal, value_date, value_json` — only the column matching
`custom_fields.field_type` is populated; keeps values typed/indexable
instead of a single stringly-typed blob. Index `(content_entry_id,
custom_field_id)` unique.

## 4. Products

**product_categories**
`id, parent_id FK self nullable, name, slug (unique), description,
image_media_id FK nullable, is_featured (bool), sort_order, seo_meta_id FK
nullable, created_at, updated_at`

**products**
`id, name, slug (unique), product_code (nullable), category_id FK
product_categories, short_description, full_description (richtext),
main_image_media_id FK media, features (JSON array), benefits (JSON array),
applications (JSON array), video_url (nullable), status (enum: draft,
published, scheduled, unpublished), published_at, sort_order, seo_meta_id FK
nullable, created_by, updated_by, created_at, updated_at, deleted_at`
Indexes: `slug`, `status`, `category_id`, `published_at`.

**product_specifications**
`id, product_id FK, label (e.g. 'Material'), value (e.g. 'Stainless
Steel'), sort_order`
Index `product_id`.

**specification_templates** / **specification_template_items**
Reusable spec sets (spec §13): `specification_templates(id, name)`,
`specification_template_items(id, template_id FK, label, sort_order)` —
applying a template to a product just inserts rows into
`product_specifications`.

**product_images** (gallery, separate from `main_image_media_id`)
`id, product_id FK, media_id FK, sort_order`

**product_documents**
`id, product_id FK, media_id FK, doc_type (enum: datasheet, brochure,
other), label`

**product_related** (self-referencing M:N)
`product_id FK, related_product_id FK` — composite PK.

**product_category_map** — only needed if a product can belong to more than
one category; v1 uses a single `products.category_id` FK, this table is
reserved for when that's insufficient (documented here, not created until
needed, per "avoid unnecessary tables").

## 5. Services

**service_categories**: same shape as `product_categories`.

**services**
`id, name, slug (unique), category_id FK nullable, description
(richtext), features (JSON), applications (JSON), process (JSON — ordered
steps), status, published_at, sort_order, seo_meta_id FK, created_by,
updated_by, created_at, updated_at, deleted_at`

**service_images**: `id, service_id FK, media_id FK, sort_order`

**service_documents**: same shape as `product_documents`.

**service_related_products** / **service_related_projects**: M:N join
tables (`service_id`, `product_id`/`project_id`).

## 6. Projects / Case Studies

**projects**
`id, title, slug (unique), client, location, industry_id FK nullable,
project_date (date), description (richtext), challenge (richtext),
solution (richtext), results (richtext), status, published_at, sort_order,
seo_meta_id FK, created_by, updated_by, created_at, updated_at, deleted_at`

**project_images**: `id, project_id FK, media_id FK, sort_order`
**project_videos**: `id, project_id FK, video_url, title`
**project_documents**: same shape as `product_documents`.
**project_related_services** / **project_related_products**: M:N.

## 7. Other content modules (spec §18)

- **industries**: `id, name, slug, description, image_media_id, seo_meta_id, sort_order`
- **clients**: `id, name, logo_media_id, website_url, sort_order`
- **testimonials**: `id, author_name, author_title, company, photo_media_id, quote, rating (tinyint nullable), sort_order, status`
- **team_members**: `id, name, role, photo_media_id, bio, sort_order, status`
- **certifications**: `id, name, issuing_body, image_media_id, issued_date, expiry_date, sort_order`
- **blog_categories**: same shape as product_categories
- **blog_posts**: `id, title, slug, category_id FK, excerpt, body (richtext), featured_image_media_id, author_id FK users, status, published_at, seo_meta_id, created_at, updated_at, deleted_at`
- **faqs**: `id, question, answer (richtext), group_label (nullable), sort_order, status`
- **downloads**: `id, title, media_id FK, category (nullable), description, download_count (int, incremented on request)`
- **galleries** / **gallery_images**: `galleries(id, title, slug)`,
  `gallery_images(id, gallery_id FK, media_id FK, caption, sort_order)`

## 8. Sliders

**sliders**: `id, name, slug, autoplay (bool), interval_ms (int), created_at, updated_at`
**slider_slides**: `id, slider_id FK, image_media_id FK, mobile_image_media_id
FK nullable, heading, subheading, cta_label, cta_url, sort_order, status,
start_date nullable, end_date nullable`

## 9. Media Library

**media_folders**: `id, parent_id FK self nullable, name, slug`

**media**
`id, folder_id FK nullable, filename (stored, sanitized), original_filename,
mime_type, size_bytes, width (nullable), height (nullable), alt_text,
caption, description, uploaded_by FK users, created_at`
Variants are **not** separate rows: `media_variants` below tracks only the
handful actually generated.

**media_variants**
`id, media_id FK, variant (enum: thumb, medium, webp, avif), filename,
width, height, size_bytes`
Unique `(media_id, variant)` — enforces "only the variants actually used"
(spec §21/§7 of architecture.md).

## 10. Menus

**menus**: `id, name, slug (e.g. 'main', 'footer'), location (varchar)`

**menu_items**
`id, menu_id FK, parent_id FK self nullable, label, link_type (enum: page,
product, category, service, project, custom_url, content_entry), link_target
(varchar/id depending on type), url_override (nullable), icon (nullable),
open_new_tab (bool), sort_order, mobile_hidden (bool)`

## 11. Header / Footer / Global Layout

Rather than dedicated tables, header/footer/announcement-bar configuration is
stored as structured rows in **site_settings** (see §16) under namespaced
keys (`header.*`, `footer.*`, `announcement_bar.*`), because this content is
singleton configuration, not a repeating content type — avoids empty
one-row-forever tables.

## 12. Forms (Form Builder)

**forms**
`id, name, slug (unique), recipient_emails (JSON array), success_message,
redirect_url (nullable), store_in_db (bool, default true), captcha_provider
(enum: none, turnstile, recaptcha), auto_response_enabled (bool),
auto_response_subject, auto_response_body, created_at, updated_at`

**form_fields**
`id, form_id FK, field_key, label, field_type (enum: text, email, phone,
textarea, dropdown, checkbox, radio, file, date, number, hidden),
options (JSON), is_required (bool), sort_order, validation_rules`

**form_submissions**
`id, form_id FK, data (JSON — field_key => value), source_url, ip_address,
user_agent, status (enum: new, read, spam), created_at`
Index `(form_id, created_at)`.

**form_submission_files**
`id, submission_id FK, media_id FK` (uploads submitted via a file field are
stored through the same media pipeline/validation as the media library).

## 13. Enquiries / Leads (spec §15, §23)

**enquiries**
`id, product_id FK nullable, service_id FK nullable, form_submission_id FK
nullable (links back to the raw submission if it came via the form
builder), name, company, email, phone, quantity (nullable), message,
source_url, status (enum: new, contacted, qualified, quoted, won, lost,
spam, closed), assigned_to FK users nullable, follow_up_date nullable,
created_at, updated_at`

**enquiry_notes**
`id, enquiry_id FK, user_id FK, note, created_at`

## 14. SEO

**seo_meta**
`id, seo_title, meta_description, canonical_url (nullable), robots (varchar,
default 'index,follow'), focus_keyword, og_title, og_description,
og_image_media_id FK nullable, twitter_title, twitter_description,
twitter_image_media_id FK nullable, schema_json (JSON, nullable)`
Every content table above holds a nullable FK to this table rather than
duplicating ~12 SEO columns per content type.

**redirects**
`id, from_path (unique, normalized without domain), to_path, status_code
(enum: 301, 302), hit_count (int, default 0), is_active (bool), created_at`

**not_found_logs**
`id, path, referrer, hit_count (int), first_seen_at, last_seen_at` —
upserted (increment hit_count) rather than one row per hit, to keep this
table bounded.

## 15. Revisions

**revisions**
`id, revisionable_type (varchar, e.g. 'page', 'product'), revisionable_id
(bigint), data (JSON snapshot), created_by FK users, created_at`
Index `(revisionable_type, revisionable_id, created_at)`. A scheduled cron
(`spark revisions:prune`) keeps only the newest N (default 20) revisions per
record, deleting older rows — bounds table growth per spec §30.

## 16. Settings

**site_settings**
`key (varchar, PK), value (JSON), group (varchar, e.g. 'general', 'smtp',
'captcha', 'social'), is_secret (bool)` — single flexible key/value table
for Settings → General/SMTP/CAPTCHA/Cloudflare/etc. `is_secret` rows (SMTP
password, API keys) are encrypted at rest using CI4's `Encryption` service
before storage and are excluded from any settings export/API response.

**theme_settings**
`key (varchar, PK), value (varchar)` — colors, fonts, spacing, button
radius; kept separate from `site_settings` so the theme customizer can
validate/whitelist keys independently (spec §33 — "do not allow arbitrary
settings to break the frontend").

## 17. Popups / Announcements

**popups**
`id, type (enum: announcement_bar, promo_popup, newsletter_popup,
product_popup), title, content (richtext/JSON), page_targeting (JSON —
'all' or list of page ids/paths), delay_seconds, start_date, end_date,
frequency (enum: always, once_per_session, once_per_day), show_desktop
(bool), show_mobile (bool), status`

## 18. Audit Log (spec §37 — DB-backed, not files)

**audit_logs**
`id, user_id FK nullable, action (varchar, e.g. 'product.update'), module,
record_type, record_id, before_data (JSON nullable), after_data (JSON
nullable), ip_address, created_at`
Index `(module, record_type, record_id)`, `(created_at)`. Retention: cron
purges rows older than the configured window (default 180 days) — keeps
this high-write table from growing unbounded, per spec §37.

## 19. Backups (spec §38–47)

**backup_settings**
`key (PK), value` (schedule, retention count N, Google Drive folder id,
enabled flags) — reuses the `site_settings` pattern conceptually but kept as
its own small table for clarity in the Backups admin screen.

**backup_records**
`id, started_at, finished_at, status (enum: running, success, failed),
archive_filename, archive_size_bytes, database_checksum, archive_checksum,
file_count, app_version, schema_version, drive_file_id (nullable),
drive_folder_path, error_message (nullable)`
This is the row the Backups dashboard table (spec §43) renders directly.

**oauth_connections**
`id, provider (enum: google_drive), account_email, access_token (encrypted),
refresh_token (encrypted), token_expires_at, connected_by FK users,
connected_at`

## 20. API / Integrations (spec §58)

**api_keys**
`id, label, key_hash, scopes (JSON), created_by FK users, last_used_at,
revoked_at nullable`

**webhooks**
`id, event (varchar), target_url, secret (encrypted), is_active,
last_triggered_at, last_response_code`

## 21. Jobs (background work without a daemon — architecture.md §9)

**jobs**
`id, type (varchar, e.g. 'backup.run', 'sitemap.regenerate'), payload
(JSON), status (enum: pending, running, done, failed), attempts (int),
run_after (datetime), created_at, updated_at, error_message nullable`
Polled by `php spark queue:work` via cron; failed jobs are retried up to a
configured max then left in `failed` for admin visibility rather than
retried forever.

## 22. Indexing summary (spec §54)

Every table above indexes, at minimum: `slug`/unique natural keys, `status`,
`published_at`, all FK columns, `created_at`/`updated_at` where the admin
UI sorts or filters by them. No table is designed to require loading an
unbounded result set into PHP memory — all listing queries are paginated at
the model layer by default (CodeIgniter's `paginate()`).

## 23. Multilingual readiness (spec §57)

No `language` column is added in v1 to avoid premature complexity, but every
content table's design (separate `slug` per row, SEO in a joined table, no
language-sensitive data baked into non-content tables) means a future
`language` + `translation_group_id` pair of columns can be added to the
content tables via a single additive migration without restructuring
relationships — this is a documented constraint on future migrations, not
code that exists today.
