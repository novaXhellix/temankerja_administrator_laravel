# SYSTEM STATE & DEVELOPMENT HISTORY

**Last Updated**: 2026-10-06 10:31:00  
**Project**: TemanKerja HRIS & Corporate Portal  

---

## 🚀 7-Agent Squad Workflow Execution Log

### 1. Faust – Code Architect
- **Geofencing & Attendance Schema Blueprint**:
  - Defined `attendance_locations`, `leave_types`, `attendances`, `leave_requests`, and `attendance_reports` tables.
  - Specified Leaflet + Google Maps tile integration (`https://{s}.google.com/vt/lyrs=m...`) for interactive coordinate selecting without API key overhead.

### 2. Mephisto – Backend Writer
- **Database Migrations & Models**:
  - `2026_10_06_033319_create_attendances_and_leaves_tables.php`: Executed migration for attendance locations and records.
  - `App\Models\AttendanceLocation`: Built model with relationships to Company and Region.
  - `App\Http\Controllers\AttendanceLocationController`: Handled CRUD API endpoints for geofencing locations with lat/lng validation (`between:-90,90` and `between:-180,180`).

### 3. Lilith – Frontend Writer
- **Interactive Google Maps Geofencing UI**:
  - `resources/js/components/InteractiveGeofenceMap.svelte`: Built interactive Leaflet component wrapped with Google Maps hybrid tile layers, draggable markers, map click handlers, and live geofence circle overlay.
  - `resources/js/pages/attendances/locations/Index.svelte`: Flat minimalist location grid cards with Live Interactive Google Maps preview side panel.
  - `resources/js/pages/attendances/locations/Create.svelte` & `Edit.svelte`: 2-column interactive forms with real-time two-way `$bindable` lat/lng/radius updates.

### 4. Malphas – Bug Finder & Static Review
- Verified no raw emojis in UI elements (all icons converted to Lucide SVG icons).
- Audited Leaflet dynamic module loading for SSR safety in Svelte 5 / Vite.

### 5. Belial – TestCraft
- **Automated Tests**:
  - `tests/Feature/AttendanceLocationTest.php`: Built Pest feature tests for location list rendering and location creation with geofence radius assertions.
  - Test Suite Status: `passed: 2, assertions: 4`.

### 6. Baal – Terminal Executor
- Executed database migrations for attendance tables.
- Ran `npm run build` with Vite compilation outputting 0 errors.

### 7. Valac – DocuMint
- Updated `docs/project-notes/SYSTEM_STATE.md` with Geofencing Interactive Google Maps implementation details.

---

### 🕒 Feature: Work Shifts & Jam Kerja Management (2026-10-06)
1. **Faust (Code Architect)**:
   - Designed work shift schema (`work_shifts` table and `work_shift_id` on `employees` table).
   - Designed model support for day shifts, night shifts (overnight/lintas hari), late arrival tolerance (minutes), and active/inactive status.
2. **Mephisto (Backend Writer)**:
   - Migration `2026_10_06_140000_create_work_shifts_table.php`.
   - `App\Models\WorkShift.php` & updated `App\Models\Employee.php` relation (`workShift()`).
   - `App\Http\Controllers\WorkShiftController.php` with search/filter, list, create, store, edit, update, destroy actions.
   - Resource route `work-shifts` added to `routes/web.php`.
3. **Lilith (Frontend Writer)**:
   - `resources/js/pages/work-shifts/Index.svelte`: Flat minimalist card grid view displaying jam masuk, jam keluar, total duration calculation, late tolerance badge, employee count badge, and search/company filters.
   - `resources/js/pages/work-shifts/Create.svelte`: 2-column interactive form for creating shift models.
   - `resources/js/pages/work-shifts/Edit.svelte`: Form for updating work shifts.
   - Updated `AppSidebar.svelte` under Human Resource menu with `Clock` Lucide icon.
4. **Malphas (Bug Finder & Static Review)**:
   - Validated strict time formats (`H:i`), integer bounds for tolerance (0-120 min), and Lucide SVG icon usage.
5. **Belial (TestCraft)**:
   - `tests/Feature/WorkShiftTest.php`: 4 Pest tests covering listing, creation, and updating of shift schedules (`passed: 4, assertions: 8`).
6. **Baal (Terminal Executor)**:
   - Executed `php artisan migrate` successfully.
   - Executed `php artisan test --filter=WorkShiftTest` (4 passed).
   - Executed `npm run build` (0 Vite errors).
7. **Valac (DocuMint)**:
   - Documented Work Shifts & Jam Kerja feature state in `docs/project-notes/SYSTEM_STATE.md`.

---

### 🏢 Feature: Master Data Departemen & Relasi Karyawan (2026-10-15)
1. **Faust (Code Architect)**:
   - **Requirement**: Entitas master data Departemen agar setiap karyawan memiliki departemen masing-masing.
   - **Device Profile Target**: FHD 1080p, layout density flat minimalist, subtle 1px border, anti-nested card clutter.
   - **Schema Blueprint**:
     - Tabel `departments`: `id`, `company_id` (nullable, null on delete), `uuid` (unique), `slug` (unique), `nama_departemen`, `kode_departemen` (unique), `deskripsi`, `status` ('Aktif'/'Non-Aktif'), `timestamps`.
     - Tabel `employees`: foreign key `department_id` (nullable, null on delete) after `region_id`.
   - **API & Relational Contract**:
     - `Department belongsTo Company`, `Department hasMany Employee`.
     - `Employee belongsTo Department`.
     - `Company hasMany Department`.
     - Resource Route: `Route::resource('departments', DepartmentController::class)`.
2. **Mephisto (Backend Writer)**:
   - Migrations:
     - `database/migrations/2026_10_15_140000_create_departments_table.php`
     - `database/migrations/2026_10_15_140500_add_department_id_to_employees_table.php`
   - Models:
     - `App\Models\Department.php` (UUID & Slug auto-generation, relations to Company & Employee).
     - `App\Models\Employee.php` (`department_id` in `$fillable`, `department(): BelongsTo`).
     - `App\Models\Company.php` (`departments(): HasMany`).
   - Controllers:
     - `App\Http\Controllers\DepartmentController.php` (CRUD: index, create, store, show, edit, update, destroy).
     - `App\Http\Controllers\EmployeeController.php` (Injected department relation, filtering, validation, and view props).
     - `App\Http\Controllers\CompanyController.php` (Injected department relation in `show`).
   - Seeders:
     - `database/seeders/DepartmentSeeder.php` (Standard enterprise units: DEPT-HRGA, DEPT-ITDEV, DEPT-FINACC, DEPT-OPSLOG, DEPT-PRODUX, DEPT-CSM, and mapped existing dummy employees).
     - `database/seeders/DatabaseSeeder.php` (Registered DepartmentSeeder).
   - Express API sync:
     - `express_api_temankerja/src/cacheService.js`: Cached `departments` table, resolved employee department object, added `getDepartments(companyId)` helper.
     - `express_api_temankerja/src/index.js`: Added `GET /api/departments` endpoint.
3. **Lilith (Frontend Writer)**:
   - Svelte 5 runes (`$props`, `$state`, `$derived`, `$effect`) & Tailwind CSS:
     - `resources/js/Pages/departments/Index.svelte`: Grid card view, search filter, status filter, company filter, employee count badge.
     - `resources/js/Pages/departments/Create.svelte`: Dedicated create form with company selection, department name, code, status, and description.
     - `resources/js/Pages/departments/Edit.svelte`: Dedicated edit form with prefilled values.
     - `resources/js/Pages/departments/Show.svelte`: Quick stats (Total Anggota, Karyawan Aktif, Perusahaan, Kode Unit), detail info, and roster list of assigned employees.
     - `resources/js/Pages/employees/Create.svelte` & `Edit.svelte`: Added Departemen selection dropdown.
     - `resources/js/Pages/employees/Show.svelte`: Displayed Departemen info in header and Card 1 (Identitas & Jabatan).
     - `resources/js/Pages/employees/Index.svelte`: Added Departemen dropdown filter and table column with Briefcase icon.
     - `resources/js/components/AppSidebar.svelte`: Added "Departemen" menu item with `Briefcase` Lucide icon.
     - `resources/js/Pages/companies/Show.svelte`: Added dedicated "Departemen & Divisi Kerja" section.
4. **Malphas – Bug Finder & Static Review**:
   - Audited foreign key cascade/null constraints (on delete set null prevents accidental data loss).
   - Audited slug uniqueness and input validations.
   - Verified 100% SVG/Lucide iconography without plain emojis.
5. **Belial – TestCraft**:
   - `tests/Feature/DepartmentTest.php`: 10 comprehensive Pest tests covering index, create, store, duplicate code validation, show by slug, edit, update, destroy, employee department assignment, and null-on-delete integrity (`passed: 10, assertions: 26`).
   - Total test suite: 98 tests pass (89 passed, 9 skipped, 0 failed, 408 assertions).
6. **Baal – Terminal Executor**:
   - `php artisan migrate` (Executed 2 migrations).
   - `php artisan db:seed --class=DepartmentSeeder` (Seeded 6 departments, mapped 8 employees).
   - `php artisan test` (98 tests passed 100%).
   - `npm run build` (Compiled in 17.67s, 0 errors).
7. **Valac – DocuMint**:
   - Documented Department Master Data & Employee Relations in `docs/project-notes/SYSTEM_STATE.md`.

---

### Milestone: React-Select Style Modern SearchableSelect & Form Edit Select Fix
- **Tanggal**: 15 Oktober 2026
- **Squad**: Hellix Crew (Faust, Mephisto, Lilith, Malphas, Belial, Baal, Valac)
- **Latar Belakang & Masalah**:
  - Beberapa elemen `<select>` pada form edit (terutama `employees/Edit.svelte`) tidak bekerja dengan baik: nilai pilihan seringkali ter-reset kembali ke nilai awal karena pemanggilan `$effect` yang re-evaluasi tak terkendali, serta tidak adanya two-way binding pada beberapa field native select.
  - Kebutuhan UX: Pengguna menginginkan seluruh elemen dropdown `<select>` menggunakan pengalaman modern seperti library **React-Select** (searchable real-time, clearable button `X`, custom dropdown menu mengambang, badge/kategori, dan integrasi hidden input untuk form submission).
- **Penyelesaian & Komponen Baru**:
  1. **`resources/js/components/SearchableSelect.svelte`**:
     - Filter pencarian instan (search query input dengan auto-focus).
     - Opsi badge & deskripsi sub-label (misal kode perusahaan, level jabatan, kuota cuti).
     - Tombol clear `X` (opsional via prop `isClearable`).
     - Deteksi klik di luar (click-outside listener) & keyboard Escape handler.
     - Input hidden terintegrasi `<input type="hidden" name={name} value={value} />` untuk kompatibilitas form Inertia.js dan `FormData`.
     - Aksesibilitas WCAG: Semantik `combobox` dan `listbox` tanpa nested button (100% bebas SSR hydration warning).
  2. **Halaman yang Telah Di-upgrade**:
     - `resources/js/pages/employees/Edit.svelte`: 7 field (`company_id`, `region_id`, `department_id`, `user_role`, `manager_id`, `status_kontrak`, `status_karyawan`) beserta cascading options.
     - `resources/js/pages/employees/Create.svelte`: 7 field identik.
     - `resources/js/pages/departments/Edit.svelte` & `Create.svelte`: `company_id` dan `status`.
     - `resources/js/pages/regions/Edit.svelte` & `Create.svelte`: `company_id` dan `status`.
     - `resources/js/pages/companies/Edit.svelte` & `Create.svelte`: `status`.
     - `resources/js/pages/work-shifts/Edit.svelte` & `Create.svelte`: `company_id` dan `status`.
     - `resources/js/pages/attendances/locations/Edit.svelte` & `Create.svelte`: `company_id` dan `region_id`.
     - `resources/js/pages/leaves/Create.svelte`: `employee_id` dan `leave_type_id`.
     - `resources/js/pages/contracts/Create.svelte`: `employee_id` dan `jenis_kontrak`.
     - `resources/js/pages/reimbursements/Create.svelte`: `employee_id` dan `reimbursement_type_id`.
  3. **Verifikasi Lingkungan**:
     - `npm run build` sukses 100% (built in 21.92s tanpa error).

---

### 📋 Milestone: Dynamic Resign Form & Enhanced Contract UX
- **Tanggal**: 15 Oktober 2026
- **Squad**: Hellix Crew (Faust, Mephisto, Lilith, Malphas, Belial, Baal, Valac)
- **Latar Belakang & Masalah**:
  - Pada form tambah dan edit karyawan (`Create.svelte` & `Edit.svelte`), input Tanggal Resign dan Alasan Resign bercampur dengan BPJS dan selalu tampil, padahal mayoritas karyawan baru/aktif tidak membutuhkan input resign.
  - Jika seorang karyawan berubah status dari "Resign" kembali ke "Aktif", data resign lama berisiko tetap tersimpan jika tidak dibersihkan secara eksplisit.
  - Untuk kontrak **PKWTT** (karyawan tetap), field periode selesai kontrak dan lama kontrak membingungkan karena karyawan tetap tidak memiliki tanggal akhir kontrak.
- **Implementasi Solusi**:
  1. **Faust (Code Architect)**:
     - Merancang aturan kondisional UI & sanitasi data: Form Resign hanya dirender jika `status_karyawan === 'Resign'`. Jika status selain Resign, nilai form otomatis mengirim string kosong / null untuk mereset riwayat resign.
     - Merancang penanganan PKWTT: input lama kontrak dinormalisasi ke "Tetap" dan input tanggal periode kontrak disembunyikan diganti banner informatif PKWTT.
  2. **Mephisto (Backend Writer)**:
     - `App\Http\Controllers\EmployeeController.php`:
       - Menambahkan auto-sanitasi pada `store()` dan `update()`: jika status karyawan bukan `Resign`, `tgl_resign` dan `alasan_resign` disetel ke `null`.
       - Jika status kontrak `PKWTT`, `lama_kontrak` dinormalisasi ke `Tetap` dan `periode_kontrak_mulai`/`periode_kontrak_selesai` disetel ke `null`.
       - Menambahkan fallback jabatan yang aman (`Karyawan`) jika field jabatan tidak dikirim.
  3. **Lilith (Frontend Writer)**:
     - `resources/js/pages/employees/Create.svelte` & `Edit.svelte`:
       - **Section 3: Status Pekerjaan & Hubungan Kontrak**: Dilengkapi status indicator pill (Karyawan Aktif emerald, Status Resign rose dengan efek pulse, dan Non-Aktif amber).
       - **Kondisi PKWTT**: Banner informatif dengan icon `Info` Lucide SVG.
       - **Section 4: Informasi Terminasi & Resign (Kondisional)**: Tampil dengan aksen soft rose, icon `UserX`, alert banner bahwa field wajib diisi saat resign, input `tgl_resign` dan textarea `alasan_resign`.
       - **Section 5: BPJS Ketenagakerjaan & Kesehatan**: Dipisahkan secara mandiri dengan icon `ShieldCheck` Lucide SVG.
     - `resources/js/pages/employees/Show.svelte`:
       - Card 2 (Kontrak & Masa Kerja) dipercantik dengan badge status dan deteksi teks "PKWTT (Karyawan Tetap)".
  4. **Malphas (Bug Finder & Static Review)**:
     - Memastikan seluruh icon menggunakan Lucide inline SVG (`UserX`, `ShieldCheck`, `Info`). Dilarang keras teks emoji mentah pada UI.
     - Memastikan input hidden tetap terikat pada state form Inertia.js sehingga submit data FormData bekerja 100%.
  5. **Belial (TestCraft)**:
     - Menambahkan 3 skenario tes pada `tests/Feature/EmployeeTest.php`:
       - `storing employee with status Resign saves resign date and reason`
       - `updating employee from Resign back to Aktif automatically clears resign data`
       - `PKWTT employee automatically normalizes lama_kontrak to Tetap and clears contract period`
     - Seluruh 7 tes lulus (26 assertions).
  6. **Baal (Terminal Executor)**:
     - `php artisan test --filter=EmployeeTest`: passed 7 tests, 26 assertions.
     - `npm run build`: sukses 100% tanpa error dalam 16.39 detik.
  5. **Belial (TestCraft)**:
     - `tests/Feature/OperationalTerritoryTest.php`: 7 skenario pengujian Pest (`passed: 7, assertions: 17, 100% Green`):
       - Listing halaman sub regions index.
       - Halaman create sub region.
       - Pembuatan sub region dengan validasi relasi provinsi.
       - Pembaruan sub region.
       - Penugasan wilayah operasional (provinsi & kota) pada perusahaan (`companies`).
       - Filter daftar perusahaan berdasarkan `region_id` dan `sub_region_id`.
       - Halaman detail perusahaan (`companies.show`) dengan wilayah operasional & sub wilayah.
  6. **Baal (Terminal Executor)**:
     - `php artisan migrate` (3 migrasi sukses diaplikasikan).
     - `php artisan db:seed --class=TerritorySeeder` (Seeder sukses dijalankan).
     - `php artisan test --filter=OperationalTerritoryTest` (7 passed, 17 assertions, 100% Green).
     - `php artisan test` (106 passed, 459 assertions, 0 failed).
     - `npm run build` (Frontend Vite + Tailwind CSS bundle terkompilasi dalam 16.07s tanpa error).
  7. **Valac (DocuMint)**:
     - `SYSTEM_STATE.md` diperbarui dengan seluruh arsitektur dan riwayat perubahan.

---

### 🏢 Penyelarasan Hierarki Wilayah: Perusahaan -> Cabang -> Sub-Wilayah
- **Arsitektur Data yang Benar**:
  - Hierarki operasional: `Company` (Perusahaan) $\to$ `Region` (Wilayah Operasional Cabang) $\to$ `SubRegion` (Sub-Wilayah / Kota / Area Kerja).
  - Menghapus keterikatan statis `region_id` & `sub_region_id` langsung pada level Perusahaan (`Company`), karena wilayah operasional sebenarnya melekat pada Cabang (`Region`), dan Sub-Wilayah dibuat serta dinaungi oleh masing-masing Cabang.
- **Backend Controller (`App\Http\Controllers\CompanyController.php` & `SubRegionController.php`)**:
  - `CompanyController`:
    - Membersihkan form `create`, `edit`, `store`, dan `update` dari input statis wilayah/sub-wilayah.
    - Memperkaya `show` dengan relasi lengkap: `regions.subRegions` (dengan hitungan `employees_count`) dan `regions.employees`.
  - `SubRegionController`:
    - Mengaitkan Sub-Wilayah langsung ke Cabang (`region_id`), dan secara otomatis mewarisi `company_id = $region->company_id`.
    - Mendukung query parameter `redirect_company` untuk alur kerja cepat kembali ke halaman detail perusahaan.
- **Frontend Svelte 5**:
  - `resources/js/pages/companies/Create.svelte` & `Edit.svelte`:
    - Menghapus Section 2 input wilayah statis yang salah paham. Form difokuskan pada identitas perusahaan & legalitas/kontak.
  - `resources/js/pages/companies/Show.svelte`:
    - Menghapus banner wilayah terisolasi.
    - Menampilkan Section "Wilayah Operasional Cabang & Sub-Wilayah": setiap kartu cabang menampilkan badge & chip seluruh Sub-Wilayah (Kota) di bawahnya, jumlah karyawan per sub-wilayah, tombol aksi cepat `+ Tambah Sub-Wilayah` (pre-filled `region_id` & `company_id`), serta tombol edit/hapus sub-wilayah.
  - `resources/js/pages/sub-regions/Create.svelte` & `Edit.svelte`:
    - Label pemilih induk diperbarui menjadi "Cabang / Wilayah Operasional Induk *" lengkap dengan badge perusahaan dan kode cabang.
  - `resources/js/pages/sub-regions/Show.svelte`:
    - Kartu induk menampilkan informasi Cabang & Perusahaan Induk lengkap dengan link navigasi langsung.
- **Penghapusan Route & Halaman `/regions` (Index Wilayah)**:
  - Menu 'Wilayah Operasional' dihapus dari `AppSidebar.svelte` karena seluruh pengelolaan cabang dan sub-wilayah kini terpusat langsung di dalam masing-masing Perusahaan (`/companies/{slug}`).
  - Halaman `resources/js/pages/regions/Index.svelte` dihapus permanen.
  - Route `/regions` dialihkan 100% via redirect (`Route::redirect('/regions', '/companies')` dan `RegionController@index` $\to$ `companies.index`).
  - Seluruh breadcrumb dan tombol pembatalan/kembali pada form dan detail cabang serta sub-wilayah diselaraskan ke Perusahaan (`/companies`).

---

### 📦 Penyederhanaan Skema Customer (Database & UI)
- **Database Migration (`2026_10_15_103610_remove_company_id_and_status_from_customers_table.php`)**:
  - Menghapus foreign key `company_id` (Perusahaan Induk) dan kolom `status` (Status Kerjasama) dari tabel `customers`.
- **Model & Controller**:
  - `App\Models\Customer.php`: Membersihkan `$fillable` dan menghapus relasi `company()`.
  - `App\Models\Company.php`: Menghapus relasi `customers()`.
  - `App\Http\Controllers\CustomerController.php`:
    - Mengeliminasi validasi `company_id` dan `status` dari `store` dan `update`.
    - Menyederhanakan `index`, `create`, `edit`, dan `show` tanpa ketergantungan model `Company`.
    - Filter bar difokuskan pada pencarian cepat (Nama, Kode, PIC, Telepon, Alamat).
  - `App\Http\Controllers\CustomerRouteController.php`: Menghapus relasi `customer.company` dan penarikan kolom `company_id` dari model Customer.
- **Frontend Svelte 5 (`resources/js/pages/customers/`)**:
  - `Create.svelte` & `Edit.svelte`: Menghapus input "Perusahaan Induk (Opsional)" dan "Status Kerjasama *". Formulir kini bersih dan fokus pada identitas customer & kontak PIC.
  - `Index.svelte`: Menghapus filter perusahaan dan status, menghapus badge status pada kartu, serta memperbarui statistik menjadi Total Customer, Total Rute, dan Total Jarak (KM).
  - `Show.svelte`: Menghapus badge status kerjasama dan terafiliasi perusahaan, memperbarui kartu ringkasan dengan Tanggal Terdaftar.
- **Validasi Kualitas & Otomasi**:
  - `tests/Feature/CustomerRouteTest.php`: 9 passed, 26 assertions (100% Green).
  - Full test suite: 108 passed, 467 assertions, 0 failed.
  - `npm run build`: bundle frontend terkompilasi 100% tanpa error dalam 16.94s.

