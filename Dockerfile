FROM php:8.2-fpm-alpine

# Install essential packages
RUN apk add --no-cache \
    curl \
    wget \
    git \
    mysql-client \
    freetype \
    freetype-dev \
    libjpeg \
    libjpeg-turbo-dev \
    libpng \
    libpng-dev \
    zlib \
    zlib-dev \
    libzip \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    oniguruma-dev \
    build-base

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    ctype \
    json \
    bcmath \
    zip \
    fileinfo \
    gd \
    intl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

COPY . .

RUN chown -R 82:82 /var/www/html

RUN composer install --no-dev --optimize-autoloader

RUN npm install && npm run build

EXPOSE 9000

CMD ["php-fpm"]
