# Plan: Agregar Spinner de Carga en booking.php

## Resumen

Agregar un spinner de carga centrado que se muestre durante el procesamiento de la solicitud de reserva. El código ya tiene un overlay semitransparente en `booking_http_client.js`, pero no incluye un spinner visible. Se modificará para incluir un spinner de Bootstrap centrado.

## Archivos a modificar

1. **assets/js/http/booking_http_client.js** - Modificar la función `registerAppointment()` para incluir spinner
2. **assets/css/frontend.scss** - Agregar estilos CSS para el spinner overlay

## Cambios detallados

### 1. assets/js/http/booking_http_client.js

En la función `registerAppointment()` (línea ~209), modificar el `beforeSend` para:
- Crear un contenedor overlay con clase identificable
- Incluir un spinner de Bootstrap (`spinner-border`) centrado dentro del overlay
- Agregar texto indicativo opcional (ej: "Processing...")

**Código actual (líneas 202-218):**
```javascript
const $layer = $('<div/>');

$.ajax({
    url: url,
    method: 'post',
    data: data,
    dataType: 'json',
    beforeSend: () => {
        $layer.appendTo('body').css({
            background: 'white',
            position: 'fixed',
            top: '0',
            left: '0',
            height: '100vh',
            width: '100vw',
            opacity: '0.5',
        });
    },
})
```

**Nuevo código propuesto:**
```javascript
const $layer = $('<div/>', {
    'class': 'booking-loading-overlay',
});

const $spinner = $('<div/>', {
    'class': 'booking-loading-spinner',
}).html(`
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-3 fw-semibold">${lang('processing')}</div>
`);

$.ajax({
    url: url,
    method: 'post',
    data: data,
    dataType: 'json',
    beforeSend: () => {
        $layer.appendTo('body');
        $spinner.appendTo('body');
    },
})
```

### 2. assets/css/frontend.scss

Agregar estilos para centrar el overlay y el spinner:

```scss
/* Booking loading overlay */
.booking-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 100vw;
    background: rgba(255, 255, 255, 0.8);
    z-index: 9999;
}

.booking-loading-spinner {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
```

### 3. Nota sobre traducción

El texto "Processing..." usará la función `lang('processing')`. Se debe verificar si esta clave de traducción existe. Si no existe, se puede:
- Usar un texto hardcodeado en inglés como fallback
- Agregar la clave de traducción al sistema (requeriría modificar archivos de idioma)

## Consideraciones

- Bootstrap ya incluye clases `spinner-border` y `spinner-grow` en los temas CSS existentes
- El `z-index` alto (9999/10000) asegura que el spinner esté por encima de todos los elementos
- El overlay semitransparente (0.8) permite ver el contenido de fondo pero indica claramente que la página está ocupada
- El spinner se elimina automáticamente en el callback `.always()` existente (línea 256-258)
- No se requieren cambios en booking.php ya que el spinner se genera dinámicamente via JavaScript

## Pasos de implementación

1. Editar `assets/js/http/booking_http_client.js`:
   - Modificar la creación de `$layer` para agregar clase CSS
   - Crear elemento `$spinner` con spinner de Bootstrap
   - Agregar `$spinner.appendTo('body')` en `beforeSend`
   - Agregar `$spinner.remove()` en el callback `.always()`

2. Editar `assets/css/frontend.scss`:
   - Agregar reglas CSS para `.booking-loading-overlay` y `.booking-loading-spinner`

3. Ejecutar compilación SCSS si es necesaria (verificar si hay script de build)
