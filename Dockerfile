# 微信云托管 - 仓库根目录构建（GitHub 流水线用）
# 业务代码在 backend/ 目录
FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "max_execution_time=120" >> "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /var/www/html

COPY backend/ /var/www/html/

RUN mkdir -p uploads/images uploads/videos uploads/masks uploads/results \
    && chown -R www-data:www-data uploads \
    && chmod -R 755 uploads

EXPOSE 8080

CMD ["apache2-foreground"]
