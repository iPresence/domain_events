FROM splitbrain/phpfarm:latest

WORKDIR /app/domain-events
VOLUME ["/app/domain-events"]

RUN curl -sS https://getcomposer.org/installer | php-7.1 && mv /app/domain-events/composer.phar /usr/local/bin/composer
