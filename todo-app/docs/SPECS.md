# SPEC — TODO App

## Estructura de fitxers
├── Dockerfile
├── public/
│   ├── index.html
│   └── app.js
├── api/
│   └── todos.php       ← API REST mínima
└── data/
    └── todos.json      ← persistència (creat automàticament)

## API — todos.php
Tots els endpoints al mateix fitxer, discriminats per $_SERVER['REQUEST_METHOD'].

GET    /api/todos.php         → retorna tots els todos
POST   /api/todos.php         → crea un todo nou
PATCH  /api/todos.php?id=X    → marca com a fet/no fet
DELETE /api/todos.php?id=X    → elimina

## Format todos.json
[
  {
    "id": "uuid-v4-simple",
    "text": "Comprar llet",
    "done": false,
    "created_at": "2026-05-11T10:00:00Z"
  }
]

## Frontend — app.js
- Fetch a l'API en carregar la pàgina
- Afegir: POST + re-render
- Marcar: PATCH + re-render
- Eliminar: DELETE + re-render
- Cap framework. DOM manipulat directament.

## Dockerfile
FROM php:8.2-cli
WORKDIR /app
COPY . .
RUN mkdir -p data && echo "[]" > data/todos.json
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

## Validació d'input
- Text buit → no crear todo
- ID inexistent en PATCH/DELETE → 404 JSON
- todos.json corrupte → reinicialitzar a []

## Comportament d'error
Tots els errors retornen JSON: {"error": "missatge"} amb el codi HTTP correcte.
Cap excepció no capturada visible a l'usuari.

## Fora d'abast
- Sense autenticació
- Sense categories ni prioritats
- Sense edició de text un cop creat