# Plan: Requerir aprobación solo para usuarios de tipo "alumno"

## Problema

Actualmente, en `application/libraries/Accounts.php` (líneas 82-84), el método `check_login()` verifica `is_approved` para **todos** los usuarios:

```php
if (empty($user['is_approved'])) {
    throw new RuntimeException('Cuenta pendiente de aprobación.');
}
```

Esto impide que administradores y proveedores de servicios accedan al sistema si tienen `is_approved = 0`, lo cual no es el comportamiento deseado.

## Solución

Modificar la verificación de `is_approved` para que solo aplique a usuarios con rol `alumno`.

### Archivo a modificar

**`application/libraries/Accounts.php`** - Método `check_login()`

### Cambio específico

En las líneas 80-86, actualmente el código es:

```php
$user = $this->CI->users_model->find($user_settings['id_users']);

if (empty($user['is_approved'])) {
    throw new RuntimeException('Cuenta pendiente de aprobación.');
}

$role = $this->CI->roles_model->find($user['id_roles']);
```

Se debe reestructurar para obtener el rol **antes** de verificar la aprobación, y luego condicionar la verificación:

```php
$user = $this->CI->users_model->find($user_settings['id_users']);

$role = $this->CI->roles_model->find($user['id_roles']);

if ($role['slug'] === 'alumno' && empty($user['is_approved'])) {
    throw new RuntimeException('Cuenta pendiente de aprobación.');
}
```

### Explicación

1. Se mueve la consulta del rol (`$role`) **antes** de la verificación de `is_approved`
2. La verificación de `is_approved` ahora solo se ejecuta cuando `$role['slug'] === 'alumno'`
3. Los demás roles (admin, provider, secretary, customer) podrán acceder independientemente del valor de `is_approved`

## Impacto

- **Administradores**: Podrán acceder sin estar aprobados
- **Proveedores (providers)**: Podrán acceder sin estar aprobados
- **Secretarios**: Podrán acceder sin estar aprobados
- **Alumnos**: Seguirán requiriendo aprobación (`is_approved = 1`) para acceder
- **Clientes (customers)**: No se ven afectados (no acceden al backend)

## Riesgos

- Mínimo. El cambio es localizado en un solo archivo y una sola condición.
- El modelo `Roles_model` ya se carga en el constructor (línea 36), por lo que no hay dependencias adicionales.
