Docker lleuger amb Nginx + PHP-FPM en un sol contenidor.

Configuracio actual:
- es construeix un sol contenidor a partir de `php:8.2-fpm-alpine` i s'hi afegeix `nginx`
- el codi viu directament a `sources/`
- nginx serveix els fitxers estatics i deriva les peticions `.php` a `php-fpm` dins del mateix contenidor
- el directori `sources/` es munta en lectura-escriptura per facilitar el desenvolupament
- la URL del model es configura amb `LLM_API_URL` i per defecte apunta a `host.docker.internal`
- el servei s'exposa al port `8080` de l'host

Arrencada:
- `docker compose up`

Aplicació:
- `http://localhost:8080`

Worker B02:
- el frontend de `B02` nomes encola jobs; el processament real el fa `sources/B02/worker.php`
- el worker s'ha d'executar en un altre terminal i quedar-se viu mentre hi hagi jobs pendents

Executar el worker dins Docker:
- `docker compose exec ia-curs sh -lc 'cd /var/www/html/B02 && php worker.php'`

Crear un job de prova:
- `curl -X POST http://localhost:8080/B02/analyse.php -H 'Content-Type: application/json' -d '{"feedback":"Molt bona organitzacio, pero el so era fluix i les cues massa llargues."}'`

Consultar B02 al navegador:
- `http://localhost:8080/B02/`

Important:
- tal com esta ara, `sources/B02/worker.php` fa la crida a l'LLM contra `http://localhost:1234`
- si el worker s'executa dins del contenidor, aquest `localhost` apunta al contenidor i no al Mac host
- per tant, perque funcioni dins Docker cal adaptar `worker.php` per reutilitzar `LLM_API_URL` o canviar aquesta URL a `http://host.docker.internal:1234/v1/chat/completions`
- alternativa rapida: executar el worker fora de Docker amb `cd sources/B02 && php worker.php`