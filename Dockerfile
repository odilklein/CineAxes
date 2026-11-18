FROM php:8.2-apache

# Extensões necessárias: PDO/MySQL (obrigatório), mbstring (acentos e iniciais).
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql mbstring

# O DocumentRoot passa a ser /var/www/html/public.
# Importante: sites-enabled é carregado depois de conf-enabled, então a troca
# precisa acontecer no próprio 000-default.conf.
# Endurecimento: assinatura do Apache e TRACE.
COPY docker/apache-security.conf /etc/apache2/conf-available/zz-personacut-security.conf
COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-security.ini

RUN a2enmod rewrite headers \
    && a2enconf zz-personacut-security \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/personacut.conf \
    && a2enconf personacut

COPY --chown=www-data:www-data . /var/www/html/

EXPOSE 80
