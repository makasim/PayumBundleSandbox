#!/usr/bin/env bash

mkdir -p $PAYUM_ROOT_DIR;
(rm -rf /payum && cd / && ln -s $PAYUM_ROOT_DIR payum)

/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf -l /var/log/payum/supervisor.log

tail --pid $$ -F /payum/app/logs/* &
tail --pid $$ -F /var/log/payum/* &
