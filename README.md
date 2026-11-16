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
- Run the Laravel test suite (**8 tests, 44 assertions** in the verified run).
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


## Framework maintenance

CareDesk currently uses Laravel 10 and PHP 8.1+. Laravel 10 has passed its official security-support period. Before any public deployment, plan a staged upgrade to a supported Laravel release using PHP 8.3+, then re-run the existing clinic feature suite, database migrations, Vue build, and HTTP smoke tests.

The current successful CI run validates the existing Laravel 10 codebase only. It does not remove the separate production limitation documented above: API authentication and role-based authorization must be implemented before any real patient information is used.
