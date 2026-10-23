# Message Space — Laravel Admin & User Portal

An English-language Laravel 12 portal for WhatsApp template submissions, broadcast approvals, delivery reports, and user balances. The responsive interface uses a white background, charcoal text, and soft green accents, with keyboard-accessible navigation.

## Accounts and access

- Sign in with a unique username; email remains an account contact field.
- Public registration is disabled. Administrators create accounts through **Users → Add user**.
- New accounts have the `user` role. Administrators can change other accounts' roles, but cannot change their own role.
- User dashboards and resources are scoped to their owners. Administrators review templates and broadcasts across users.
- Passwords are hashed. Authentication includes CSRF protection, session regeneration, remember-me support, and login rate limiting.
- Application and fallback locales are English. New templates default to English; existing template language metadata is preserved.

## Wallets and rates

Each account starts with **IDR 0** and a default rate of **IDR 500 per number**. Administrators open **Users → Manage balance** to add funds, set a custom rate, and inspect transactions. A rate of IDR 0 means free. Users see their own balance, rate, and history under **Balance & Transactions**.

Broadcast cost equals valid recipient count multiplied by the user's rate. The rate is locked on submission or resubmission. Later rate changes do not change existing submissions. The CSV form displays an estimate.

Pending and rejected submissions are not charged. Approval deducts the cost once, in the same database transaction as the status update and recipient creation. Insufficient funds block approval. Finished does not charge again. Charges are based on approved recipients, rather than individual sent/failed outcomes. Broadcasts approved or finished before the wallet feature are not billed retroactively.

Credits use unique references to prevent duplicate credits from repeated form submissions. Balance changes lock the account row, and each broadcast has at most one charge transaction. Money is stored as integer IDR. Maximum balance: IDR 1,000,000,000,000. Maximum rate: IDR 1,000,000,000 per number.

## Templates and the visual editor

Create a template with a name, language, optional text/image header, and message body. Names start with a lowercase letter and contain lowercase letters, numbers, or underscores. Header images support JPG, PNG, and WebP under 1 MB. New images are stored on the public disk (`storage/app/public`) and accessible without login through `/storage/` after `php artisan storage:link`. Upload only images intended to be public. Ownership checks still protect template management and preview routes; previews can also read older private images during migration.

To enable public images on the server, deploy this version and run from the Laravel project directory:

```sh
php artisan whatsapp:publish-template-images
php artisan storage:link
php artisan optimize:clear
```

The first command copies database-referenced images from `storage/app/private` to `storage/app/public` using the same paths, preserves private originals, and reports missing files. It is safe to rerun. Set the server's `APP_URL=https://wa-ln.myads-tracers.com` and the Python worker's `LARAVEL_PUBLIC_STORAGE_URL=https://wa-ln.myads-tracers.com/storage`. A database path `whatsapp-templates/2/photo.png` must open at `https://wa-ln.myads-tracers.com/storage/whatsapp-templates/2/photo.png` without logging in. If the link already exists, verify that `public/storage` points to `storage/app/public`. For HTTP 403, check the site's document root points to Laravel's `public` directory, its web server can follow this storage link, and the web server account can traverse/read the image directories/files. Do not expose `storage/app/private`, the project root, or `.env`.

Text headers are saved as `*Header text*` for WhatsApp bold formatting. The editor accepts plain text, the preview displays it in bold, and resaving an already formatted header does not add duplicate markers. Header variables are personalized inside the markers, for example `*Hello {{name}}*` becomes `*Hello Alex*`. The 60-character header limit applies to the content, excluding the two formatting markers.

The visual editor supports bold, italic, strikethrough, monospace, lists, emoji, and CSV variables. Bodies are stored as plain text with WhatsApp formatting markers, rather than HTML. The 550-character limit includes markers and counts Unicode characters. Template, administrator, and broadcast previews share a formatter with allowlisted tags and escaped text. See the [WhatsApp formatting guide](https://faq.whatsapp.com/539178204879377/).

Submit templates for administrator approval. Rejection requires a reason. Every edit creates a new revision, clears approval, and resubmits the template. Broadcast approval remains blocked until the current template revision is approved. Reviews reject stale statuses and revisions. Legacy sender-name, footer, and button fields are not used by the current form.

## Broadcasts, CSV, and scheduling

Choose an approved template and upload comma-separated UTF-8 CSV. The first column must be `msisdn`; subsequent column names are case-sensitive template variables:

```csv
msisdn,name,product
6281234567890,Alex,Shoes
6281234567891,Sam,Bag
```

For example, `Hello {{name}}, your {{product}} order is ready.` previews as `Hello Alex, your Shoes order is ready.` Variables work in the body and text header. Every variable needs a column and a nonempty value in every record. Numbers currently must start with `62`, contain digits only, and have 8–15 digits. Duplicate CSV numbers are rejected. The application does not impose a recipient-count limit.

Previews use the first record. Uploading a replacement CSV replaces all recipients; leaving it empty when editing preserves saved records. Select the file again after form validation fails. The downloadable example is `public/downloads/sample-broadcast.csv`.

Schedules use **UTC+7**. Both date/time fields may be empty; past schedules are rejected. Scheduling and report date boundaries use `Asia/Jakarta`, while stored timestamps use UTC. Money is displayed in IDR with English separators.

Broadcasts move through Pending → Approved → Finished, or are Rejected with a reason. Users can edit Pending and Rejected broadcasts. Resubmission clears the rejection reason, returns to Pending, and quotes the latest rate. Approved and Finished broadcasts are locked. Approval stores recipient numbers and variables in batches.

The Laravel application manages submissions, approvals, and sender settings. The companion Python worker in `../wa_blast_package` sends approved campaigns through the configured gateway. Administrator approval is distinct from provider approval. Finished is an administrative processing marker; individual delivery reports use stored recipient delivery data.

Administrators open **WhatsApp Senders** to add multiple gateway URL, API key, and sender pairs, mark them **Active** or **Non-Active**, and configure per-sender minimum/maximum delay in seconds. A random delay within the inclusive range starts after each completed attempt. Equal bounds give a fixed delay. The worker chooses randomly among Active senders whose cooldown and daily quota allow a new send; Non-Active senders have no environment fallback. An attempt already reserved may finish after deactivation.

Sender API keys are encrypted with Laravel's `APP_KEY`, hidden from serialization, and never redisplayed. The worker must use the same MySQL/MariaDB database, with `LARAVEL_APP_KEY` matching Laravel's `APP_KEY` and `LARAVEL_CIPHER` matching its cipher. Install the updated Python requirements before using the worker. Sender ranges, quotas, and failure controls are managed only by administrators; existing cooldowns and counters are preserved when settings change. Quotas count attempts across campaigns and reset by Asia/Jakarta calendar day. Failure cooldowns apply after failed/unknown attempts; repeated failures automatically make a sender Non-Active for review. These controls do not guarantee protection against account restrictions.

## Reports

Users see their own reports. Administrators can filter across users. Reports include delivery summaries and date/status/number filters, with English CSV and Excel exports. Downloads use the active filters. CSV exports protect against spreadsheet formula injection; Excel preserves phone numbers and values as text. Failure details appear only for failed recipients.

## Setup

Requirements: PHP 8.2+, Composer, and PDO SQLite. Excel exports require PHP ZIP and XMLWriter. CSS and JavaScript are served directly, so an npm build is not required. Plus Jakarta Sans and Space Grotesk load from Bunny Fonts, with fallback fonts. Font Awesome assets and licensing are in `public/vendor/fontawesome`.

```sh
composer install
```

Copy `.env.example` to `.env` and create an empty `database/database.sqlite` file if needed. Then run:

```sh
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000. Existing installations should run `php artisan migrate`. Migrations preserve credentials and roles, generate unique usernames for older accounts, and initialize balances/rates without charging historical broadcasts. Use `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`, and `APP_FAKER_LOCALE=en_US`.

### Local demo accounts

| Role | Username | Password |
| --- | --- | --- |
| Admin | admin | Admin123! |
| User | user | User12345! |

The seeder creates demo accounts only in `local` and `testing`, without overwriting existing accounts. Demo credentials are for development, not production.

## Routes

| Route | Access |
| --- | --- |
| `/login` | Guests |
| `/dashboard` | Signed-in accounts, redirected by role |
| `/admin/dashboard`, `/admin/users` | Administrators |
| `GET /admin/users/create`, `POST /admin/users` | Administrators, create users |
| `GET /admin/users/{user}/wallet` | Administrators, user balance/history |
| `POST /admin/users/{user}/wallet/credit` | Administrators, add funds |
| `PATCH /admin/users/{user}/wallet/rate` | Administrators, set rate |
| `PATCH /admin/users/{user}/role` | Administrators, other accounts only |
| `/user/dashboard`, `/user/wallet` | Users, own account |
| `/user/whatsapp/templates`, `/user/whatsapp/broadcasts` | Users, own resources |
| `/admin/whatsapp/templates`, `/admin/whatsapp/broadcasts` | Administrators, reviews |
| `/user/whatsapp/reports`, `/admin/whatsapp/reports` | Respective role, scoped reports |
| `POST /logout` | Signed-in accounts |

Template and broadcast resources provide index, create, store, show, edit, and update routes. Reviews use PATCH. There are no public registration, sending, or deletion endpoints.

## Implementation and tests

Models live in `app/Models`, validation in `app/Http/Requests/WhatsApp`, ownership policies in `app/Policies`, and transactional submission/review/charging actions in `app/Actions/WhatsApp`. CSV and export services are in `app/Services/WhatsApp`. Role middleware is registered in `bootstrap/app.php` and applied in `routes/web.php`.

Blade screens are in `resources/views`. The shared visual system is in `public/css/white-theme.css`, with component styles in `app.css`, `whatsapp.css`, and `reports.css`. The editor and previews use `public/js/whatsapp-*.js`. The logo/favicon is `public/assets/message-space.svg`.

```sh
php artisan test
php vendor/bin/pint --test
```

Tests use SQLite `:memory:` and do not modify the application database. They cover authentication, account creation, authorization, revisions, CSV validation, personalization, reports, wallet credits, idempotency, insufficient funds, quoted rates, approval rollback, and historical broadcasts.

Open `tests/browser/whatsapp-editor.html` in a browser for dependency-free editor checks covering formatting, emoji, caret restoration, paste, safe previews, and character validation.
