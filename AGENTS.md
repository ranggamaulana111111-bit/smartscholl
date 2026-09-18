<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, load the antislop skill for the task:
- Core filter, always on: `antislop`
Before starting, ask the user when antislop applies: during the work, or after it is done.
<!-- antislop:end -->

## Project

Smart School Enterprise SaaS: multi-tenant school operations system with real-time attendance (QR/RFID), academic grading, early warning system, and parent portal.
Full requirements in `PRD.md`. Entities: Tenants, Users/Roles (RBAC), Students, Teachers, Attendances, Assessments/Grades, JournalKBM, EarlyWarningLogs.

Stack: Laravel 12 + Tailwind CSS 4 + Vite 6. PHP 8.2+. SQLite database.
Blade templates only (no Vue/React).

## Environment

Laragon on Windows. No Docker, no Sail.
Dev server: `composer dev` (runs artisan serve, queue:listen, pail, vite concurrently).
Individual: `php artisan serve`, `npm run dev` (Vite only).

## Commands

```bash
composer dev                          # all-in-one dev server
php artisan test                      # all tests
php artisan test --filter=TestName    # single test
./vendor/bin/pint                     # auto-fix PHP style
./vendor/bin/pint --test              # check only (dry-run)
php artisan migrate
```

## Conventions

- 4-space indent, LF line endings, UTF-8 (`.editorconfig`).
- Tests in `tests/Unit/` and `tests/Feature/`. PHPUnit 11 with array-backed cache/mail/session/queue.
- SQLite (`database/database.sqlite`). Toggle to in-memory in `phpunit.xml`.
- Vite entrypoints: `resources/css/app.css`, `resources/js/app.js`.
- Tailwind v4 uses `@tailwindcss/vite` plugin (not PostCSS).

## Coding Rules (from PRD, non-negotiable)

- **No placeholders**: every function complete, no `// TODO` or empty stubs. Handle edge cases.
- **KISS & DRY**: no over-abstraction for single-use code.
- **Strict types**: typed params, return types, model properties. No untyped arrays.
- **Validate at the gate**: use FormRequest for all input validation.
- **Explicit error handling**: no silent catches. Log debug info, return standard API responses.
- **Multi-tenant isolation**: every DB query must scope to `tenant_id` automatically, except SuperAdmin context. Use foreign keys, indexes, DB transactions for multi-table writes.
- **Plan before coding**: for any feature request, explain data flow + DB changes + API contract in 3-5 bullets before writing code.
- **Security audit**: before finishing, verify no cross-tenant data leakage or RBAC gaps.
