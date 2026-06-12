# Plan: Incluir dirección de la empresa en correos de reserva y cancelación

## Objetivo
Permitir al administrador configurar una dirección de la empresa en la configuración general y mostrarla en el pie de página de los correos electrónicos de confirmación de reserva y cancelación de citas.

## Archivos a modificar

### 1. Vista de Configuración General
- **Archivo**: `application/views/pages/general_settings.php`
- **Acción**: Agregar un nuevo campo de entrada de texto debajo del campo "Enlace de la empresa" (`company_link`).
- **Detalles**: 
  - `id="company-address"`
  - `data-field="company_address"`
  - Etiqueta: `<?= lang('company_address') ?>`
  - Texto de ayuda: `<?= lang('company_address_hint') ?>`
  - *Nota*: El script `general_settings.js` ya maneja dinámicamente cualquier campo con el atributo `data-field`, por lo que no se requieren cambios en el JavaScript.

### 2. Archivos de Idioma (Inglés)
- **Archivo**: `application/language/english/translations_lang.php`
- **Acción**: Agregar las siguientes líneas después de las definiciones de `company_link` (aprox. línea 167):
  ```php
  $lang['company_address'] = 'Company Address';
  $lang['company_address_hint'] = 'This address will be displayed in appointment confirmation and cancellation emails.';
  ```

### 3. Archivos de Idioma (Español)
- **Archivo**: `application/language/spanish/translations_lang.php`
- **Acción**: Agregar las siguientes líneas después de las definiciones de `company_link` (aprox. línea 167):
  ```php
  $lang['company_address'] = 'Dirección de la empresa';
  $lang['company_address_hint'] = 'Esta dirección se mostrará en los correos de confirmación y cancelación de citas.';
  ```

### 4. Plantilla de Correo: Cita Guardada
- **Archivo**: `application/views/emails/appointment_saved_email.php`
- **Acción**: Modificar la sección `<!-- START FOOTER -->` (aprox. línea 640) para incluir la dirección de la empresa debajo del nombre/enlace de la empresa.
- **Detalles**:
  ```php
  <?php if (!empty($settings['company_address'])): ?>
      <tr>
          <td class="content-block powered-by" style="padding-top: 5px;">
              <?= e($settings['company_address']) ?>
          </td>
      </tr>
  <?php endif; ?>
  ```

### 5. Plantilla de Correo: Cita Cancelada
- **Archivo**: `application/views/emails/appointment_deleted_email.php`
- **Acción**: Modificar la sección `<!-- START FOOTER -->` (aprox. línea 626) de la misma manera que en la plantilla de cita guardada, para mantener la consistencia.
- **Detalles**:
  ```php
  <?php if (!empty($settings['company_address'])): ?>
      <tr>
          <td class="content-block powered-by" style="padding-top: 5px;">
              <?= e($settings['company_address']) ?>
          </td>
      </tr>
  <?php endif; ?>
  ```

## Validación
1. Verificar que el campo "Dirección de la empresa" aparezca y se guarde correctamente en la página de Configuración General.
2. Realizar una reserva de prueba y verificar que el correo de "Cita guardada" muestre la dirección en el pie de página.
3. Cancelar la cita de prueba y verificar que el correo de "Cita cancelada" también muestre la dirección.
4. Probar con el campo vacío para asegurar que no se rompe el diseño ni se muestra un espacio en blanco innecesario.

## Consideraciones
- El helper `e()` se utilizará para escapar la salida y prevenir vulnerabilidades XSS.
- No se requieren cambios en el modelo `Settings_model.php` ni en el controlador, ya que el sistema de configuración de AgendaRRF guarda dinámicamente cualquier par clave-valor enviado desde el frontend con el atributo `data-field`.