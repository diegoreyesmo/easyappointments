# comando para ejecutar migraciones

php index.php console migrate

# comando para procesar cola de correos (agregar a cronjob)

cd /home/user && /usr/bin/docker compose exec <nombre-servicio> php index.php console process