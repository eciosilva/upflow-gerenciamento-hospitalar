FROM php:8.4-fpm

# Arguments defined in docker-compose.yaml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring pcntl bcmath xml

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
RUN printf '[PHP]\ndate.timezone = "America/Sao_Paulo"\n' > /usr/local/etc/php/conf.d/tzone.ini

# Create Laravel directories and set permissions
RUN mkdir -p /var/www/bootstrap/cache /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache && \
    chown -R $user:www-data /var/www/bootstrap/cache /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache /var/www/storage

# Set working directory
WORKDIR /var/www

USER $user