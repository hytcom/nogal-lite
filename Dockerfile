FROM hytcom/php74-apache

# Copia del framework
RUN mkdir -p /usr/share/nogal
WORKDIR /usr/share/nogal
COPY . .
WORKDIR /var/www