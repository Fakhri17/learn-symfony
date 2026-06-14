# Setup Project Symfony

Dokumen ini berisi langkah setup project Symfony secara umum, bukan hanya fitur Yahoo Finance.

---

## 1. Requirement

- PHP `>= 8.4`
- Composer
- Symfony CLI atau web server lokal seperti Laragon
- Database, pilih salah satu:
  - SQLite
  - PostgreSQL
  - MySQL / MariaDB
- Git
- Node.js/npm jika ingin build asset frontend

---

## 2. Clone Project

```bash
git clone <repository-url>
cd learn-symfony
```

Jika project sudah ada di local, cukup masuk ke folder project:

```bash
cd c:\laragon\www\learn-symfony
```

---

## 3. Install Dependency PHP

```bash
composer install
```

Package penting yang digunakan project:

- Symfony `8.x`
- Doctrine ORM
- Doctrine Migrations
- Twig
- Symfony Security
- Symfony Asset Mapper / Importmap
- `scheb/yahoo-finance-api`

---

## 4. Setup Environment

Copy file environment lokal jika diperlukan:

```bash
copy .env .env.local
```

Atau di Linux/macOS:

```bash
cp .env .env.local
```

Pastikan minimal konfigurasi berikut tersedia:

```dotenv
APP_ENV=dev
APP_SECRET=your-secret
```

Generate secret manual bisa memakai string acak apa saja untuk local development.

---

## 5. Setup Database

Konfigurasi database ada di variable `DATABASE_URL` pada `.env` atau `.env.local`.

Gunakan `.env.local` untuk konfigurasi lokal agar tidak mengubah default project.

---

## 5.1 Opsi SQLite

SQLite cocok untuk setup paling cepat di local.

Contoh konfigurasi:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

Langkah setup:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

Catatan:

- File database akan dibuat di folder `var/`
- Contoh file dev: `var/data_dev.db`

---

## 5.2 Opsi PostgreSQL

Contoh konfigurasi:

```dotenv
DATABASE_URL="postgresql://app:password@127.0.0.1:5432/learn_symfony?serverVersion=16&charset=utf8"
```

Sesuaikan:

- username: `app`
- password: `password`
- database: `learn_symfony`
- port: `5432`

Langkah setup:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

Jika database sudah dibuat manual, cukup jalankan:

```bash
php bin/console doctrine:migrations:migrate
```

---

## 5.3 Opsi MySQL / MariaDB

Contoh konfigurasi MySQL:

```dotenv
DATABASE_URL="mysql://app:password@127.0.0.1:3306/learn_symfony?serverVersion=8.0.32&charset=utf8mb4"
```

Contoh konfigurasi MariaDB:

```dotenv
DATABASE_URL="mysql://app:password@127.0.0.1:3306/learn_symfony?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

Sesuaikan:

- username: `app`
- password: `password`
- database: `learn_symfony`
- port: `3306`

Langkah setup:

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

Jika database sudah dibuat manual lewat Laragon/phpMyAdmin/Adminer, cukup jalankan:

```bash
php bin/console doctrine:migrations:migrate
```

---

## 6. Jalankan Migration

Setelah `DATABASE_URL` benar, jalankan migration:

```bash
php bin/console doctrine:migrations:migrate
```

Cek status schema:

```bash
php bin/console doctrine:schema:validate
```

---

## 7. Seed Data Awal

Seed sample emiten saham:

```bash
php bin/console app:seed-sample-companies
```

Preview tanpa simpan database:

```bash
php bin/console app:seed-sample-companies --dry-run
```

---

## 8. Test Yahoo Finance

Test package Yahoo Finance tanpa menyimpan ke database:

```bash
php bin/console app:test-yahoo-finance --ticker=BBCA.JK
```

Test beberapa ticker:

```bash
php bin/console app:test-yahoo-finance --tickers=BBCA.JK,BBRI.JK,TLKM.JK
```

Lihat raw object:

```bash
php bin/console app:test-yahoo-finance --ticker=BBCA.JK --raw
```

---

## 9. Sync Data Saham ke Database

Sync semua company aktif:

```bash
php bin/console app:sync-yahoo-finance
```

Sync terbatas:

```bash
php bin/console app:sync-yahoo-finance --limit=5
```

Sync satu kode saham:

```bash
php bin/console app:sync-yahoo-finance --code=BBCA
```

Dry run tanpa simpan database:

```bash
php bin/console app:sync-yahoo-finance --code=BBCA --dry-run
```

---

## 10. Jalankan Web Server

Dengan Symfony CLI:

```bash
symfony server:start
```

Atau jika memakai Laragon:

```text
https://learn-symfony.test
```

Sesuaikan dengan virtual host lokal masing-masing.

---

## 11. URL Penting

Frontend publik:

```text
/stocks
/stocks?q=BBCA
/stocks/BBCA
```

Admin/internal:

```text
/admin
/admin/stocks
/admin/stocks?code=BBCA
/admin/stocks?status=failed
/admin/stocks/BBCA
```

Catatan:

- `/stocks` dipakai untuk tampilan publik seperti aplikasi saham.
- `/admin/stocks` dipakai untuk debugging internal dan monitoring hasil staging.

---

## 12. Asset Frontend

Jika asset belum tersedia, jalankan:

```bash
php bin/console asset-map:compile
```

Jika memakai workflow npm tambahan, jalankan sesuai kebutuhan project:

```bash
npm install
npm run build
```

Catatan: project ini memakai Symfony Asset Mapper / Importmap, jadi tidak selalu perlu build npm untuk development sederhana.

---

## 13. Command Pengecekan

Cek route:

```bash
php bin/console debug:router
```

Cek service container:

```bash
php bin/console lint:container
```

Cek Twig:

```bash
php bin/console lint:twig templates
```

Cek schema Doctrine:

```bash
php bin/console doctrine:schema:validate
```

Jalankan test PHPUnit jika diperlukan:

```bash
php bin/phpunit
```

---

## 14. Alur Setup Cepat

Ringkasan langkah paling umum:

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console app:seed-sample-companies
php bin/console app:sync-yahoo-finance --limit=5
symfony server:start
```

Lalu buka:

```text
/stocks
/admin/stocks
```

---

## 15. Catatan Development

- Jangan commit `.env.local` karena berisi konfigurasi lokal.
- Gunakan SQLite untuk setup cepat.
- Gunakan PostgreSQL/MySQL jika ingin mendekati environment production.
- Jalankan migration setelah perubahan entity.
- Jalankan seed company sebelum sync Yahoo Finance.
- Yahoo Finance memakai endpoint non-official, jadi rate limit atau perubahan response bisa terjadi.