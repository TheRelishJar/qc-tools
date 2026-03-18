FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip bcmath \
    && docker-php-ext-install mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node 24
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy and install PHP deps first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --ignore-platform-reqs

# Copy and install Node deps
COPY package.json package-lock.json ./
RUN npm install

# Copy the rest of the app
COPY . .

# Finish composer install
RUN composer dump-autoload --optimize --ignore-platform-reqs

# Get Chromium for PDF exports
RUN apt-get update && apt-get install -y \
    chromium \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

EXPOSE 9000
CMD ["php-fpm"]