# CREA IYEM
## Seeders
### Usuarios operativos (`LimpiarUsuariosSeeder`)

Este seeder crea (o conserva) al usuario administrador y al usuario tester del panel operativo. Sus contraseñas **no están en el código**: se leen de tu archivo `.env`.

- `SEED_ADMIN_PASSWORD`: contraseña del usuario administrador.
- `SEED_TESTER_PASSWORD`: contraseña del usuario tester.

Cómo ejecutarlo:
    1. Define ambas variables en tu `.env` (puedes ver el formato en `.env.example`).
    2. Ejecuta `php artisan db:seed --class=LimpiarUsuariosSeeder`.

Importante: Si alguna variable falta o está vacía, el seeder se detiene con un mensaje y no crea ningún usuario.