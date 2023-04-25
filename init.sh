#!/bin/bash

nginx_log_redir() {
    #log redirection
    ln -sf /dev/stdout /var/log/nginx/access.log
    ln -sf /dev/stderr /var/log/nginx/error.log
}

if [[ $1 == "serve" ]]; then
    /usr/bin/supervisord -c /etc/supervisor.conf

elif [[ $1 == "debug" ]]; then
    nginx_log_redir
    /usr/bin/supervisord -c /etc/supervisor.conf

elif [[ $1 == "check" ]]; then
    cd /var/www/app/
    rm -rf beans
    bean-check waldenpoint_accounting/waldenpoint.beancount

elif [[ $1 == "bake" ]]; then
    cd /var/www/app
    bean-check waldenpoint_accounting/waldenpoint.beancount
    if [[ $? == 0 ]]; then
        rm -rf beans
        bean-bake waldenpoint_accounting/waldenpoint.beancount beans
    fi

elif [[ $1 == "bash" ]]; then
    cd /var/www/app/
    bash

elif [[ $1 == "simple" ]]; then
    cd /var/www/app/public
    php81 -S 0.0.0.0:80
fi
