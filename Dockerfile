# Production image for Render: PHP 8.2 + Apache serving this project at the site root.
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite headers

# Uploads up to 200 MB (videos), with enough time for slow connections.
RUN { \
        echo 'upload_max_filesize = 200M'; \
        echo 'post_max_size = 200M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 300'; \
        echo 'max_input_time = 300'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
    } > /usr/local/etc/php/conf.d/app.ini

# Render ends HTTPS at its proxy; tell PHP the original request was HTTPS so secure cookies work.
RUN echo 'SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on' > /etc/apache2/conf-enabled/forwarded-https.conf

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/videos /var/www/html/uploads/branding /var/www/html/uploads/characters \
    && chown -R www-data:www-data /var/www/html/uploads

# Render tells the app which port to use through $PORT (80 when run locally).
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
