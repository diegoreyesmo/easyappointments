# Plan: Permitir acceso al sitio público de reservas para usuarios autenticados

## Contexto

Actualmente, el setting `disable_booking` bloquea el sitio público de reservas para **todos** los visitantes, sin distinción. El objetivo es que cuando `disable_booking` esté activo, los usuarios anónimos vean el mensaje de "reservas deshabilitadas", pero los usuarios autenticados (cualquier rol con sesión activa) puedan seguir realizando y cancelando reservas normalmente.

## Estrategia

Modificar la condición de verificación en todos los puntos donde se evalúa `disable_booking` para que también compruebe si el usuario tiene una sesión activa (`session('user_id')`). La lógica cambia de:

```php
if ($disable_booking) {
    abort(403); // o mostrar mensaje
}
```

a:

```php
if ($disable_booking && !session('user_id')) {
    abort(403); // o mostrar mensaje
}
```

## Cambios

### 1. `application/controllers/Booking.php`

**5 ubicaciones** donde se verifica `disable_booking`:

| Método | Línea | Cambio |
|--------|-------|--------|
| `index()` | 138 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |
| `register()` | 408 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |
| `get_available_hours()` | 760 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |
| `get_unavailable_dates()` | 850 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |
| `store_alumno()` | 982 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |

### 2. `application/controllers/Booking_cancellation.php`

**1 ubicación**:

| Método | Línea | Cambio |
|--------|-------|--------|
| `of()` | 54 | `if ($disable_booking)` → `if ($disable_booking && !session('user_id'))` |

## Verificación

1. Desactivar el sitio público (`disable_booking = 1`) desde Booking Settings.
2. Acceder a `/` sin sesión → debe mostrar el mensaje de "reservas deshabilitadas".
3. Iniciar sesión con cualquier rol y acceder a `/` → debe mostrar el wizard de reservas normal.
4. Realizar una reserva como usuario autenticado → debe completarse exitosamente.
5. Cancelar una cita como usuario autenticado → debe completarse exitosamente.
6. Verificar que los endpoints AJAX (`get_available_hours`, `get_unavailable_dates`) funcionen para usuarios autenticados.
7. Cerrar sesión y verificar que los endpoints AJAX retornen 403.
