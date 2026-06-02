FROM php:8.2-fpm

# Install system dependencies + default-mysql-client for tools/healthchecks
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Fetch up-to-date Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy existing repository files into the environment
COPY . /var/www

# Safely copy and expose entrypoint runner
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Guarantee proper directory ownership for webserver processes
RUN chown -R www-data:www-data /var/www

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
