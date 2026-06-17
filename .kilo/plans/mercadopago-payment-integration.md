# Plan: Integración de MercadoPago Chile en el flujo de reserva

## Resumen

Agregar un paso de pago con MercadoPago Chile como último paso antes de confirmar la cita, con la posibilidad de activar/desactivar el cobro desde la configuración del administrador.

---

## Arquitectura actual relevante

- **Framework**: CodeIgniter 3.x (PHP 8.2+)
- **Base de datos**: MySQL (tabla `settings` como key-value store)
- **Flujo de reserva**: Wizard de 4 pasos (Servicio → Fecha/Hora → Datos cliente → Confirmación)
- **Servicios**: Ya tienen campos `price` (DECIMAL) y `currency` (VARCHAR) pero son solo informativos
- **No existe infraestructura de pagos**: Ningún gateway, tabla de transacciones, ni flujo de checkout

---

## Cambios propuestos

### 1. Migración de base de datos

**Archivo**: `application/migrations/074_add_mercadopago_payment_support.php`

#### Tabla `payment_transactions`
| Columna | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK |
| `id_appointments` | BIGINT UNSIGNED FK | FK → appointments.id |
| `id_users_customer` | BIGINT UNSIGNED FK | FK → users.id |
| `payment_method` | VARCHAR(50) | 'mercadopago' |
| `preference_id` | VARCHAR(255) | ID de preferencia de MP |
| `payment_id` | VARCHAR(255) | ID de pago de MP |
| `status` | VARCHAR(50) | 'pending', 'approved', 'rejected', 'cancelled', 'refunded' |
| `amount` | DECIMAL(10,2) | Monto cobrado |
| `currency` | VARCHAR(10) | 'CLP' |
| `mp_response` | TEXT | Respuesta JSON de MP |
| `create_datetime` | DATETIME | |
| `update_datetime` | DATETIME | |

#### Columnas nuevas en `appointments`
| Columna | Tipo | Descripción |
|---|---|---|
| `payment_required` | TINYINT(1) | Si requiere pago (denormalizado del setting al momento de reservar) |
| `payment_status` | VARCHAR(50) | 'none', 'pending', 'approved', 'rejected' |

#### Índices
- Índice en `payment_transactions.id_appointments`
- Índice en `payment_transactions.payment_id`
- Índice en `payment_transactions.status`

---

### 2. Settings de MercadoPago

**Nuevas entradas en tabla `settings`** (insertadas por la migración):

| Setting Name | Tipo | Default | Descripción |
|---|---|---|---|
| `mercadopago_enabled` | 0/1 | 0 | Activar/desactivar cobro |
| `mercadopago_access_token` | string | '' | Access Token (producción o sandbox) |
| `mercadopago_public_key` | string | '' | Public Key (para el frontend) |
| `mercadopago_client_id` | string | '' | Client ID para OAuth |
| `mercadopago_client_secret` | string | '' | Client Secret para OAuth |
| `mercadopago_sandbox` | 0/1 | 1 | Modo sandbox por defecto |
| `mercadopago_currency` | string | 'CLP' | Moneda (CLP para Chile) |
| `mercadopago_statement_descriptor` | string | '' | Descriptor en el estado de cuenta |

---

### 3. Librería MercadoPago

**Archivo**: `application/libraries/Mercadopago_client.php`

Responsabilidades:
- Crear preferencia de pago (`create_preference`)
- Consultar estado de pago (`get_payment_info`)
- Verificar webhook/IPN (`verify_webhook`)
- Manejar la integración con el SDK oficial de MercadoPago PHP

```php
class Mercadopago_client {
    private $access_token;
    private $sdk_initialized;
    
    public function initialize($access_token)
    public function create_preference($title, $amount, $currency, $payer_email, $payer_name, $external_reference, $success_url, $failure_url, $pending_url)
    public function get_payment_info($payment_id)
    public function verify_webhook($data, $headers)
}
```

**Dependencia**: Agregar `mercadopago/dx-php` via Composer (SDK oficial)

---

### 4. Controlador de pagos

**Archivo**: `application/controllers/Payments.php`

Endpoints:
- `POST payments/create_preference` - Crea preferencia de pago y retorna `preference_id` y `init_point` (URL de checkout)
- `GET payments/success` - Callback de retorno exitoso (redirige a booking_confirmation)
- `GET payments/failure` - Callback de retorno fallido (redirige a booking con error)
- `GET payments/pending` - Callback de pago pendiente
- `POST payments/webhook` - Webhook de MercadoPago para notificaciones asíncronas
- `GET payments/status/<appointment_hash>` - Verifica estado de pago de una cita

Lógica del flujo:
1. Cliente llega al paso de pago → se llama `create_preference`
2. Se crea la cita con `payment_status = 'pending'` (no se envían notificaciones aún)
3. Se redirige al cliente al `init_point` de MercadoPago
4. Al volver (success/failure), se consulta el estado y se actualiza
5. Si `approved` → se cambian a `payment_status = 'approved'`, se envían notificaciones, se sincroniza calendario
6. Webhook actualiza estado de forma asíncrona como respaldo

---

### 5. Modificación del flujo de booking

#### 5.1 Controller `Booking.php`

Modificaciones en `register()`:
- Si `mercadopago_enabled = 1` Y el servicio tiene `price > 0`:
  1. Crear cita con `payment_status = 'pending'`
  2. NO enviar notificaciones ni sincronizar calendario aún
  3. Retornar `appointment_hash` + flag `requires_payment = true`
  4. El frontend redirige al paso de pago
- Si no requiere pago: comportamiento actual

#### 5.2 Vista `booking.php`

Agregar condicionalmente el componente de pago:
```php
<?php if (setting('mercadopago_enabled')): ?>
    <?php component('booking_payment_step'); ?>
<?php endif; ?>
```

Nuevo orden de pasos:
1. Select Service & Provider
2. Pick Date & Time
3. Customer Information
4. **Payment** (nuevo, condicional)
5. Confirmation

#### 5.3 Nuevo componente `booking_payment_step.php`

**Archivo**: `application/views/components/booking_payment_step.php`

Contenido:
- Resumen del monto a pagar (precio del servicio)
- Botón "Pagar con MercadoPago"
- Logo de MercadoPago
- Mensaje de seguridad
- Estado del pago (spinner, éxito, error)

#### 5.4 Modificación `booking_final_step.php`

- Si el pago es requerido y ya fue aprobado: mostrar badge "Pago confirmado"
- Si el pago es requerido y está pendiente: no mostrar botón de confirmar (redirigir a pago)
- Si no requiere pago: comportamiento actual

---

### 6. JavaScript

#### 6.1 `booking.js`

Modificaciones:
- Agregar validación del paso de pago antes de ir a confirmación
- Manejar la navegación al paso de pago
- Manejar redirección a MercadoPago
- Manejar retorno desde MercadoPago (detectar parámetros URL `payment_id`, `status`, `external_reference`)
- Actualizar `updateConfirmFrame()` para mostrar info de pago

Nuevo método:
```javascript
function initiatePayment() {
    // Llama a payments/create_preference
    // Redirige a MercadoPago checkout
}

function checkPaymentStatus(appointmentHash) {
    // Poll payments/status/<hash> hasta approved/rejected
}
```

#### 6.2 `booking_http_client.js`

Nuevos métodos:
```javascript
function createPaymentPreference(appointmentData) {
    // POST payments/create_preference
}

function getPaymentStatus(appointmentHash) {
    // GET payments/status/<hash>
}
```

#### 6.3 Nuevo archivo `assets/js/pages/booking_payment.js`

Manejo específico del paso de pago:
- Renderizar botón de MercadoPago
- Manejar click → crear preferencia → redirigir
- Manejar retorno → verificar estado → continuar o mostrar error
- Polling para webhook asíncrono

---

### 7. Admin: Configuración de MercadoPago

#### 7.1 Nueva vista `mercadopago_settings.php`

**Archivo**: `application/views/pages/mercadopago_settings.php`

UI con:
- Toggle "Habilitar cobro con MercadoPago"
- Campo "Access Token" (password field con toggle show/hide)
- Campo "Public Key"
- Campo "Client ID"
- Campo "Client Secret"
- Toggle "Modo Sandbox"
- Select "Moneda" (CLP pre-seleccionado)
- Campo "Descriptor en estado de cuenta"
- URL del webhook (generada automáticamente, copiable)
- Botón de prueba de conexión
- Instrucciones de configuración

#### 7.2 Nuevo controller `Mercadopago_settings.php`

**Archivo**: `application/controllers/Mercadopago_settings.php`

- `index()`: Renderiza la página de settings
- `save()`: Guarda los settings (valida credenciales)
- `test_connection()`: Endpoint AJAX para probar credenciales

Agregar al whitelist de settings permitidos en el controller.

#### 7.3 Integración en el menú de settings

Agregar enlace a "MercadoPago" en:
- `application/views/components/settings_nav.php` (navegación lateral de settings)
- O alternativamente, en `application/views/pages/integrations.php` como una tarjeta de integración

---

### 8. Modificación del modelo Appointments

**Archivo**: `application/models/Appointments_model.php`

- Agregar `payment_required` y `payment_status` a `allowed_appointment_fields`
- Método `update_payment_status($appointment_id, $status)`

---

### 9. Nuevo modelo Payments

**Archivo**: `application/models/Payments_model.php`

CRUD completo para la tabla `payment_transactions`:
- `save($transaction)`
- `find_by_appointment_id($appointment_id)`
- `find_by_payment_id($payment_id)`
- `find_by_external_reference($external_reference)`
- `get_transactions_by_customer($customer_id)`

---

### 10. Notificaciones

**Archivo**: `application/libraries/Notifications.php`

Modificación en `notify_appointment_saved()`:
- Si la cita requiere pago y NO está aprobada: NO enviar notificación de confirmación
- Enviar notificación de "pago pendiente" al cliente
- Cuando el pago se aprueba (vía webhook o callback): enviar notificación de confirmación completa

Nuevo método:
```php
public function notify_payment_approved($appointment, $service, $provider, $customer, $settings)
```

---

### 11. Language files

**Archivo**: `application/language/spanish/translations_lang.php`

Agregar traducciones:
```php
$lang['mercadopago_settings'] = 'Configuración de MercadoPago';
$lang['mercadopago_enabled'] = 'Habilitar MercadoPago';
$lang['mercadopago_access_token'] = 'Access Token';
$lang['mercadopago_public_key'] = 'Public Key';
$lang['mercadopago_sandbox'] = 'Modo Sandbox';
$lang['mercadopago_currency'] = 'Moneda';
$lang['payment_required'] = 'Pago requerido';
$lang['pay_with_mercadopago'] = 'Pagar con MercadoPago';
$lang['payment_amount'] = 'Monto a pagar';
$lang['payment_pending'] = 'Pago pendiente';
$lang['payment_approved'] = 'Pago confirmado';
$lang['payment_rejected'] = 'Pago rechazado';
$lang['payment_processing'] = 'Procesando pago...';
$lang['payment_success_message'] = 'Tu pago fue procesado exitosamente.';
$lang['payment_failure_message'] = 'El pago no pudo ser procesado. Intente nuevamente.';
$lang['payment_webhook_url'] = 'URL del Webhook';
$lang['test_connection'] = 'Probar conexión';
$lang['payment_step'] = 'Pago';
$lang['complete_payment_to_confirm'] = 'Complete el pago para confirmar su cita';
```

---

### 12. Composer

**Archivo**: `composer.json`

Agregar dependencia:
```json
"mercadopago/dx-php": "^3.0"
```

---

## Orden de implementación sugerido

1. **Migración de BD** (tablas y columnas nuevas)
2. **Composer**: agregar SDK de MercadoPago
3. **Librería** `Mercadopago_client.php`
4. **Modelo** `Payments_model.php`
5. **Controller** `Payments.php` (endpoints)
6. **Modelo** `Appointments_model.php` (campos de pago)
7. **Controller** `Booking.php` (modificar `register()`)
8. **Vista** `booking_payment_step.php` (componente nuevo)
9. **Vista** `booking_final_step.php` (modificar)
10. **Vista** `booking.php` (agregar componente condicional)
11. **JavaScript** `booking.js` + `booking_http_client.js` + `booking_payment.js`
12. **Controller** `Mercadopago_settings.php`
13. **Vista** `mercadopago_settings.php`
14. **Nav** `settings_nav.php` (agregar enlace)
15. **Library** `Notifications.php` (modificar)
16. **Language files** (agregar traducciones)
17. **Testing**: flujo completo sandbox

---

## Flujo completo del usuario

```
1. Cliente selecciona servicio con precio → Paso 1
2. Cliente selecciona fecha/hora → Paso 2
3. Cliente ingresa datos → Paso 3
4. ┌ Si mercadopago_enabled = 1 Y precio > 0:
   │   → Se crea cita con status 'pending'
   │   → Paso 4: Pago (botón MercadoPago)
   │   → Click → crea preferencia → redirige a MP
   │   → Cliente paga en MP → vuelve a success
   │   → Se verifica pago → status 'approved'
   │   → Se envían notificaciones
   │   → Paso 5: Confirmación (con badge "Pago confirmado")
   │
   └ Si no:
       → Paso 4: Confirmación (comportamiento actual)
```

---

## Consideraciones de seguridad

- CSRF token en todos los endpoints de pago
- Validar webhook con signature de MercadoPago
- No exponer Access Token en el frontend
- Sanitizar todos los inputs del webhook
- Rate limiting en endpoints de pago
- Log de todas las transacciones

---

## Consideraciones de UX

- Mostrar claramente el monto antes de redirigir a MP
- Indicador de carga durante la creación de preferencia
- Manejar gracefully el caso de "back button" desde MP
- Polling para detectar pago aprobado vía webhook
- Mensaje claro si el pago falla con opción de reintentar
- En email de confirmación, incluir estado del pago

---

## Archivos a crear

| Archivo | Tipo |
|---|---|
| `application/migrations/074_add_mercadopago_payment_support.php` | Migración |
| `application/libraries/Mercadopago_client.php` | Librería |
| `application/models/Payments_model.php` | Modelo |
| `application/controllers/Payments.php` | Controller |
| `application/controllers/Mercadopago_settings.php` | Controller |
| `application/views/components/booking_payment_step.php` | Vista componente |
| `application/views/pages/mercadopago_settings.php` | Vista admin |
| `assets/js/pages/booking_payment.js` | JavaScript |

## Archivos a modificar

| Archivo | Cambio |
|---|---|
| `composer.json` | Agregar SDK MP |
| `application/models/Appointments_model.php` | Campos payment |
| `application/controllers/Booking.php` | Lógica de pago en register() |
| `application/views/pages/booking.php` | Agregar componente pago |
| `application/views/components/booking_final_step.php` | Badge de pago |
| `application/views/components/settings_nav.php` | Enlace a settings MP |
| `assets/js/pages/booking.js` | Paso de pago |
| `assets/js/http/booking_http_client.js` | Métodos de pago |
| `application/libraries/Notifications.php` | Notificaciones de pago |
| `application/language/spanish/translations_lang.php` | Traducciones |
| `application/language/english/translations_lang.php` | Traducciones |
