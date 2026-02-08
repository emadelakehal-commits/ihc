## IHC - Ibiidi Heating Catalog

### Prerequisites

Before setting up this project, ensure you have the following installed:

#### System Requirements
- **PHP 8.1 or higher** with the following extensions:
  - `ext-pdo` (required for database operations)
  - `ext-mbstring` (required for Unicode string handling)
  - `ext-openssl` (required for secure connections)
  - `ext-tokenizer` (required by Laravel framework)
  - `ext-xml` (required for XML processing)
  - `ext-curl` (required for HTTP requests and API calls)
  - `ext-json` (required for JSON data handling)
  - `ext-gd` (required for image processing and uploads)
  - `ext-zip` (required for ZIP file extraction and Excel processing)
  - `ext-simplexml` (required for Excel file processing via PhpSpreadsheet)

**Note:** These extensions are typically included by default in most PHP installations (XAMPP, WAMP, MAMP, and standard hosting environments). No manual installation should be required in most cases.

#### Optional Dependencies
- **Apache/NGINX** (web server)
- **XAMPP/MAMP/WAMP** (local development environment)

**Note:** Redis is configured in the application but not actively used. The project currently uses database-based caching and queue management.

#### PM2 Requirements (for Production Deployments)
**Node.js and npm are required for PM2:**
- **Node.js 18+** (same version as Development Tools requirement)
- **npm** (bundled with Node.js)
- **PM2** (installed via: `npm install -g pm2`)

**Note:** Node.js is required for PM2 process management in this microservice architecture. Vite asset compilation is not needed for this API-only service.

#### Database Requirements
- **MySQL 8.0+** 
  - UTF8MB4 charset support
  - InnoDB storage engine

#### Environment Setup
- **Operating System:** Linux, macOS, or Windows
- **Memory:** Minimum 2GB RAM (4GB recommended)
- **Storage:** Minimum 1GB free space

#### PHP Configuration
Ensure your `php.ini` has these settings:
```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

**Testing Framework:**
- **PHPUnit** for unit and feature testing
- Built-in Laravel testing utilities
- SQLite in-memory database for testing


### Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd ihc
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   set values in .env
   set the JWT_SECRET as f10d08554d83513cc75911ad1899030f0958620414fc971f2b5ae7dd5c424e639165abc63bd7345c5f076ca3d5d09d0ab23369833c6af0c037d4ee636f0a05b4
   set the CORS_ALLOWED_ORIGINS : ibiidi heating website link
   php artisan key:generate
   ```

6. **Set up database connection** in `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ihc
   DB_USERNAME=root
   DB_PASSWORD=
   ```

7. **Run database setup:**
   ```bash
php artisan ihc:setup-database 
php artisan migrate --force
php artisan storage:ensure-directories  # This will create the directories
php artisan db:seed --force



   ```

8. **Start the development server:**
   ```bash
   php artisan serve
   ```

The application will be available at `http://127.0.0.1:8000`

### Upload Product Images
Incase you don't have the product images on your local machine download them from : https://drive.google.com/drive/folders/1tdD2d4DkMjoAwAGf8ZiMBqzveBp8Fpw0?usp=sharing

compress the folder then :
scp -r /local/folder/path username@server_ip:/remote/project/folder/path/storage/app/public/products/


### Run Project 
pm2 start artisan \
  --name laravel \
  --interpreter php \
  --cwd /path/to/project/folder/YOUR_LARAVEL_PROJECT \
  -- serve --host=0.0.0.0 --port=8000

pm2 status
pm2 logs laravel

