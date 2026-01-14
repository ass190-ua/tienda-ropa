# 1. Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# 2. Instalamos las herramientas necesarias (Git, Unzip, Node.js para Vue, Drivers de Postgres)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    nodejs \
    npm

# 3. Instalamos los drivers de PHP para conectar con la base de datos PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# 4. Habilitamos "mod_rewrite" de Apache (vital para las rutas de Laravel)
RUN a2enmod rewrite

# 5. Copiamos Composer (el gestor de paquetes de PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Configuramos la carpeta de trabajo
WORKDIR /var/www/html

# 7. Copiamos todos tus archivos al contenedor
COPY . .

# 8. Configuramos Apache para que la carpeta pública sea el inicio (DocumentRoot)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 9. Instalamos dependencias de PHP (Backend)
RUN composer install --optimize-autoloader

# 10. Instalamos dependencias de JS y construimos el Frontend (Vue)
RUN npm install && npm run build

# 11. Damos permisos a las carpetas de almacenamiento (para subir fotos y logs)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 12. Exponemos el puerto 80 (el estándar web)
EXPOSE 80

# Ejecutamos las migraciones y luego arrancamos Apache
CMD php artisan migrate:fresh --seed --force && apache2-foreground
