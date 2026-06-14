## Ringkasan Implementasi Datasource Saham Indonesia — Prompt 1 s/d Prompt 5

Project ini membuat proof of concept datasource saham Indonesia di Symfony menggunakan Yahoo Finance melalui package `scheb/yahoo-finance-api`. Format ticker Yahoo Finance memakai suffix `.JK`, contoh `BBCA.JK`.

---

## 1. Test Package Yahoo Finance

Tahap awal berfokus untuk memahami dan menguji package Yahoo Finance tanpa menyimpan data ke database.

### Command

```bash
php bin/console app:test-yahoo-finance
```

### Tujuan

Command ini digunakan untuk:

- test koneksi ke Yahoo Finance
- fetch data ticker saham Indonesia
- melihat field/getter apa saja yang tersedia dari object `Quote`
- mencoba batch fetch dengan `getQuotes()`
- fallback ke `getQuote()` per ticker jika batch gagal
- melihat raw structure object dengan option `--raw`

### Contoh Command Testing

```bash
php bin/console app:test-yahoo-finance
php bin/console app:test-yahoo-finance --ticker=BBCA.JK
php bin/console app:test-yahoo-finance --tickers=BBCA.JK,BBRI.JK,TLKM.JK
php bin/console app:test-yahoo-finance --ticker=BBCA.JK --raw
```

---

## 2. Struktur Database / Entity

Dibuat tiga entity utama untuk kebutuhan master emiten, staging data hasil fetch, dan log proses sync.

### `Company`

Fungsi:

- menyimpan master emiten
- menyimpan kode saham lokal, misalnya `BBCA`
- menyimpan ticker Yahoo Finance, misalnya `BBCA.JK`
- menentukan apakah emiten aktif untuk disync

Field penting:

- `code`
- `name`
- `yahooTicker`
- `isActive`
- `createdAt`
- `updatedAt`

### `StockDataStaging`

Fungsi:

- menyimpan hasil fetch Yahoo Finance
- menjadi tabel staging sebelum data dipakai lebih lanjut
- menyimpan status fetch per ticker

Field penting:

- `company`, `code`, `ticker`, `source`
- `price`, `openPrice`, `highPrice`, `lowPrice`, `volume`
- `peRatio`, `eps`, `marketCap`, `currency`, `marketTime`
- `status`, `rawPayload`, `missingFields`, `errorMessage`, `fetchedAt`

Status yang digunakan:

- `success`
- `partial`
- `failed`

### `DataSourceLog`

Fungsi:

- mencatat satu proses sync Yahoo Finance
- menyimpan ringkasan jumlah sukses, partial, dan failed

Field penting:

- `source`
- `status`
- `totalSuccess`
- `totalPartial`
- `totalFailed`
- `message`
- `startedAt`
- `finishedAt`

---

## 3. Seeder Sample Emiten

Dibuat command untuk mengisi sample master emiten ke tabel `Company`.

### Command

```bash
php bin/console app:seed-sample-companies
```

### Fungsi

- melakukan upsert berdasarkan `Company.code`
- aman dijalankan berkali-kali
- jika data sudah ada, update `name`, `yahooTicker`, `isActive`, `updatedAt`
- jika belum ada, buat data baru
- tidak menghapus data lama

### Dry Run

```bash
php bin/console app:seed-sample-companies --dry-run
```

Mode `--dry-run` hanya menampilkan data yang akan dibuat/update tanpa menyimpan ke database.

### Contoh Emiten

- `BBCA` → `BBCA.JK`
- `BBRI` → `BBRI.JK`
- `BMRI` → `BMRI.JK`
- `TLKM` → `TLKM.JK`
- `ASII` → `ASII.JK`

---

## 4. Backend Sync Yahoo Finance

Dibuat backend service dan command untuk sync data dari Yahoo Finance ke tabel `StockDataStaging`.

### Command

```bash
php bin/console app:sync-yahoo-finance
```

### Alur Sync

1. Ambil data `Company` dengan `isActive = true`
2. Ambil `yahooTicker`, contoh `BBCA.JK`
3. Fetch data dari Yahoo Finance
4. Normalize hasil quote
5. Simpan hasil ke `StockDataStaging`
6. Catat proses sync ke `DataSourceLog`
7. Jika satu ticker gagal, proses ticker lain tetap lanjut

### Option Command

```bash
php bin/console app:sync-yahoo-finance --limit=5
php bin/console app:sync-yahoo-finance --code=BBCA
php bin/console app:sync-yahoo-finance --code=BBCA --dry-run
```

### Status Sync

- `success`: data penting seperti `price` dan `volume` tersedia
- `partial`: request berhasil tetapi `price` atau `volume` kosong
- `failed`: fetch gagal atau data tidak berhasil didapatkan

### Service Utama

- `YahooFinanceProvider`: fetch quote dari Yahoo Finance
- `StockDataNormalizer`: mapping field Yahoo Finance ke struktur internal
- `StockDataSyncService`: sync banyak company, simpan staging, dan buat log

---

## 5. Frontend dan Admin/Internal

Data staging kemudian ditampilkan dalam dua area.

### Frontend Publik

Frontend publik ditujukan untuk tampilan seperti aplikasi saham pada umumnya.

File utama:

```text
src/Controller/StockController.php
templates/stocks/index.html.twig
templates/stocks/show.html.twig
```

URL testing:

```text
/stocks
/stocks?q=BBCA
/stocks/BBCA
```

Fungsi:

- `/stocks` menampilkan daftar saham
- `/stocks?q=BBCA` mencari saham berdasarkan kode/nama/ticker
- `/stocks/BBCA` menampilkan detail saham berdasarkan kode

### Admin / Internal Debugging

Area admin/internal digunakan untuk melihat data staging, status sync, raw payload, missing field, dan error message.

File utama:

```text
src/Controller/Admin/StockController.php
templates/admin/stocks/index.html.twig
templates/admin/stocks/show.html.twig
```

URL testing:

```text
/admin/stocks
/admin/stocks?code=BBCA
/admin/stocks?status=failed
/admin/stocks/BBCA
```

Catatan:

- `/admin/stocks` dipakai untuk debugging internal dan monitoring data staging.
- `/stocks` dipakai untuk tampilan publik seperti aplikasi saham.
- Tampilan admin mengikuti layout `templates/_layouts/admin.html.twig`.

---

## Daftar File Utama yang Dibuat / Diubah

### Command

```text
src/Command/TestYahooFinanceCommand.php
src/Command/SeedSampleCompaniesCommand.php
src/Command/SyncYahooFinanceCommand.php
```

### Entity

```text
src/Entity/Company.php
src/Entity/StockDataStaging.php
src/Entity/DataSourceLog.php
```

### Repository

```text
src/Repository/CompanyRepository.php
src/Repository/StockDataStagingRepository.php
src/Repository/DataSourceLogRepository.php
```

### Service

```text
src/Service/StockData/YahooFinanceDebugService.php
src/Service/StockData/YahooFinanceProvider.php
src/Service/StockData/StockDataNormalizer.php
src/Service/StockData/StockDataSyncService.php
```

### Controller

```text
src/Controller/StockController.php
src/Controller/Admin/StockController.php
```

### Template

```text
templates/stocks/index.html.twig
templates/stocks/show.html.twig
templates/admin/stocks/index.html.twig
templates/admin/stocks/show.html.twig
```

### Migration / Config

```text
migrations/Version20260612085337.php
config/services.yaml
config/packages/cache.yaml
```

---

## Command Testing Utama

### Jalankan Migration

```bash
php bin/console doctrine:migrations:migrate
```

### Seed Sample Company

```bash
php bin/console app:seed-sample-companies
```

### Test Yahoo Finance Tanpa Database

```bash
php bin/console app:test-yahoo-finance --ticker=BBCA.JK
```

### Sync Yahoo Finance ke Staging

```bash
php bin/console app:sync-yahoo-finance --limit=5
php bin/console app:sync-yahoo-finance --code=BBCA
php bin/console app:sync-yahoo-finance --code=BBCA --dry-run
```

---

## Cara Cek Data Setelah Sync

1. Jalankan migration:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

2. Isi master emiten:

   ```bash
   php bin/console app:seed-sample-companies
   ```

3. Jalankan sync:

   ```bash
   php bin/console app:sync-yahoo-finance --limit=5
   ```

4. Cek hasil di admin/internal:

   ```text
   /admin/stocks
   /admin/stocks?code=BBCA
   /admin/stocks?status=failed
   /admin/stocks/BBCA
   ```

5. Cek tampilan publik:

   ```text
   /stocks
   /stocks?q=BBCA
   /stocks/BBCA
   ```

---

## Catatan

Package `scheb/yahoo-finance-api` menggunakan non-official Yahoo Finance endpoint, sehingga kemungkinan rate limit atau perubahan endpoint tetap perlu diantisipasi. Untuk proof of concept ini sudah ditambahkan retry, cache context Yahoo Finance, safe getter, dan fallback handling agar proses testing/sync lebih stabil.