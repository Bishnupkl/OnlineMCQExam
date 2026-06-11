# OEE Laravel + Vue Migration

This directory is the upgraded Laravel + Vue version of the legacy flat PHP OEE project.
The old PHP files remain untouched in the parent directory.

## Stack

- Laravel 13 backend
- Vue 3 frontend through Vite
- MySQL database configured as `omcqe`
- Session-based auth for student, teacher, and admin roles

## Local Setup

```bash
cd /var/www/html/oee/oee-laravel-vue
cp .env.example .env
php artisan key:generate
```

Create the database if it does not already exist:

```sql
CREATE DATABASE omcqe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Default seeded users:

- Admin: `admin@example.com` / `password`
- Teacher: `teacher@example.com` / `password`

## Migrating Legacy Data

The default local database credentials are `20nu` / `20nu`.

The old schema used plaintext passwords and mixed application logic directly into PHP pages.
The new app hashes passwords on save and still allows legacy plaintext passwords to authenticate once, then rehashes them.

Recommended data migration path:

1. Import `../oee.sql` into a temporary database, for example `oee_legacy`.
2. Run Laravel migrations into `omcqe`.
3. Copy rows from legacy tables into the matching new tables: `stu_reg`, `teacher_reg`, `question_table`, `notice`, `exam_date`, `rdate`, `result`, `admin_account`.
4. Ask users to sign in once, or force password resets, so plaintext credentials are replaced with hashes.

Do not point this upgraded app at the production legacy database until the import has been tested on a copy.
