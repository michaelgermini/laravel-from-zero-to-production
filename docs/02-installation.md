# Chapter 2: Installation and Setup

## Prerequisites

Before installing Laravel, you need to ensure your development environment meets the following requirements:

### System Requirements

#### PHP Requirements
- **PHP 8.1 or higher**
- **Required PHP Extensions**:
  - BCMath PHP Extension
  - Ctype PHP Extension
  - cURL PHP Extension
  - DOM PHP Extension
  - Fileinfo PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PCRE PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension

#### Additional Software
- **Composer** (PHP package manager)
- **Node.js & NPM** (for frontend asset compilation)
- **Database** (MySQL, PostgreSQL, SQLite, or SQL Server)
- **Web Server** (Apache, Nginx, or Laravel's built-in server)

## Installing Prerequisites

### 1. Installing PHP

#### On Windows
1. **Download XAMPP or WAMP**:
   - XAMPP: [apachefriends.org](https://www.apachefriends.org/)
   - WAMP: [wampserver.com](https://www.wampserver.com/)

2. **Or use Chocolatey**:
   ```bash
   choco install php
   ```

#### On macOS
1. **Using Homebrew**:
   ```bash
   brew install php
   ```

2. **Using MAMP**:
   - Download from [mamp.info](https://www.mamp.info/)

#### On Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath
```

#### On Linux (CentOS/RHEL)
```bash
sudo yum install epel-release
sudo yum install php php-cli php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath
```

### 2. Installing Composer

#### On Windows
1. **Download Composer-Setup.exe** from [getcomposer.org](https://getcomposer.org/download/)
2. Run the installer and follow the setup wizard

#### On macOS/Linux
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Installing Node.js

#### On Windows
1. Download from [nodejs.org](https://nodejs.org/)
2. Run the installer

#### On macOS
```bash
brew install node
```

#### On Linux
```bash
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## Installing Laravel

### Method 1: Using Laravel Installer (Recommended)

The Laravel installer is the easiest way to create new Laravel projects.

#### 1. Install Laravel Installer
```bash
composer global require laravel/installer
```

#### 2. Create a New Project
```bash
laravel new my-project
cd my-project
```

### Method 2: Using Composer Create-Project

If you prefer not to install the Laravel installer globally:

```bash
composer create-project laravel/laravel my-project
cd my-project
```

### Method 3: Using Laravel Sail (Docker)

Laravel Sail provides a Docker-based development environment:

```bash
curl -s "https://laravel.build/my-project" | bash
cd my-project
./vendor/bin/sail up
```

## Project Setup

### 1. Environment Configuration

After creating your Laravel project, you need to configure the environment:

```bash
# Copy the environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Configuration

Edit the `.env` file to configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (if using frontend assets)
npm install
```

### 4. Run Migrations

```bash
php artisan migrate
```

## Development Server

### Using Laravel's Built-in Server

Laravel includes a development server for local development:

```bash
php artisan serve
```

This will start the server at `http://localhost:8000`

### Using Laravel Sail (Docker)

If you're using Laravel Sail:

```bash
./vendor/bin/sail up
```

### Using Other Web Servers

#### Apache Configuration
Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName my-project.local
    DocumentRoot /path/to/my-project/public
    
    <Directory /path/to/my-project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name my-project.local;
    root /path/to/my-project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## IDE Setup

### Recommended IDEs

1. **PhpStorm** (JetBrains)
   - Full Laravel support
   - Built-in debugging
   - Code completion

2. **Visual Studio Code**
   - Free and lightweight
   - Excellent Laravel extensions
   - Integrated terminal

3. **Sublime Text**
   - Fast and lightweight
   - Extensive plugin ecosystem

### Useful Extensions for VS Code

- **Laravel Extension Pack**
- **PHP Intelephense**
- **Laravel Blade Snippets**
- **Laravel Artisan**
- **Laravel Snippets**

## Project Structure

After installation, your Laravel project will have the following structure:

```
my-project/
├── app/                    # Application core code
│   ├── Console/           # Artisan commands
│   ├── Exceptions/        # Exception handlers
│   ├── Http/              # HTTP layer
│   │   ├── Controllers/   # Controllers
│   │   ├── Middleware/    # Middleware
│   │   └── Requests/      # Form requests
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Database files
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── public/                # Web server document root
├── resources/             # Application resources
│   ├── css/              # CSS files
│   ├── js/               # JavaScript files
│   ├── lang/             # Language files
│   └── views/            # Blade templates
├── routes/                # Route definitions
├── storage/               # Application storage
├── tests/                 # Test files
├── vendor/                # Composer dependencies
├── .env                   # Environment variables
├── .env.example          # Environment template
├── .gitignore            # Git ignore file
├── artisan               # Artisan command-line tool
├── composer.json         # Composer configuration
└── package.json          # NPM configuration
```

## Common Issues and Solutions

### 1. Permission Issues

#### On Linux/macOS
```bash
# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### On Windows
Ensure your web server has write permissions to the `storage` and `bootstrap/cache` directories.

### 2. Composer Memory Limit

If you encounter memory issues during installation:

```bash
# Increase PHP memory limit
php -d memory_limit=-1 composer install
```

### 3. SSL Certificate Issues

For local development, you can ignore SSL certificate issues:

```bash
composer config --global disable-tls true
```

### 4. Extension Missing

If you get an error about a missing PHP extension:

```bash
# On Ubuntu/Debian
sudo apt-get install php8.1-[extension-name]

# On CentOS/RHEL
sudo yum install php-[extension-name]
```

## Verification

To verify your installation is working correctly:

1. **Start the development server**:
   ```bash
   php artisan serve
   ```

2. **Visit your application**:
   Open your browser and navigate to `http://localhost:8000`

3. **Check Laravel version**:
   ```bash
   php artisan --version
   ```

4. **Run health check**:
   ```bash
   php artisan about
   ```

## Next Steps

Now that you have Laravel installed and running, you're ready to start building your first application. In the next chapter, we'll explore Laravel's routing system and learn how to define the URLs that your application will respond to.

## Summary

In this chapter, we covered:

- ✅ Installing PHP and required extensions
- ✅ Installing Composer and Node.js
- ✅ Creating a new Laravel project
- ✅ Configuring the environment
- ✅ Setting up the development server
- ✅ Understanding the project structure
- ✅ Troubleshooting common issues

Your Laravel development environment is now ready! Let's move on to learning about routing in the next chapter.
