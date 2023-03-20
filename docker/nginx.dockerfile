FROM nginx:stable-alpine

ARG NGINXGROUP
ARG NGINXUSER

ENV NGINXGROUP=${NGINXGROUP}
ENV NGINXUSER=${NGINXUSER}

RUN sed -i "s/user www-data/user ${NGINXUSER}/g" /etc/nginx/nginx.conf

ADD ./nginx/default.conf /etc/nginx/conf.d/
ADD ./nginx/cert/adviser.crt /etc/ssl/
ADD ./nginx/cert/adviser.key /etc/ssl/
ADD ./nginx/cert/dhparam.pem /etc/nginx/
ADD ./nginx/cert/ssl-params.conf /etc/nginx/snippets/

RUN mkdir -p /var/www/html

RUN adduser -g ${NGINXGROUP} -s /bin/sh -D ${NGINXUSER}; exit 0
