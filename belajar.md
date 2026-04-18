# Panduan Belajar Symfony: Blog System

Dokumen ini berisi langkah-langkah untuk membangun sistem blog menggunakan Symfony Framework.

## 1. Setup User Entity
Langkah pertama adalah membuat entitas User untuk autentikasi.

```bash
# Inisialisasi User Security
php bin/console make:user
# Konfigurasi: class: User, property: email, hashing: yes

# Tambah Property Tambahan
php bin/console make:entity User
# - name (string, 100, not null)
# - createdAt (datetime_immutable, not null)
```

### 🛠️ Edit Manual: `src/Entity/User.php`
- Tambahkan `#[ORM\HasLifecycleCallbacks]` di atas definisi class.
- Atur inisialisasi `createdAt` pada constructor atau menggunakan method `PrePersist`.

---

## 2. Setup Blog Category
Membuat kategori untuk artikel blog.

```bash
php bin/console make:entity BlogCategory
# - title (string, 100, not null)
# - slug (string, 120, not null)
```

### 🛠️ Edit Manual: `src/Entity/BlogCategory.php`
- Tambahkan `unique: true` pada anotasi `title` dan `slug`.
- Tambahkan method `__toString()` yang mengembalikan `$this->title`.

---

## 3. Setup Blog Entity
Membuat entitas utama untuk konten blog.

```bash
php bin/console make:entity Blog
# - title (string, 255, not null)
# - slug (string, 255, not null)
# - body (text, not null)
# - thumbnail (string, 255, nullable: yes)
# - status (string, 20, not null)
# - isEnable (boolean, not null)
# - createdAt (datetime_immutable, not null)
# - updatedAt (datetime_immutable, nullable: yes)
```

---

## 4. Setup Relasi (Relationships)
Hubungkan Blog dengan Kategori dan Penulis.

```bash
# Relasi Blog -> BlogCategory (ManyToOne)
php bin/console make:entity Blog
# - field: category (ManyToOne -> BlogCategory, nullable: no)
# - map back to BlogCategory: yes (blogs)

# Relasi Blog -> User (ManyToOne)
php bin/console make:entity Blog
# - field: author (ManyToOne -> User, nullable: no)
# - map back to User: no
```

### 🛠️ Edit Manual: `src/Entity/Blog.php`
- Tambahkan `#[ORM\HasLifecycleCallbacks]` di atas definisi class.
- Set default value: `status = 'draft'`, `isEnable = true`.
- Gunakan `PrePersist` untuk `createdAt` dan `PreUpdate` untuk `updatedAt`.

---

## 5. Sinkronisasi Database
Langkah untuk menerapkan perubahan entitas ke database.

```bash
# Validasi skema
php bin/console doctrine:schema:validate

# Buat dan jalankan migrasi
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 6. Data Fixtures
Mengisi database dengan data awal untuk testing.

```bash
php bin/console doctrine:fixtures:load
```

## 7. Setup Security (Admin Login)
Konfigurasi autentikasi untuk area administrator.

### 🛠️ Step 0: `config/packages/framework.yaml`
Pastikan CSRF protection aktif:
```yaml
framework:
    csrf_protection: true
```

### 🛠️ Step A: `config/packages/security.yaml`
1. Atur `password_hashers` untuk `App\Entity\User`.
2. Tambahkan firewall `admin` sebelum firewall `main`.
3. Gunakan `form_login` dengan:
   - `login_path: admin_login`
   - `check_path: admin_login`
   - `default_target_path: admin_dashboard`
4. Atur `access_control` untuk membatasi `^/admin` ke `ROLE_ADMIN`.

### 🛠️ Step B: `src/Controller/Admin/AuthController.php`
Buat controller untuk menangani login dan logout:
```php
#[Route('/admin/login', name: 'admin_login')]
public function login(AuthenticationUtils $authUtils): Response { ... }

#[Route('/admin/logout', name: 'admin_logout')]
public function logout(): void { ... }
```

### 🛠️ Step C: `templates/admin/auth/login.html.twig`
Buat form login yang mengirimkan `email`, `password`, dan `_csrf_token`.

---

## 💡 Tips & Reset
Jika terjadi kesalahan fatal dan ingin mengulang dari awal (Hard Reset).

### Hapus File Terkait
Gunakan perintah ini di terminal (Windows):
```powershell
del var\data_dev.db
del migrations\Version\*.php
del src\Entity\*.php
del src\Repository\*.php
```

> [!WARNING]
> Hati-hati! Perintah di atas akan menghapus semua data dan kode entitas yang sudah dibuat.
