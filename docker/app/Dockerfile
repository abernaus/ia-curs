FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx \
    && mkdir -p /run/nginx

CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]