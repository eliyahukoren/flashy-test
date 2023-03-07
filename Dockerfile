FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
  zip \
  unzip

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer require --dev phpunit/phpunit ^9

RUN ./vendor/bin/phpunit src/tests

CMD [ "php", "./index.php" ]
