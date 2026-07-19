FROM php:8.2-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip

RUN install-php-extensions pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd intl

RUN echo "upload_max_filesize = 12M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini
# Berikan hak akses kepada www-data untuk folder temporary Nginx
RUN mkdir -p /var/lib/nginx/tmp/client_body && \
    chown -R www-data:www-data /var/lib/nginx && \
    chmod -R 777 /var/lib/nginx/tmp

WORKDIR /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
