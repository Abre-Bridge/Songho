FROM php:8.2-apache

RUN a2enmod rewrite

COPY index.html /var/www/html/index.html
COPY api/ /var/www/html/api/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -name "*.php" -exec chmod 644 {} \; \
    && find /var/www/html -type f -name "*.html" -exec chmod 644 {} \; \
    && find /var/www/html/api/data -type f -name "*.json" -exec chmod 664 {} \; \
    && chmod 775 /var/www/html/api/data

RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/project.conf \
    && a2enconf project

EXPOSE 80

CMD ["apache2-foreground"]
