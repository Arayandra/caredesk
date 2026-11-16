# CareDesk — Clinic Operations Workspace

![CareDesk CI](https://github.com/Arayandra/caredesk/actions/workflows/ci.yml/badge.svg)

CareDesk is a clinic operations demo built with **Laravel 10**, **Vue 3**, **Vite**, and **MySQL**. It provides a single-page workspace for managing patient profiles, appointments, medical staff, consultation notes, and operational reports.

## Dashboard Preview

A verified browser screenshot has not been committed yet. The README intentionally avoids using an illustrative mockup as a substitute for a real application capture.

## Features

- **Overview dashboard** — patient count, today's appointments, waiting queue, completed visits, seven-day appointment activity, and recent consultation notes.
- **Patient directory** — search, register, edit, and export patient directory data as CSV; stores basic profile fields including date of birth, blood type, and allergies.
- **Appointments** — schedule visits, filter by date/status, update visit status, and remove appointments.
- **Medical team** — doctor directory, specialties, active status, and appointment/record counts.
- **Consultations** — document complaints, clinical notes/diagnosis, treatment, recommendations, basic measurements, and follow-up.
- **Reports** — monthly appointment totals, completion rate, daily trend, and popular services.
- **REST API** — Laravel endpoints consumed by the Vue frontend through Axios.

## Technology Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.1+, Laravel 10 |
| Frontend | Vue 3, JavaScript, Vite 5 |
| Styling | CSS, Tailwind/PostCSS toolchain |
| Database | MySQL / MariaDB |
| HTTP client | Axios |
| API package | Laravel Sanctum (bearer-token authentication for the clinic API) |

## Dependency lockfiles

`composer.lock` is committed. The repository does not currently include `package-lock.json`; run `npm install` in the project root and commit the generated lockfile before relying on deterministic frontend dependency installation. After that, switch the CI frontend install step to `npm ci`.

## Requirements

- PHP 8.1 or later, with Laravel-required extensions enabled (including `ctype`, `fileinfo`, `mbstring`, `openssl`, `PDO`, `tokenizer`, and `xml`).
- Composer 2.
- MySQL 8+ or a compatible MariaDB version.
- Node.js 18+ and npm.
- Git (optional, for cloning the repository).

For a Windows setup, XAMPP or Laragon can provide PHP and MySQL. Ensure the PHP CLI selected in CMD has the required extensions enabled; the PHP CLI version may differ from the one used by Apache.

## Installation on Windows (CMD)

1. Clone the repository and enter its folder:

   ```cmd
   git clone https://github.com/Arayandra/caredesk.git
   cd caredesk
   ```

   Or extract the project ZIP and open CMD in the project root (the folder containing `artisan`).

2. Create a MySQL database named `caredesk_db` using phpMyAdmin or the MySQL client.

3. Create the environment file:

   ```cmd
   copy .env.example .env
   ```

4. Install PHP dependencies:

   ```cmd
   composer install
   ```

5. Generate the Laravel application key:

   ```cmd
   php artisan key:generate
   ```

6. Check the database settings in `.env` (`DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`), then run migrations and fictional demo seeders:

   ```cmd
   php artisan migrate --seed
   ```

7. Install frontend dependencies and build the assets:

   ```cmd
   npm install
   npm run build
   ```

8. Start Laravel:

   ```cmd
   php artisan serve --port=8001
   ```

9. Open `http://127.0.0.1:8001` in your browser.

For frontend hot reload during development, use a second CMD window and run `npm run dev` instead of relying only on the production build. Keep that process running while using the app.

## API Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/auth/login` | Sign in and issue a Sanctum bearer token (5 attempts/minute) |
| POST | `/api/auth/logout` | Revoke the current bearer token |
| GET | `/api/dashboard` | Dashboard statistics and recent activity (authenticated) |
| GET, POST | `/api/pasiens` | List/search and create patients |
| GET, PUT, DELETE | `/api/pasiens/{id}` | Read, edit, and delete a patient |
| GET | `/api/doctors` | List doctors |
| GET, POST | `/api/appointments` | List and create appointments |
| PUT, DELETE | `/api/appointments/{id}` | Update and delete appointments |
| GET, POST | `/api/records` | List and create consultation records |
| GET | `/api/reports` | Operational report data |

## Demo Data

Run `php artisan migrate --seed` to load fictional doctors, patient profiles, appointments, and consultation notes. Seeder records are demonstration data only and are not real patient records.

To create a staff login through the demo seeder, add these values to your local `.env` before running the seeder (use a unique strong password of at least 12 characters):

```dotenv
CAREDESK_DEMO_NAME="Clinic Demo Staff"
CAREDESK_DEMO_EMAIL=staff@example.test
CAREDESK_DEMO_PASSWORD="replace-with-a-unique-strong-password"
```

The seeder creates that staff account only when a valid email and password are supplied. No default public password is embedded in the repository. In CI, a disposable test-only staff account is created so the authenticated dashboard smoke test can run.

## Security and Production Limitations

**This repository is a development/demo project and is not ready for production use with real patient information.** Clinic API endpoints now require a Sanctum bearer token. Sign in with `POST /api/auth/login` using an existing staff account, then send `Authorization: Bearer <token>` with API requests. `POST /api/auth/logout` revokes the current token. The login endpoint is rate-limited. Role-based authorization, audit logging, secure backup/retention, HTTPS deployment hardening, and formal privacy controls are not yet implemented. Do not expose this demo instance to the public internet or enter real patient data.

## Tests and Verification

The repository includes feature tests for Sanctum-protected API authentication, token issuance/revocation, patient registration, appointment scheduling conflicts (including updates), consultation-to-appointment validation, patient-history deletion protection, and dashboard/report responses.

The GitHub Actions workflow runs these checks on pushes to `main` and pull requests:

- Install Composer and npm dependencies.
- Build the Vue frontend with Vite.
- Run MySQL migrations and seed fictional demo data in a disposable CI database.
- Run the Laravel test suite (**11 tests, 54 assertions** in the latest verified run on 16 November 2026).
- Start the Laravel development server and make HTTP requests to the home page and dashboard API.
- Verify the registered Laravel routes.

See [the latest workflow runs](https://github.com/Arayandra/caredesk/actions).

To run checks locally after installing dependencies and configuring a disposable test database:

```cmd
php artisan test
php artisan route:list
npm run build
```

> `php artisan migrate:fresh --seed` drops and recreates all tables in the configured database. Use it only with a disposable development database.

---

Built as a clinic workflow demonstration with Laravel and Vue.


## Framework upgrade plan

**Current baseline:** Laravel 10 (`^10.10`) and PHP 8.1+. Laravel 10 security support ended on **4 February 2025**. The green CI workflow validates this existing Laravel 10 baseline; it does not make the framework release supported.

**Target:** Laravel 13 with PHP 8.3 or later. Laravel 13 was released on 17 March 2026 and requires PHP 8.3+. This upgrade has **not** been applied to the Composer manifest or lockfile.

Recommended sequence:

1. Create a dedicated `upgrade/laravel-13` branch and preserve the current green CI baseline.
2. Move the development and CI runtime to PHP 8.3+ (PHP 8.4 is a reasonable initial target after checking the deployment host and extensions).
3. Check every direct dependency against Laravel 13 before changing constraints. In particular, verify Sanctum, PHPUnit, Collision, Sail, and Laravel Ignition compatibility. Use `composer why-not laravel/framework ^13.0` to identify blockers; do not force a blanket major-version update.
4. Resolve framework and package breaking changes, then run migrations against a disposable database and verify login/token issuance, token revocation, patient and appointment flows, consultations, reports, and route registration.
5. Require `composer validate`, `composer audit`, `php artisan test`, `php artisan route:list`, `npm run build`, HTTP smoke tests, and a green GitHub Actions run before merging. Regenerate and commit `composer.lock` only after the dependency set is validated.

See the [official Laravel release and support policy](https://laravel.com/framework/docs/releases) and the [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade).

The current CI success is evidence for the Laravel 10 codebase only. It does not remove the production limitations documented above: role-based authorization, audit logging, secure backup/retention, HTTPS hardening, and formal privacy controls are still required before real patient information is used.

## Latest CI verification

Verified on **16 November 2026** on branch `main`: **11 PHPUnit tests passed (54 assertions)**. The workflow also installs Composer and npm dependencies, builds the Vue frontend, runs fresh migrations and demo seeders, verifies seeded staff credentials, authenticates against the dashboard API, and checks Laravel route registration.

The authenticated HTTP smoke test passed. This validates the demo workflow in CI, not production readiness or authorization policy completeness.
