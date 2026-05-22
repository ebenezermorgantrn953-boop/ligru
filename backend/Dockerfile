# 微信云托管 PHP 7.4 镜像
FROM php:7.4-apache

# 安装扩展：MySQL、GD、CURL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

# 云托管要求 8080 端口
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# PHP 生产配置 + 上传限制
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "max_execution_time=120" >> "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /var/www/html

COPY . /var/www/html/

# 上传目录权限
RUN mkdir -p uploads/images uploads/videos uploads/masks uploads/results \
    && chown -R www-data:www-data uploads \
    && chmod -R 755 uploads

EXPOSE 8080

CMD ["apache2-foreground"]
