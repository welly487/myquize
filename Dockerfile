FROM php:8.3-apache

# PostgreSQL 驅動 + 常用擴充
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# 正式環境建議關閉錯誤直接顯示在頁面上，只寫進 log
RUN { \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
    } > /usr/local/etc/php/conf.d/production-errors.ini

WORKDIR /var/www/html
COPY . /var/www/html/

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
