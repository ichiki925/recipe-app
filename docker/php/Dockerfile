FROM php:8.1-fpm

COPY php.ini /usr/local/etc/php/

# OSパッケージ + MeCab + ビルドに必要なライブラリ
RUN apt-get update \
  && apt-get install -y \
  default-mysql-client \
  zlib1g-dev \
  libzip-dev \
  unzip \
  mecab \
  libmecab-dev \
  mecab-ipadic-utf8 \
  && docker-php-ext-install pdo_mysql zip \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Composer（必要なら入れる）
RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www
