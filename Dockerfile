FROM ubuntu:16.04

RUN mkdir -p /payum /var/log/payum /run/php

## libs
RUN apt-get update && \
    apt-get install -y --no-install-recommends wget curl openssl ca-certificates nano && \
    apt-get install -y --no-install-recommends php php-fpm php-mysql php-curl php-intl php-soap php-gd php-mbstring php-xml php-ldap php-zip php-mcrypt php-xdebug php-bcmath && \
    apt-get install -y --no-install-recommends nginx && \
    service nginx stop

RUN apt-get install -y --no-install-recommends supervisor

RUN apt-get install -y --no-install-recommends nodejs npm

RUN rm -f /etc/php/7.0/fpm/pool.d/* && \
    rm -f /etc/php/7.0/cli/conf.d/*xdebug.ini && \
    rm -f /etc/nginx/sites-enabled/*

COPY ./docker/container/php/payum_cli.ini /etc/php/7.0/cli/conf.d/1-payum_cli.ini
COPY ./docker/container/php/payum_fpm.ini /etc/php/7.0/fpm/conf.d/1-payum_fpm.ini
COPY ./docker/container/php/payum-fpm.conf /etc/php/7.0/fpm/pool.d/payum_fpm.conf
COPY ./docker/container/nginx/payum.dev /etc/nginx/sites-enabled/payum.dev
COPY ./docker/container/supervisor/payum_app.conf /etc/supervisor/conf.d/payum_app.conf
COPY ./docker/container/bin/entrypoint.sh /entrypoint.sh
COPY ./docker/container/phpstorm/ide-phpunit.php /usr/local/bin/ide-phpunit.php
COPY ./docker/container/phpstorm/ide-phpinfo.php /usr/local/bin/ide-phpinfo.php

WORKDIR /payum

CMD /entrypoint.sh