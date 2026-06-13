FROM php:8.2-apache

RUN a2enmod rewrite

COPY index.html /var/www/html/index.html
COPY api/ /var/www/html/api/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/api/data \
    && chmod 664 /var/www/html/api/data/j.json

RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/project.conf \
    && a2enconf project

EXPOSE 80

CMD ["apache2-foreground"]
