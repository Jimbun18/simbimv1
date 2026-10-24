# SIM-BIM — Sistem Informasi Manajemen SDI Bina Insan Mandiri Purwokerto

Aplikasi manajemen sekolah dasar berbasis web (PWA) yang mengintegrasikan
data guru & siswa, absensi, administrasi, keuangan, sarana prasarana, serta
portal pemantauan progres anak bagi wali murid.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + Vite + Tailwind CSS (PWA) |
| Database | MySQL 8 |
| Cache/Queue | Redis / Database queue |
| Auth | Laravel Sanctum |

## Prasyarat

- PHP 8.3+
- Composer 2.x
- Node.js 20+ & npm
- MySQL 8.0
- Redis (opsional, bisa pakai database queue)
- Docker & Docker Compose (opsional, untuk deployment)

## Instalasi (Development Lokal)

### 1. Clone & Setup Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
```

### 2. Konfigurasi Database

Edit `backend/.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simbim
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi & Seeder

```bash
cd backend
php artisan migrate
php artisan db:seed
```

### 4. Setup Frontend

```bash
cd frontend
npm install
```

### 5. Jalankan Development Server

```bash
# Terminal 1 — Backend
cd backend
php artisan serve

# Terminal 2 — Frontend
cd frontend
npm run dev

# Terminal 3 — Queue worker (opsional)
cd backend
php artisan queue:work
```

## Deployment (Docker)

```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

## Testing

```bash
# Backend
cd backend
php artisan test

# Frontend
cd frontend
npm run test
```

## Struktur Proyek

```
simbim/
├── backend/         # Laravel 11 REST API
├── frontend/        # Vue 3 + Vite SPA/PWA
├── nginx/           # Konfigurasi Nginx
├── docs/            # PRD, ERD, API catalog
└── docker-compose.yml
```

## Dokumentasi

- [PRD](docs/PRD.md) — Product Requirements Document
- [Pertanyaan Terbuka](docs/OPEN_QUESTIONS.md)
- [ERD](docs/ERD.md) — Entity Relationship Diagram *(segera)*
- [API Catalog](docs/API.md) *(segera)*


## Lisensi

Proprietary — SDI Bina Insan Mandiri Purwokerto.
