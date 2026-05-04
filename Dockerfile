FROM php:8.3-cli

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libpq-dev \
        netcat-openbsd \
        unzip \
        zip \
    && docker-php-ext-install bcmath pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid app --create-home --shell /bin/sh app \
    && chown -R app:app /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/waha-dash-entrypoint
RUN chmod +x /usr/local/bin/waha-dash-entrypoint

USER app

EXPOSE 8000

ENTRYPOINT ["waha-dash-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
