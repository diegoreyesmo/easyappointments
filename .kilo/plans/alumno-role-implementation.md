# Plan: Implementación del Rol "Alumno" en EasyAppointments

## Resumen
Este plan detalla los cambios necesarios para introducir un nuevo tipo de usuario llamado "Alumno" con reglas de negocio específicas: autenticación, sistema de cuotas de reservas, asociación restringida a servicios/profesionales, flujo de registro con aprobación manual y vista restringida en el panel.

## Decisiones de Diseño Confirmadas
1. **Límite de Cuotas**: Se contará el **total histórico** de citas reservadas por el alumno (sin distinguir entre activas, completadas o canceladas).
2. **Gestión de Asociaciones**: Se integrará **directamente en el formulario de edición de usuario existente**, mostrando campos condicionales solo cuando el rol del usuario sea "alumno".
3. **Registro Público**: Se creará un **formulario de registro público independiente** (ruta dedicada) para que los alumnos se registren por sí mismos.

---

## 1. Cambios en la Base de Datos (Migraciones)

### 1.1. Nuevo Rol "Alumno"
- Insertar un nuevo registro en la tabla `ea_roles` con `slug = 'alumno'`, `is_admin = 0`, y permisos restringidos (ej. `appointments = 1` para ver, `users = 0`, `services = 0`, etc.).

### 1.2. Modificación de la tabla `ea_users`
- Añadir columna `is_approved` (TINYINT, DEFAULT 0) para controlar el estado de aprobación de la cuenta.

### 1.3. Modificación de la tabla `ea_user_settings`
- Añadir columna `appointment_quota` (INT, DEFAULT 0 o NULL) para almacenar el límite máximo de citas permitidas para el alumno.

### 1.4. Nuevas Tablas de Asociación Restringida
- Crear tabla `ea_alumnos_services`:
  - `id_users` (INT, FK a `ea_users.id`)
  - `id_services` (INT, FK a `ea_services.id`)
  - Primary Key: (`id_users`, `id_services`)
- Crear tabla `ea_alumnos_providers`:
  - `id_users` (INT, FK a `ea_users.id`)
  - `id_users_provider` (INT, FK a `ea_users.id`)
  - Primary Key: (`id_users`, `id_users_provider`)

---

## 2. Cambios en el Backend (Modelos y Librerías)

### 2.1. `Accounts.php` (Librería)
- Modificar el método `check_login` para verificar el campo `is_approved`. Si es `0`, devolver un error específico (ej. "Cuenta pendiente de aprobación") en lugar de permitir el inicio de sesión.

### 2.2. `Users_model.php`
- Actualizar los métodos `save`, `validate`, `find` y `get` para manejar el nuevo campo `is_approved`.
- Añadir métodos auxiliares:
  - `is_approved($user_id)`: Devuelve booleano.
  - `get_appointment_quota($user_id)`: Devuelve el límite de citas.
  - `get_total_appointments_count($user_id)`: Cuenta el **total histórico** de citas reservadas por el usuario (sin filtrar por estado).

### 2.3. `Appointments_model.php`
- En el método `validate` o `save`, añadir lógica para usuarios con rol "alumno":
  - Verificar que el total histórico de citas no exceda la `appointment_quota` (comparando con `get_total_appointments_count`).
  - Verificar que el `id_services` esté en la tabla `ea_alumnos_services` para ese usuario.
  - Verificar que el `id_users_provider` esté en la tabla `ea_alumnos_providers` para ese usuario.
  - Lanzar `InvalidArgumentException` con mensaje claro si alguna restricción falla.

### 2.4. Nuevos Modelos (Opcional pero recomendado)
- `Alumnos_model.php`: Para encapsular la lógica de obtención de servicios/proveedores permitidos y gestión de aprobaciones.

---

## 3. Cambios en el Backend (Controladores)

### 3.1. `Booking.php`
- Modificar los endpoints que devuelven listas de servicios y proveedores disponibles para el formulario de reserva.
- Si el usuario está autenticado como "alumno", filtrar las listas usando las tablas `ea_alumnos_services` y `ea_alumnos_providers`.

### 3.2. `Calendar.php`
- Modificar la consulta de obtención de citas para el rol "alumno".
- Forzar la cláusula `WHERE id_users_customer = [user_id]` (o el campo equivalente que identifique al alumno en la cita) para que solo vea sus propias citas.

### 3.3. Extensión de `Users.php` / `Admins.php`
- Modificar los endpoints existentes de gestión de usuarios para soportar la aprobación y configuración de alumnos:
  - Listar usuarios con rol "alumno" y `is_approved = 0`.
  - Aprobar/Rechazar cuentas de alumnos (actualizar `is_approved = 1`).
  - Guardar/Actualizar el `appointment_quota` y las asociaciones (servicios/proveedores) directamente desde el formulario de edición de usuario existente.

---

## 4. Cambios en el Frontend (Vistas y JavaScript)

*Nota arquitectónica:* El sistema no posee una vista genérica de "usuarios" (`user_form.php`), sino vistas dedicadas por rol (`admins.php`, `providers.php`, `secretaries.php`, `customers.php`). Por consistencia, se creará una vista dedicada para la gestión de alumnos.

### 4.1. Vista de Gestión de Alumnos (Nueva)
- Crear `application/views/pages/alumnos.php` siguiendo el patrón de `providers.php` o `secretaries.php`.
- Incluir campos condicionales en el formulario de detalles:
  - Checkbox "Cuenta Aprobada" (`is_approved`).
  - Input numérico "Límite de Citas (Cuota)" (`appointment_quota`).
  - Selector múltiple (Select2) para "Servicios Permitidos" (poblado desde la API de servicios).
  - Selector múltiple (Select2) para "Profesionales Permitidos" (poblado desde la API de proveedores).
- Crear `assets/js/pages/alumnos.js` para la lógica de UI (filtrado, validación, guardado, manejo de selectores múltiples).
- Crear `assets/js/http/alumnos_http_client.js` para las llamadas a la API (asumiendo endpoint `/api/v1/alumnos` o extensión del endpoint de usuarios existente).

### 4.2. Actualización del Menú de Navegación
- Modificar `application/views/components/backend_header.php`.
- Añadir un enlace "Alumnos" en el menú desplegable de "Usuarios" (junto a Proveedores, Secretarios y Administradores), protegido por la verificación de permisos adecuada (ej. `can('view', PRIV_USERS)`).

### 4.3. Registro Público de Alumno
- Crear `application/views/pages/register_alumno.php` con un formulario público que solicite: nombre, apellidos, email, teléfono, username y contraseña.
- Añadir un enlace visible en `application/views/pages/login.php` (ej. "¿Eres alumno? Regístrate aquí") que apunte a esta nueva ruta (`booking/register_alumno`).
- Añadir el método correspondiente en el controlador `Booking.php` (o un controlador dedicado) para procesar el registro, estableciendo automáticamente `id_roles` al ID del rol "alumno" y `is_approved = 0`.

### 4.4. Vista del Calendario del Alumno
- Asegurar que la interfaz del calendario (`calendar.php`) no muestre opciones de administración global cuando el rol sea "alumno".
- El cliente HTTP (`appointments_http_client.js`) ya filtra correctamente si el backend lo restringe, pero se puede añadir validación adicional en el frontend para ocultar botones de acción no permitidos (ej. crear citas de otros usuarios).

### 4.5. Actualización de Archivos de Idioma
- Añadir las nuevas cadenas de texto necesarias en `application/language/spanish/translations_lang.php` (y otros idiomas si aplica):
  - `alumnos`
  - `account_approved` (Cuenta Aprobada)
  - `appointment_quota` (Límite de Citas)
  - `allowed_services` (Servicios Permitidos)
  - `allowed_providers` (Profesionales Permitidos)
  - `register_as_student` (Registrarse como alumno)
  - `pending_approval_message` (Tu cuenta está pendiente de aprobación por un administrador).

---

## 5. Pruebas y Validación

1. **Registro y Aprobación**:
   - Registrar un nuevo alumno -> Verificar que `is_approved = 0` en la BD.
   - Intentar iniciar sesión -> Debe fallar con mensaje de "pendiente de aprobación".
   - Aprobar desde el panel de admin -> Intentar iniciar sesión -> Debe ser exitoso.
2. **Restricción de Servicios/Profesionales**:
   - Como alumno, intentar reservar un servicio no asignado -> Debe fallar o no mostrarse en la lista.
   - Asignar un servicio/proveedor desde el panel de admin -> Verificar que ahora aparece disponible para el alumno.
3. **Sistema de Cuotas**:
   - Establecer cuota = 1.
   - Reservar 1 cita -> Exitoso.
   - Intentar reservar una 2ª cita -> Debe fallar con mensaje de "Límite de citas alcanzado".
4. **Vista Restringida**:
   - Iniciar sesión como alumno -> Verificar que el calendario solo muestra sus propias citas.

---

## 7. Próximos Pasos Inmediatos (Implementación Frontend)

1. **Crear archivos base**: 
   - `application/views/pages/alumnos.php`
   - `assets/js/pages/alumnos.js`
   - `assets/js/http/alumnos_http_client.js`
2. **Actualizar menú**: Modificar `backend_header.php` para incluir el enlace a "Alumnos".
3. **Crear registro público**: 
   - `application/views/pages/register_alumno.php`
   - Añadir enlace en `login.php`.
   - Añadir ruta y método en `Booking.php` para procesar el registro.
4. **Actualizar idiomas**: Añadir las cadenas de texto necesarias en `translations_lang.php`.
5. **Verificar endpoint API**: Confirmar que el endpoint `/api/v1/alumnos` (o el equivalente) existe y acepta los campos `is_approved`, `appointment_quota`, `allowed_services` y `allowed_providers`. Si no existe, crear `Alumnos_api_v1.php` siguiendo el patrón de `Providers_api_v1.php`.

---

## 8. Consideraciones de Seguridad
- Validar en el backend **siempre** las restricciones de cuota y asociación, nunca confiar solo en el filtrado del frontend.
- Asegurar que los endpoints de administración para aprobar alumnos y asignar servicios verifiquen que el usuario que realiza la acción tenga rol de Administrador o Secretario.
- Sanitizar todas las entradas en los nuevos formularios de registro y administración.
