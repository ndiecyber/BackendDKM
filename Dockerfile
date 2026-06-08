FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
# We use www-data which is the default user for php-fpm
RUN chown -R www-data:www-data /var/www/html

# Switch to www-data user
USER www-data

# Copy application source code
COPY --chown=www-data:www-data . /var/www/html/

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
