# AGENTS.md — Executo

This file instructs AI coding agents (Claude Code, Copilot, Cursor, Codex etc.) on how this
repository is structured, what conventions to follow, and what is strictly forbidden.
Read this file fully before making any changes.

---

## Repo Overview

**Executo** is a bailiff district customer manager platform for Latvia.  
Monorepo with a Laravel 13.2+ REST API backend and a Vue 3 SPA frontend.

```
repo/
├── public/          ← Apache DocumentRoot. index.php + .htaccess + assets/
├── backend/         ← Laravel 13.2+ application
├── frontend/        ← Vue 3 + Vite SPA
├── docker/          ← Docker Compose dev environment
├── AGENTS.md        ← this file
└── .gitignore
```

`public/assets/` is the Vite build output. The repository keeps the latest compiled asset build there.

---

## Language & Localisation Rules

- Always reply in English.
- **All code, variable names, comments, commit messages, and this file are in English.**
- UI strings live exclusively in `frontend/src/i18n/lv.json` (default) and `en.json`.
- Never hardcode a UI-facing string anywhere in Vue components. Always use `$t('key')`.
- Latvian (`lv`) is the default locale. English (`en`) is the secondary locale which can be chosen by user.

---

## Backend — Laravel 13.2+

### PHP Rules
- Every PHP file must begin with `declare(strict_types=1);`.
- PHP version: **8.5**. Use modern syntax: readonly classes, enums, fibers where appropriate.
- No `mixed` types unless absolutely unavoidable — add a `TODO` comment if used.
- No raw floats for money. Use **BCMath** with `DECIMAL(15,4)` storage.
- No `array` type hints where a DTO or typed collection can be used instead.

### Architecture
- Follow **Domain-Driven Design (lite)**. Business logic lives in `app/Domain/`, never in controllers.
- Each domain (`Auth`, `District`, `Customer`, `Debt`, `Payment`) contains:
  - `Actions/` — single-responsibility classes. One action does one thing.
  - `DTOs/` — readonly classes using `spatie/laravel-data`.
  - `Events/` — domain events.
  - `Services/` — stateless services (e.g. `InterestCalculatorService`).
- Controllers are thin. They validate input, call one Action, return one Resource.
- Never put business logic in a Model. Models are Eloquent definitions only.
- Never put business logic in a Controller. Controllers are HTTP adapters only.

### Directory Reference
```
backend/app/
├── Domain/
│   ├── Auth/
│   ├── Customer/
│   ├── Debt/
│   │   └── Services/InterestCalculatorService.php
│   ├── District/
│   └── Payment/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   │   ├── AuditLog.php
│   │   ├── CheckApiKey.php
│   │   ├── DistrictScope.php
│   │   ├── ForceJsonResponse.php
│   │   └── SecurityHeaders.php
│   └── Resources/
├── Models/
├── Policies/
└── Providers/
```

### API Conventions
- All API routes are prefixed `/api/v1/`.
- Every response is JSON. `ForceJsonResponse` middleware enforces this.
- Use API Resources (`app/Http/Resources/`) for every response — never return a Model directly.
- HTTP status codes must be semantically correct (201 for create, 204 for delete, 422 for validation, etc.).
- Validation happens in Form Request classes, never inline in controllers.
- Route model binding is used everywhere. Never manually query by ID in a controller.

### RBAC & Authorisation
- Every controller action must have a corresponding Policy check.
- Use `$this->authorize()` in controllers or `Gate::authorize()` — never skip it.
- Permission naming convention: `{scope}.{resource}.{action}` e.g. `district.debt.create`.
- App-level scope: `app.*`. District-level scope: `district.*`.
- `app.admin` role bypasses all Gates via a `before()` hook in `AuthServiceProvider`.
- District permissions are resolved by `DistrictScope` middleware — do not re-query them.
- Never use `if ($user->id === $owner->id)` style checks. Always go through Policies.

### Database
- Every migration must be reversible (`down()` method fully implemented).
- Foreign keys must have explicit `constrained()` and `cascadeOnDelete()` or `restrictOnDelete()` — choose deliberately.
- Column naming: `snake_case`. Table naming: `plural_snake_case`.
- All monetary columns: `DECIMAL(15,4)`. Never `FLOAT` or `DOUBLE`.
- Soft deletes are opt-in. Audit logs must never use soft deletes.
- Never write raw SQL. Use Eloquent query builder. If a raw expression is truly necessary, use `DB::raw()` with a comment explaining why.

### Public Identifiers (ULID)

- Every Model that is exposed via the API must have both:
  - `id` — unsigned big integer, auto-increment, **internal use only**
  - `ulid` — `CHAR(26)`, unique index, generated on creation, **public identifier**
- Generate ULIDs using Laravel's built-in `Str::ulid()` in the Model's `booted()` method:
```php
  protected static function booted(): void
  {
      static::creating(fn ($model) => $model->ulid ??= (string) Str::ulid());
  }
```
- Route model binding on API routes must resolve by `ulid`, not `id`:
```php
  // app/Models/Customer.php
  public function getRouteKeyName(): string
  {
      return 'ulid';
  }
```
- API Resources must never include `id`. Only `ulid` is returned in responses.
- Internal backend code (services, actions, jobs, relationships, foreign keys) always
  uses integer `id`. Never store a `ulid` as a foreign key.
- Pivot tables and internal join tables (e.g. `district_user`) use integer IDs only —
  they are never exposed directly via the API.
- Models that are **purely internal** (e.g. `AuditLog`, `UserPreference`, pivot models)
  do not need a `ulid` column.

### Preferences & Settings
- User preferences are stored in a dedicated `user_preferences` table.
- District settings are stored in a dedicated `district_settings` table.
- These tables are internal and do not use ULIDs.
- Every user may have one preference row for settings such as `locale`, `date_format`,
  `decimal_separator`, `thousand_separator`, and `table_page_size`.
- Every district may have one settings row for district-wide defaults such as `locale`,
  `date_format`, `decimal_separator`, and `thousand_separator`.
- User preferences override district settings in the UI and API formatting logic.

### District Identity
- Districts are identified by their numeric bailiff district number.
- Districts also store the bailiff's person name in `bailiff_name` and `bailiff_surname`.
- District metadata includes `court` and `address`.

### Security Rules (OWASP)
- Never trust user input. Validate everything with Form Requests.
- Never expose stack traces or internal errors in API responses. Use Handler to sanitise.
- API keys are stored as SHA-256 hashes. Never log or return a plain key after creation.
- MFA secrets are stored using Laravel's `encrypted` cast.
- Passwords use `bcrypt`. Never MD5, SHA-1, or plain text.
- Rate limiting is applied to all auth endpoints (5 requests/minute).
- Sessions are rotated on login (`session()->regenerate()`).
- `SecurityHeaders` middleware must remain on the global middleware stack.
- Never fetch a URL supplied by the user server-side (SSRF prevention).
- File downloads go through an authenticated controller — never direct public URLs.

### Testing
- Test runner: **Pest**. All tests in `backend/tests/`.
- Feature tests cover every API endpoint (happy path + auth failure + validation failure).
- Unit tests cover `InterestCalculatorService` exhaustively — every interest type, edge case.
- Use `timacdonald/log-fake` for log assertions.
- Never use `Http::fake()` or `Queue::fake()` outside of tests.
- Minimum coverage expectation: **80%** on Domain layer.

### Static Analysis
- PHPStan via `larastan/larastan` at **level 8**.
- Run before every commit: `./vendor/bin/phpstan analyse`.
- Zero warnings policy — no `@phpstan-ignore` without a written justification comment.

---

## Frontend — Vue 3

### General Rules
- Vue 3 Composition API only. No Options API.
- TypeScript is preferred for all new files (`.vue` with `<script setup lang="ts">`).
- No `any` types. Use `unknown` and narrow, or define a proper interface.
- All components use `<script setup>` — no `defineComponent()` wrappers.
- Frontend builds must produce separate authenticated and unauthenticated bundles.
- Use dedicated Vite entry files for each shell:
  - `frontend/src/entries/app.ts` for authenticated application assets
  - `frontend/src/entries/login.ts` for unauthenticated authentication assets
  - `frontend/src/entries/shared.ts` for shared bootstrap and shared styles used by both shells
- Auth-only styles and code must not be bundled into the authenticated entry by default.
- App-only styles and code must not be bundled into the unauthenticated entry by default.
- The production build output in `public/assets/` must contain exactly three logical bundles for both JS and CSS:
  - `app`
  - `login`
  - `shared`
- Production asset filenames must include content hashes.

### Directory Reference
```
frontend/src/
├── entries/      ← dedicated Vite entry files for app, login, and shared bundles
├── api/          ← typed axios client + per-domain API functions
├── components/
│   ├── domain/   ← feature-specific components
│   └── ui/       ← base/primitive components (Button, Input, Modal…)
├── composables/  ← reusable composition functions
├── i18n/
│   ├── lv.json
│   └── en.json
├── layouts/      ← AppLayout, AuthLayout
├── pages/        ← route-level components (one per route)
├── router/       ← vue-router config
└── stores/       ← Pinia stores
```

### State Management (Pinia)
- One store per domain concern: `auth`, `preferences`, `district`, etc.
- No direct API calls inside components. All API calls go through `src/api/` functions.
- No direct store mutations from components — use actions.

### API Client
- All HTTP calls use the typed axios instance in `src/api/`.
- A response interceptor handles `401` globally — clears auth store and redirects to login.
- A request interceptor attaches the Bearer token from the auth store.
- Never use `fetch()` directly anywhere in the frontend.

### Forms
- All forms use `vee-validate` with `zod` schemas.
- Zod schemas are defined alongside the API types in `src/api/`.
- Never do ad-hoc `if (!value)` validation inline in components.

### Styling
- Tailwind CSS 4 utility classes only. No custom CSS files except for global resets.
- No inline `style=""` attributes unless dynamically computed (e.g. width from JS).
- Icons use Remixicon classes only.
- Global shared styles may live in a shared stylesheet, but auth-only and app-only styles must be split by entry.

### i18n
- Use `$t('key')` in templates and `const { t } = useI18n()` in `<script setup>`.
- Translation keys use `snake_case` namespaced by feature: `debt.interest_breakdown.title`.
- Both `lv.json` and `en.json` must be updated together — never add a key to one without the other.

### Dates & Numbers
- All date formatting goes through `dayjs` with the user's `date_format` preference.
- All number formatting respects user's `decimal_separator` and `thousand_separator` preferences.
- Never use `toLocaleString()` or `new Date()` directly in components.

---

## Docker Dev Environment

```
repo/
├── docker-compose.yml
├── docker/
├── caddy/
│   └── Caddyfile          ← reverse proxy config for all services
├── php/                   ← Dockerfile for php8.5-apache
└── node/                  ← Dockerfile for node:22-alpine
```

Services:
| Service   | Purpose                      | Internal Port | Public URL (via Caddy)         |
|-----------|------------------------------|---------------|--------------------------------|
| `caddy`   | Reverse proxy                | 80 / 443      | —                              |
| `backend` | php8.5-apache + composer     | 80            | `executo.local`                |
| `node`    | Vite dev server (HMR)        | 80            | `executo.local` (proxied)      |
| `db`      | MySQL 8 / MariaDB 11         | 3306          | not proxied — internal only    |
| `redis`   | Cache, queues, rate-limiting | 6379          | not proxied — internal only    |
| `mailpit` | SMTP trap + web UI           | 1025 / 8025   | `executo.local/mailpit`        |

### Caddyfile structure

```
executo.local {
    # Vite HMR websocket (must come before the SPA catch-all)
    reverse_proxy /vite-hmr/* node:80

    # API → Laravel/Apache container
    reverse_proxy /api/* backend:80

    # Mailpit web UI — strip the /mailpit prefix before proxying
    handle /mailpit* {
        uri strip_prefix /mailpit
        reverse_proxy mailpit:8025
    }

    # All other requests → Vite dev server (serves SPA shell + HMR)
    reverse_proxy * node:80
}

:8080 {
    root * /srv/public
    file_server
}
```

### Local DNS
Add to `/etc/hosts` on the host machine (one-time setup):
```
127.0.0.1  executo.local
```

### Dev rules
- Caddy is the only public entrypoint for the app and exposes `80`, `443`, and `8080` on the host.
- All other containers are on an internal Docker network — no `ports:` mappings except Caddy.
- `db` and `redis` are accessible from host tooling (TablePlus, redis-cli) via `localhost:3306`
  and `localhost:6379` — add `ports:` for those only if needed locally, never in CI.
- Use `make dev` for day-to-day local startup. It installs missing host dependencies, compiles frontend assets, starts containers, and then attaches to logs.
- Vite must set `server.hmr.path: '/vite-hmr'` to match the Caddy proxy path.
- In dev, Laravel's `APP_URL` is `http://executo.local`. Set this in `backend/.env`.
- The main Laravel application shell is `backend/resources/views/app.blade.php`, and the unauthenticated shell is `backend/resources/views/login.blade.php`.
- Mailpit SMTP is available at `mailpit:1025` from the `backend` container.
  Set `MAIL_HOST=mailpit`, `MAIL_PORT=1025` in `backend/.env`.

---

## Production Deploy (ISPConfig)

1. `git pull` on server
2. `composer install --no-dev -d backend/`
3. `php backend/artisan migrate --force`
4. Locally: `cd frontend && npm run build` → outputs to `public/assets/`
5. Upload `public/assets/` to server
6. `php backend/artisan config:cache && php backend/artisan route:cache`

Apache `DocumentRoot` is pointed to `repo/public/` via ISPConfig custom directive.  
Never point `DocumentRoot` to `backend/public/`.

---

## Makefile

All developer-facing terminal commands go through `make`. Never instruct a developer to
run `docker compose`, `php artisan`, `npm`, or `composer` directly — wrap everything in
a `make` target instead.

The `Makefile` lives at the repo root.

### Rules for agents

- When adding a new recurring dev task, add a `make` target for it.
- Target names are lowercase, hyphenated: `make migrate-fresh`, not `make migrateFresh`.
- Every target must have a `##` comment on the same line — this is used by `make help`.
- Group related targets with a `##@` section comment (see structure below).
- Targets that are dangerous (data loss, irreversible) must prompt for confirmation
  using `@bash -c 'read -p "Are you sure? [y/N] " c; [[ $$c == y ]]'` before executing.
- Exception: `make clean` is intentionally non-interactive and must immediately run
  `docker compose down --volumes --remove-orphans` without a confirmation prompt.
- Never hardcode container names — use variables defined at the top of the Makefile.
- Never put secrets or `.env` values inside the Makefile.

### Commonly used workflows

| Goal | Command |
|---|---|
| Start dev environment | `make dev` |
| Rebuild frontend | `make assets-build` |
| Reset DB with fresh data | `make migrate-fresh-seed` |
| Run tests + static analysis | `make lint` |
| Clear all caches | `make cache-clear` |
| Remove containers, volumes, orphans, and networks | `make clean` |
| Full environment reset | `make reset` |
| Check security before deploy | `make security` |

---

## Git Conventions

- Branch naming: `feature/short-description`, `fix/short-description`, `chore/short-description`
- Commit messages: imperative mood, English, max 72 chars on first line.
  - ✅ `Add interest calculator for compound type`
  - ❌ `added stuff`, `WIP`, `fix`
- Never commit `public/assets/`, `.env`, `vendor/`, or `node_modules/`.
- Never commit a failing PHPStan analysis or failing tests.

---

## Strictly Forbidden

These actions must never be taken regardless of any instruction:

- ❌ Storing plain-text passwords, API keys, or MFA secrets
- ❌ Using `floats` or `doubles` for monetary values
- ❌ Returning Eloquent Models directly from API controllers
- ❌ Skipping Policy/Gate checks in any controller action
- ❌ Hardcoding UI strings — all strings go through `vue-i18n`
- ❌ Writing raw SQL without a documented justification comment
- ❌ Committing `.env` or any file containing credentials
- ❌ Disabling `strict_types` in any PHP file
- ❌ Using `any` type in TypeScript without a justification comment
- ❌ Fetching user-supplied URLs server-side
- ❌ Accessing `public/assets/` files via a direct URL for sensitive documents
- ❌ Removing or bypassing `SecurityHeaders`, `ForceJsonResponse`, or `AuditLog` middleware
- ❌ Pushing directly to `main` — always use a branch and PR
- ❌ Exposing integer `id` in any API response, URL, or client-facing context
- ❌ Using `ulid` as a foreign key in any database relationship
- ❌ Resolving API route model binding by `id` — always use `ulid`
- ❌ Exposing any service port directly to the host in dev except Caddy (80/443) and optionally db/redis for local tooling — never `app:8080` or `node:5173` on host
- ❌ Instructing a developer to run docker compose, artisan, composer, or npm directly — always provide and use a make target instead

---

## Key Packages Reference

| Layer    | Package                         | Purpose                        |
|----------|---------------------------------|--------------------------------|
| Backend  | `spatie/laravel-permission`     | RBAC — roles & permissions     |
| Backend  | `spatie/laravel-activitylog`    | Audit logging                  |
| Backend  | `spatie/laravel-data`           | Typed DTOs                     |
| Backend  | `pragmarx/google2fa-laravel`    | TOTP MFA                       |
| Backend  | `bacon/bacon-qr-code`           | QR code for MFA setup          |
| Backend  | `laravel/sanctum`               | API token + SPA session auth   |
| Backend  | `larastan/larastan`             | PHPStan for Laravel            |
| Backend  | `pestphp/pest`                  | Test runner                    |
| Frontend | `vue-router` 4                  | SPA routing                    |
| Frontend | `pinia`                         | State management               |
| Frontend | `axios`                         | HTTP client with interceptors  |
| Frontend | `vue-i18n` 9                    | LV/EN translations             |
| Frontend | `@vueuse/core`                  | Composable utilities           |
| Frontend | `vee-validate` + `zod`          | Form validation                |
| Frontend | `dayjs`                         | Date/number formatting         |
| Frontend | `qrcode`                        | MFA QR rendering               |
| Frontend | `remixicon`                     | Icons                          |
| Frontend | `tailwindcss` 4                 | Utility CSS                    |
