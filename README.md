# CaseTech Oilfield CMS

A custom CodeIgniter 4 CMS and corporate website for
[CaseTech Oilfield Services](https://casetechoilfield.com), built to run on
Hostinger shared hosting and to let a non-developer administrator manage
essentially all day-to-day site content. See `docs/` for the full spec —
this README is the practical setup guide.

## Project status

This repository is being built in the phased sequence described in
`docs/cms-specification.md` and the master development brief. **Current
state:**

- [x] Live-site audit (`docs/current-site-audit.md`) — all real company
      facts, products, URLs, and media captured from casetechoilfield.com
- [x] Architecture, database schema, CMS spec, deployment, and backup
      design docs (`docs/`)
- [x] CodeIgniter 4 application scaffold
- [x] Full database schema as migrations (`app/Database/Migrations`) —
      auth, pages/page builder, products, services, projects, media, menus,
      forms, enquiries, SEO/redirects, custom content types, revisions,
      popups, backups, jobs
- [x] Role/permission seeder (`RolesAndPermissionsSeeder`) + first Super
      Admin bootstrap
- [x] Session-based admin authentication with login throttling, CSRF,
      optional TOTP 2FA, and DB-backed audit logging
- [ ] Admin CRUD screens for each content module (Pages, Products,
      Services, Projects, Media, Menus, Forms, Enquiries, SEO, Settings)
- [ ] Public frontend (homepage, page builder rendering, product/service/
      project detail pages) and content migration from the live site
- [ ] Google Drive backup integration
- [ ] GitHub Actions deploy workflow
- [ ] Automated test suite

Each unchecked item is a substantial module in its own right and is being
built incrementally against the specs in `docs/`, not invented ad hoc —
read those documents before extending any module so new work stays
consistent with the schema and architecture already committed to.

## Requirements

- PHP 8.2+ with `intl`, `mbstring`, `mysqlnd`, `curl`
- MySQL/MariaDB (InnoDB, utf8mb4)
- Composer

## Local setup

```bash
composer install
cp .env.example .env
php spark key:generate
```

Edit `.env`: set `database.default.*` to a local MySQL database, and set
`app.baseURL`. Then:

```bash
php spark migrate --all
php spark db:seed RolesAndPermissionsSeeder
php spark serve
```

The seeder prints a generated Super Admin email/password to the console
the first time it runs (or set `SEED_SUPERADMIN_EMAIL` /
`SEED_SUPERADMIN_PASSWORD` in `.env` before seeding to choose your own).
Log in at `/admin/login` and change the password immediately.

## Documentation

| Doc | Covers |
|---|---|
| `docs/current-site-audit.md` | Source-of-truth content migrated from the live WordPress site |
| `docs/architecture.md` | App architecture, directory layout, inode-optimization rules for shared hosting |
| `docs/database-schema.md` | Full table-by-table schema reference |
| `docs/cms-specification.md` | Functional spec for every admin module |
| `docs/deployment.md` | GitHub Actions → Hostinger deployment pipeline |
| `docs/backup-architecture.md` | Full-site + DB backup to Google Drive, retention, restore |

## Conventions

- Controllers stay thin; business logic lives in `app/Services`.
- Every migration matches `docs/database-schema.md` — update both together.
- No content, product data, or company facts are invented — everything
  traces back to `docs/current-site-audit.md` or explicit admin input.
- Production deploys never touch `writable/uploads` or `.env` — see
  `docs/deployment.md` for the release strategy.
