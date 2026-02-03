# Gunakan image PHP dengan Apache yang siap pakai
FROM php:8.2-apache

# Install ekstensi yang dibutuhkan Laravel & PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer (Manajer Paket PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy semua file project ke dalam container
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Setting Port Apache agar sesuai dengan Render (Port 10000)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Install dependency Laravel
RUN composer install --no-dev --optimize-autoloader

# Atur permission folder storage (PENTING agar tidak error 500)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Perintah untuk menjalankan server
CMD php artisan migrate --force && apache2-foreground