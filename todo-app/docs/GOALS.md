# GOALS — TODO App

## Problema
Una app web mínima per gestionar tasques. Les dades persiteixen
en un fitxer JSON al servidor. No necessita base de dades.

## Input
L'usuari escriu una tasca i prem "Afegir".

## Output
Llista de tasques persistent entre sessions, guardada a data/todos.json.

## Restriccions
- Backend: PHP pla (sense Laravel ni cap framework)
- Frontend: JS vanilla (sense React, sense npm)
- Infraestructura: un sol contenidor Docker amb PHP built-in server
- Sense base de dades

## Criteri d'èxit
1. Afegir una tasca → apareix a la llista
2. Marcar com a feta → queda ratllada
3. Eliminar → desapareix
4. Reiniciar el contenidor → les tasques continuen