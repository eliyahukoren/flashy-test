FROM php:7.4-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer update

CMD [ "php", "./index.php" ]
