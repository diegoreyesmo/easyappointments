# Plan: Habilitar Agendamiento de Citas para Rol Alumno

## Contexto

Actualmente, cuando un alumno intenta crear una cita desde el calendario backend, el botón "Appointment" no funciona y se produce un error JS:

```
Cannot read properties of undefined (reading 'setDate')
```

**Causa raíz**: El rol alumno tiene `appointments=1` (solo vista, sin permiso `add`). El botón `#insert-appointment` no se renderiza en el DOM (`calendar.php:51` lo oculta con `can('add', PRIV_APPOINTMENTS)`). Al hacer `$('#insert-appointment').trigger('click')`, el handler nunca se ejecuta, `resetModal()` nunca inicializa Flatpickr, y `_flatpickr` es `undefined`.

Adicionalmente, al crear una cita como administrativo, solo se pueden seleccionar clientes (role `customer`), pero no alumnos (role `alumno`). Se requiere que ambos roles aparezcan en la lista de selección, filtrando alumnos por el servicio y proveedor seleccionados.

---

## Cambio 1: Migración — Permisos del rol alumno

**Archivo nuevo**: `application/migrations/073_update_alumno_appointments_permission.php`

Actualizar el permiso `appointments` del rol alumno de `1` (view) a `3` (view + add = `PRIV_VIEW | PRIV_ADD`).

```php
$this->db->where('slug', 'alumno')->update('roles', ['appointments' => 3]);
```

---

## Cambio 2: Calendar.php `index()` — Filtrar servicios/proveedores para alumno

**Archivo**: `application/controllers/Calendar.php`, método `index()` (~línea 158-190)

Después de calcular `$available_providers` y `$available_services`, agregar lógica para filtrar ambos arrays cuando el rol es `alumno`, usando `Users_model::get_allowed_services()` y `get_allowed_providers()` — exactamente igual que ya se hace en `Booking.php:167-189`.

```php
if ($role_slug === 'alumno') {
    $this->load->model('users_model');
    
    $allowed_services = $this->users_model->get_allowed_services($user_id);
    if (!empty($allowed_services)) {
        $available_services = array_values(array_filter($available_services, function ($s) use ($allowed_services) {
            return in_array($s['id'], $allowed_services, true);
        }));
        $available_providers = array_values(array_filter($available_providers, function ($p) use ($allowed_services) {
            return !empty(array_intersect($p['services'], $allowed_services));
        }));
    }
    
    $allowed_providers = $this->users_model->get_allowed_providers($user_id);
    if (!empty($allowed_providers)) {
        $available_providers = array_values(array_filter($available_providers, function ($p) use ($allowed_providers) {
            return in_array($p['id'], $allowed_providers, true);
        }));
    }
}
```

---

## Cambio 3: Calendar.php `index()` — Pre-cargar datos del alumno como customer

**Archivo**: `application/controllers/Calendar.php`, método `index()` (~línea 196)

Cuando el rol es alumno, en lugar de cargar los primeros 50 clientes, cargar solo el propio alumno como customer para auto-rellenar el formulario:

```php
if ($role_slug === 'alumno') {
    $customers = [$user]; // El propio alumno es el "customer" de su cita
} else {
    $customers = $this->customers_model->get(null, 50, null, 'update_datetime DESC');
    // ... existing limit_customer_access logic ...
}
```

---

## Cambio 4: Calendar.php `save_appointment()` — Forzar customer para alumno

**Archivo**: `application/controllers/Calendar.php`, método `save_appointment()` (~línea 284-311)

Después de validar permisos, si el rol es alumno:
1. Forzar `id_users_customer` al `user_id` del alumno (no puede crear citas para otros)
2. Saltar la lógica de guardar customer (el alumno ya existe en la BD)
3. Validar que el servicio y proveedor estén permitidos para este alumno

```php
$role_slug = session('role_slug');

if ($role_slug === 'alumno') {
    $user_id = session('user_id');
    $appointment_data['id_users_customer'] = $user_id;
    $customer_data = null; // Skip customer save — alumno already exists
    
    // Validate allowed services/providers for this alumno
    $this->load->model('users_model');
    $allowed_services = $this->users_model->get_allowed_services($user_id);
    if (!empty($allowed_services) && !in_array($appointment_data['id_services'], $allowed_services, true)) {
        throw new RuntimeException('El servicio seleccionado no está permitido para tu cuenta.');
    }
    $allowed_providers = $this->users_model->get_allowed_providers($user_id);
    if (!empty($allowed_providers) && !in_array($appointment_data['id_users_provider'], $allowed_providers, true)) {
        throw new RuntimeException('El profesional seleccionado no está permitido para tu cuenta.');
    }
}
```

---

## Cambio 5: Calendar.php `check_event_permissions()` — Permitir alumno

**Archivo**: `application/controllers/Calendar.php`, método `check_event_permissions()` (~línea 432-447)

El alumno no es provider ni secretary, así que las comprobaciones existentes no aplican. Pero hay que verificar que el proveedor seleccionado esté en la lista de proveedores permitidos del alumno:

```php
if ($role_slug === 'alumno') {
    $this->load->model('users_model');
    $allowed_providers = $this->users_model->get_allowed_providers($user_id);
    if (!empty($allowed_providers) && !in_array($provider_id, $allowed_providers, true)) {
        abort(403);
    }
    return;
}
```

---

## Cambio 6: Backend — Nuevo endpoint para buscar clientes + alumnos

**Archivo**: `application/controllers/Calendar.php`

Agregar método `search_patients()` que busca tanto customers como alumnos, con filtro opcional por servicio y proveedor:

```php
public function search_patients(): void
{
    method('post');
    if (cannot('view', PRIV_APPOINTMENTS)) { abort(403); }
    
    $keyword = request('keyword', '');
    $limit = request('limit', 50);
    $id_services = request('id_services');
    $id_users_provider = request('id_users_provider');
    
    // Search customers
    $customers = $this->customers_model->search($keyword, $limit);
    foreach ($customers as &$c) { $c['role_type'] = 'customer'; }
    
    // Search alumnos
    $alumnos = $this->users_model->search($keyword, $limit);
    $filtered_alumnos = [];
    foreach ($alumnos as $alumno) {
        $role = $this->roles_model->find($alumno['id_roles']);
        if ($role['slug'] !== 'alumno') continue;
        
        // Filter by allowed service
        if ($id_services) {
            $allowed_services = $this->users_model->get_allowed_services($alumno['id']);
            if (!empty($allowed_services) && !in_array($id_services, $allowed_services, true)) continue;
        }
        // Filter by allowed provider
        if ($id_users_provider) {
            $allowed_providers = $this->users_model->get_allowed_providers($alumno['id']);
            if (!empty($allowed_providers) && !in_array($id_users_provider, $allowed_providers, true)) continue;
        }
        
        $alumno['role_type'] = 'alumno';
        $filtered_alumnos[] = $alumno;
    }
    
    json_response(array_merge($customers, $filtered_alumnos));
}
```

Registrar la ruta en `application/config/routes.php` si es necesario (Calendar ya es un controller accesible).

---

## Cambio 7: Calendar.php `index()` — Pasar alumnos al frontend

**Archivo**: `application/controllers/Calendar.php`, método `index()` (~línea 213)

Para roles admin/secretary, también pasar una lista inicial de alumnos (los más recientes) en la variable `alumnos` para poblar la lista inicial de selección:

```php
if ($role_slug === 'alumno') {
    $alumnos = [];
} else {
    // Load initial alumnos list (top 50)
    $alumnos_raw = $this->users_model->search('', 50, 0, 'update_datetime DESC');
    $alumnos = [];
    foreach ($alumnos_raw as $a) {
        $role = $this->roles_model->find($a['id_roles']);
        if ($role['slug'] === 'alumno') {
            $a['allowed_services'] = $this->users_model->get_allowed_services($a['id']);
            $a['allowed_providers'] = $this->users_model->get_allowed_providers($a['id']);
            $a['role_type'] = 'alumno';
            $alumnos[] = $a;
        }
    }
}

// In script_vars, add:
'alumnos' => $alumnos,
```

---

## Cambio 8: Frontend — appointments_modal.js integrar alumnos en selección de customer

**Archivo**: `assets/js/components/appointments_modal.js`

### 8a. Agregar `alumnos` al pool de selección (línea ~300)

En el handler `$selectCustomer.on('click')`, incluir también los alumnos de `vars('alumnos')`:

```javascript
// After the existing customers forEach:
vars('alumnos').forEach((alumno) => {
    $('<div/>', {
        'data-id': alumno.id,
        'data-type': 'alumno',
        'text': (alumno.first_name || '[No First Name]') + ' ' + (alumno.last_name || '[No Last Name]') + ' (Alumno)',
    }).appendTo($existingCustomersList);
});
```

### 8b. Modificar búsqueda por texto (línea ~353-416)

En el handler `$filterExistingCustomers.on('keyup')`, además de buscar customers, buscar alumnos con el nuevo endpoint:

```javascript
// Replace App.Http.Customers.search with a call to the new search_patients endpoint
// that includes service_id and provider_id for alumno filtering
const serviceId = $selectService.val();
const providerId = $selectProvider.val();

App.Http.Calendar.searchPatients(keyword, 50, serviceId, providerId)
    .done((response) => {
        $existingCustomersList.empty();
        response.forEach((patient) => {
            const label = (patient.first_name || '[No First Name]') + ' ' + (patient.last_name || '[No Last Name]') 
                + (patient.role_type === 'alumno' ? ' (Alumno)' : '');
            $('<div/>', {
                'data-id': patient.id,
                'data-type': patient.role_type || 'customer',
                'text': label,
            }).appendTo($existingCustomersList);
            
            // Merge into the appropriate global array
            const targetArray = patient.role_type === 'alumno' ? vars('alumnos') : vars('customers');
            const exists = targetArray.find((e) => Number(e.id) === Number(patient.id));
            if (!exists) targetArray.push(patient);
        });
    });
```

### 8c. Modificar selección de customer (línea ~319-344)

Al hacer click en un item de la lista, buscar en ambos arrays (`vars('customers')` y `vars('alumnos')`):

```javascript
$appointmentsModal.on('click', '#existing-customers-list div', (event) => {
    const id = $(event.target).attr('data-id');
    const type = $(event.target).attr('data-type');
    
    const pool = type === 'alumno' ? vars('alumnos') : vars('customers');
    const person = pool.find((p) => Number(p.id) === Number(id));
    
    if (person) {
        $customerId.val(person.id);
        $firstName.val(person.first_name);
        $lastName.val(person.last_name);
        $email.val(person.email);
        $phoneNumber.val(person.phone_number);
        $address.val(person.address);
        $city.val(person.city);
        $zipCode.val(person.zip_code);
        $language.val(person.language);
        $timezone.val(person.timezone);
        $customerNotes.val(person.notes);
        // custom_fields only for customers
        if (type !== 'alumno') {
            $customField1.val(person.custom_field_1);
            // ... etc
        }
    }
    $selectCustomer.trigger('click');
});
```

### 8d. Filtrar alumnos al cambiar servicio/proveedor (línea ~424-474)

En el handler de cambio de servicio (`$selectService.on('change')`) y proveedor, actualizar la lista visible de alumnos si está abierta. Esto se maneja automáticamente por el endpoint `search_patients` que recibe `id_services` y `id_users_provider`.

---

## Cambio 9: Frontend — calendar_http_client.js agregar searchPatients

**Archivo**: `assets/js/http/calendar_http_client.js`

Agregar función para llamar al nuevo endpoint:

```javascript
function searchPatients(keyword, limit, idServices, idUsersProvider) {
    const url = App.Utils.Url.siteUrl('calendar/search_patients');
    const data = {
        csrf_token: vars('csrf_token'),
        keyword,
        limit,
        id_services: idServices || undefined,
        id_users_provider: idUsersProvider || undefined,
    };
    return $.post(url, data);
}
```

Exportar en el módulo.

---

## Cambio 10: Frontend — Auto-rellenar customer para alumno

**Archivo**: `assets/js/components/appointments_modal.js`, en `$insertAppointment.on('click')` (~línea 233)

Si el rol es `alumno`, auto-rellenar los campos del customer con los datos del propio alumno (de `vars('customers')` que ahora contiene solo su propio registro):

```javascript
// After resetModal() call, if role is alumno:
if (vars('role_slug') === 'alumno') {
    const alumnoData = vars('customers')[0]; // Only contains self
    if (alumnoData) {
        $customerId.val(alumnoData.id);
        $firstName.val(alumnoData.first_name);
        $lastName.val(alumnoData.last_name);
        $email.val(alumnoData.email);
        $phoneNumber.val(alumnoData.phone_number);
        $address.val(alumnoData.address);
        $city.val(alumnoData.city);
        $zipCode.val(alumnoData.zip_code);
        $language.val(alumnoData.language || vars('default_language'));
        $timezone.val(alumnoData.timezone || vars('default_timezone'));
        $customerNotes.val(alumnoData.notes);
    }
    // Hide the customer selection button (alumno is always the customer)
    $selectCustomer.hide();
    $newCustomer.hide();
}
```

También en el handler `onSelect` de `calendar_default_view.js`, después de `$('#insert-appointment').trigger('click')` y `preselectServiceAndProvider()`, el flujo ahora funciona porque el botón existe en el DOM.

---

## Cambio 11: JS — calendar_default_view.js — Ocultar "Unavailability" para alumno

**Archivo**: `assets/js/utils/calendar_default_view.js`, en `onSelect()` (~línea 738)

El alumno no debería poder crear unavailability periods. Filtrar los botones del message dialog:

```javascript
function onSelect(info) {
    if (info.allDay) return;

    const buttons = [];

    if (vars('role_slug') !== 'alumno') {
        buttons.push({
            text: lang('unavailability'),
            click: (event, messageModal) => { /* existing code */ },
        });
    }

    buttons.push({
        text: lang('appointment'),
        click: (event, messageModal) => { /* existing code */ },
    });

    App.Utils.Message.show(lang('add_new_event'), lang('what_kind_of_event'), buttons);
    // ...
}
```

---

## Cambio 12: JS — calendar_default_view.js — Solo botón "Cita" para alumno

Relacionado con Cambio 11. Si solo hay un botón, ajustar el CSS del footer del message modal para no aplicar el layout de 50/50.

---

## Archivos a modificar (resumen)

| # | Archivo | Tipo de cambio |
|---|---------|---------------|
| 1 | `application/migrations/073_update_alumno_appointments_permission.php` | **Nuevo** — migración |
| 2 | `application/controllers/Calendar.php` | Modificar `index()`, `save_appointment()`, `check_event_permissions()`, agregar `search_patients()` |
| 3 | `assets/js/components/appointments_modal.js` | Integrar alumnos en selección, auto-rellenar para alumno |
| 4 | `assets/js/http/calendar_http_client.js` | Agregar `searchPatients()` |
| 5 | `assets/js/utils/calendar_default_view.js` | Filtrar botones en `onSelect()` para alumno |

---

## Orden de implementación

1. Migración (Cambio 1)
2. Backend Calendar.php (Cambios 2, 3, 4, 5, 6, 7)
3. Frontend HTTP client (Cambio 9)
4. Frontend appointments_modal.js (Cambios 8 y 10)
5. Frontend calendar_default_view.js (Cambios 11-12)
6. Ejecutar migración y rebuild de JS (minificar)

---

## Validación

1. Login como alumno → calendario debe mostrar solo citas propias, servicios/proveedores filtrados
2. Seleccionar slot → solo aparece botón "Cita" (no "Unavailability")
3. Click "Cita" → modal abre sin errores, customer auto-rellenado con datos del alumno
4. Seleccionar servicio/proveedor permitido → guardar cita exitosamente
5. Intentar guardar con servicio/proveedor no permitido → error de validación
6. Login como admin → crear cita → buscar alumno → debe aparecer en la lista (marcado como "Alumno")
7. Alumnos filtrados por servicio/proveedor seleccionado
8. Guardar cita con alumno como customer → exitoso
9. Verificar que `validate_alumno_appointment()` se ejecuta al guardar (quota, servicios, proveedores)
