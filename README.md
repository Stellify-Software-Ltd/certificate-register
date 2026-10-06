# Certificate Register

A free register of certificates and compliance records for a small business —
the gas safety certificate, the EICR, the insurance schedule, the forklift
licence — with a dashboard that shows what has expired and what is about to.
It replaces the spreadsheet (or the filing cabinet) where these things go to
be forgotten. A record's status is **never stored**: it is derived on every
read from the expiry date and today's date, so it can never be stale.

Built with [Stellify](https://stellisoft.com) — and shipped as a completely
standard Laravel application: no Stellify dependency, MIT licensed, yours to
change.

## What it does

- **Dashboard** — everything expired, then everything expiring within 30, 60
  and 90 days, each row showing what it is, what it covers, who is responsible
  and how many days are left (or how far over it is)
- **Register** — every record, searchable across type, covered entity, issuer
  and reference number; filterable by type, entity, responsible person and
  derived status; sortable by expiry
- **Records** — a record ties a type (Gas Safety, EICR, PAT, insurance…) to
  the thing it covers ("Unit 14 boiler", "Reception block", "Dave Mitchell",
  "whole site"), with issue date, expiry date, issuer, reference number, notes
  and a responsible person. Types and entities are created on the fly as you
  type — no admin screen required first. Records without an expiry date
  (a premises licence, say) are supported properly
- **Documents** — attach the actual certificate, report or schedule to a
  record: real file upload, download, delete. Files live in
  `storage/app/private/documents`
- **Renewal chains** — "Renew" pre-fills a new record from the old one and
  files the old one as its predecessor. Renewed records disappear from the
  dashboard and the default register, but stay one click away from their
  replacement and in the entity's history. Chains can be any length
- **Per-type warning thresholds** — each type has its own "expiring soon"
  window (insurance might warn at 30 days, an EICR at 60). Edit them on the
  Types screen
- **One shared sign-in** — an operator key in `.env` opens every screen,
  exactly like a shared till key. Failed attempts are rate-limited and logged

## What it deliberately does not do

No reminder emails or escalation, no contractor chasing, no spreadsheet
import, no multi-site rollups, no reporting or auditor exports, no AI, no
user roles. The paid [Stellify](https://stellisoft.com) build adds those.

It also **never tells you what the law requires**. Seeded types carry a
*suggested renewal interval* — used only to pre-fill the expiry date when you
renew, and editable on the Types screen — but whether something must be
renewed, and how often, is between you, your contractors and your insurer.
This app records; it does not advise.

## Requirements

- PHP 8.3+ with the `sqlite3` extension (any recent PHP install has it)
- [Composer](https://getcomposer.org)
- No database server needed — SQLite is the default. PostgreSQL is recommended
  for production: set the `DB_*` variables in `.env` and migrate

## Setup

```bash
git clone https://github.com/Stellify-Software-Ltd/certificate-register.git
cd certificate-register
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Then set your **operator key** in `.env` (any hard-to-guess string):

```
OPERATOR_KEY=pick-something-long-and-random
```

Open `http://localhost:8000` and sign in with it. The seeder loads a demo
register for a small mixed site — expired, expiring, valid, a no-expiry
licence, a renewal chain and a few placeholder PDFs — all dated relative to
today, so it never goes stale. Before going live, start clean with
`php artisan migrate:fresh`, then bring just the suggested types back with
`php artisan db:seed --class=RecordTypeSeeder`.

## Backing up

Everything lives in two places: the database at `database/database.sqlite`
and the uploaded documents in `storage/app/private/documents`. Copy both
somewhere safe on a schedule you trust and you can rebuild the whole register
on a fresh install.

## A note on timezones

Expiry is a calendar date, not a moment in time. "Today" is computed in the
timezone set by `APP_TIMEZONE` in `.env` (default `Europe/London`), so a
certificate that expires today does not flip to expired at 11pm just because
the server clock runs on UTC. This is tested across the BST boundary. If your
business is elsewhere, change `APP_TIMEZONE`.

## Tests

```bash
php artisan test
```

The suite (56 tests) covers the derived-status boundaries (expiring today is
never "expired"; the threshold day itself counts as expiring soon), timezone
correctness during BST, renewal chaining (superseded records excluded from
every dashboard bucket and the default register, still reachable from the
chain and entity history), per-type thresholds including live re-derivation
when a threshold changes, document upload/download/delete, the operator gate
on every screen with rate-limited sign-in, and register search, filters and
sorting.

## Licence

MIT — see [LICENSE](LICENSE).
