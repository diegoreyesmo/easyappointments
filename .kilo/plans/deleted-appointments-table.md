# Plan: Tabla de Citas Eliminadas para Notificaciones por Email

## Resumen

Crear una tabla `deleted_appointments` que almacene una copia de las citas eliminadas, permitiendo que el worker de emails acceda a los datos históricos para generar notificaciones de cancelación sin depender de la existencia de la cita en la tabla principal.

## Problema

Cuando se elimina una cita:
1. La cita se borra de `ea_appointments`
2. Se agrega un trabajo a `ea_email_queue` con el `appointment_id`
3. El worker intenta recuperar la cita con `$this->appointments_model->find($appointment_id)`
4. La cita ya no existe, causando el error: "The provided appointment ID was not found in the database: 5"

## Solución

Crear una tabla de archivado que preserve los datos de las citas eliminadas para uso en notificaciones y auditoría.

## Pasos de Implementación

### 1. Crear Migración para Tabla `deleted_appointments`

**Archivo:** `application/migrations/071_create_deleted_appointments_table.php`

La tabla debe contener:
- Todos los campos de `ea_appointments` (para preservar el estado al momento de la eliminación)
- Campo adicional `deleted_at` (DATETIME) - cuándo fue eliminada
- Campo adicional `deleted_by` (INT, nullable) - quién eliminó la cita (si está disponible)
- Campo adicional `cancellation_reason` (TEXT, nullable) - motivo de cancelación
- Índice en `deleted_at` para consultas de auditoría
- Índice en `appointment_id` para búsquedas rápidas

Estructura completa:
```sql
CREATE TABLE `ea_deleted_appointments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `book_datetime` DATETIME,
  `start_datetime` DATETIME,
  `end_datetime` DATETIME,
  `notes` TEXT,
  `hash` VARCHAR(12),
  `is_unavailability` TINYINT(4) DEFAULT 0,
  `id_users_provider` BIGINT UNSIGNED,
  `id_users_customer` BIGINT UNSIGNED,
  `id_services` BIGINT UNSIGNED,
  `id_google_calendar` TEXT,
  `id_caldav_calendar` TEXT,
  `location` TEXT,
  `meeting_link` TEXT,
  `color` VARCHAR(255),
  `status` VARCHAR(512),
  `update_datetime` DATETIME,
  `create_datetime` DATETIME,
  `deleted_at` DATETIME NOT NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `cancellation_reason` TEXT,
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Crear Modelo `Deleted_appointments_model`

**Archivo:** `application/models/Deleted_appointments_model.php`

Métodos necesarios:
- `archive(array $appointment, string $cancellation_reason = ''): void` - Guarda una copia de la cita eliminada
- `find(int $appointment_id): array` - Busca una cita archivada por su ID original
- `cleanup(int $days_retention): void` - Elimina registros antiguos (para GDPR/limpieza)

### 3. Modificar `Appointments_model::delete()`

**Archivo:** `application/models/Appointments_model.php`

Antes de eliminar la cita:
1. Obtener los datos completos de la cita
2. Llamar al modelo de citas eliminadas para archivar
3. Proceder con la eliminación

```php
public function delete(int $appointment_id): void
{
    // Obtener la cita antes de eliminar
    $appointment = $this->find($appointment_id);
    
    // Archivar en deleted_appointments
    $this->load->model('deleted_appointments_model');
    $this->deleted_appointments_model->archive($appointment);
    
    // Eliminar de la tabla principal
    $this->db->delete('appointments', ['id' => $appointment_id]);
}
```

### 4. Modificar `Console.php` (Email Worker)

**Archivo:** `application/controllers/Console.php`

En el método `process()`, modificar la lógica para manejar notificaciones de citas eliminadas:

```php
$appointment = $this->appointments_model->find($appointment_id);

// Si no existe en appointments, buscar en deleted_appointments
if (!$appointment) {
    $this->load->model('deleted_appointments_model');
    $appointment = $this->deleted_appointments_model->find($appointment_id);
    
    if (!$appointment) {
        $this->email_queue_model->mark_as_failed($job_id, "Appointment not found in active or deleted tables: {$appointment_id}");
        continue;
    }
}
```

### 5. Modificar `Email_messages::send_appointment_deleted()`

**Archivo:** `application/libraries/Email_messages.php`

Asegurar que el método pueda trabajar con datos de citas eliminadas (debería funcionar sin cambios, ya que recibe un array con los datos).

### 6. Actualizar `Notifications::notify_appointment_deleted()`

**Archivo:** `application/libraries/Notifications.php`

No requiere cambios mayores, ya que recibe los datos de la cita como parámetro. El cambio en Console.php asegurará que los datos estén disponibles.

### 7. Crear Configuración de Retención (Opcional)

**Archivo:** `application/migrations/072_add_deleted_appointments_retention_setting.php`

Agregar setting `deleted_appointments_retention_days` (default: 90 días) para limpieza automática.

### 8. Actualizar el Método de Cleanup

**Archivo:** `application/libraries/Cleanup.php`

Agregar limpieza de citas eliminadas antiguas basada en la configuración de retención.

## Consideraciones

### Rendimiento
- La tabla `deleted_appointments` crecerá con el tiempo
- Implementar índices adecuados en `appointment_id` y `deleted_at`
- Configurar limpieza automática después de 90 días (configurable)

### Privacidad (GDPR)
- Las citas eliminadas contienen datos personales
- La retención debe ser configurable
- Considerar anonimización después del período de retención

### Compatibilidad
- Los controladores existentes (`Booking_cancellation`, `Calendar`, `Appointments_api_v1`) ya obtienen los datos antes de eliminar, por lo que seguirán funcionando
- El cambio es transparente para el código existente

### Rollback
- La migración debe incluir método `down()` para eliminar la tabla
- Los datos archivados no se pueden recuperar automáticamente si se revierte

## Archivos a Modificar/Crear

### Crear:
1. `application/migrations/071_create_deleted_appointments_table.php`
2. `application/models/Deleted_appointments_model.php`
3. `application/migrations/072_add_deleted_appointments_retention_setting.php` (opcional)

### Modificar:
1. `application/models/Appointments_model.php` - Método `delete()`
2. `application/controllers/Console.php` - Método `process()`

### No Requieren Cambios (ya funcionan correctamente):
- `application/libraries/Notifications.php`
- `application/libraries/Email_messages.php`
- `application/controllers/Booking_cancellation.php`
- `application/controllers/Calendar.php`
- `application/controllers/api/v1/Appointments_api_v1.php`

## Testing

Después de la implementación:
1. Crear una cita de prueba
2. Cancelar la cita desde el frontend
3. Verificar que la cita aparece en `ea_deleted_appointments`
4. Ejecutar manualmente: `php index.php console process`
5. Verificar que el email se envía correctamente
6. Verificar que no hay errores en `ea_email_queue.error_message`

## Alternativas Consideradas

1. **Almacenar datos completos en email_queue**: Rechazado porque duplicaría datos y haría la tabla muy grande
2. **Notificar antes de eliminar**: Rechazado porque podría enviar notificaciones para citas que fallan al eliminarse
3. **Usar soft delete**: Rechazado porque complicaría las consultas existentes y afectaría el rendimiento
