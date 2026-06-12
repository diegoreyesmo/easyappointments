/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Register Alumno page.
 *
 * This module implements the functionality of the alumno registration page.
 */
App.Pages.RegisterAlumno = (function () {
    const $form = $('#register-alumno-form');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $phoneNumber = $('#phone-number');
    const $username = $('#username');
    const $password = $('#password');
    const $passwordConfirmation = $('#password-confirm');
    const $alert = $('.alert');

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        $form.on('submit', (event) => {
            event.preventDefault();

            if (!validate()) {
                return;
            }

            const alumno = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                phone_number: $phoneNumber.val(),
                username: $username.val(),
                password: $password.val(),
            };

            if (vars('require_captcha')) {
                if (vars('altcha_enabled') === '1') {
                    alumno.altcha_payload = $('#altcha-payload').val();
                } else {
                    alumno.captcha = $('#captcha-text').val();
                }
            }

            App.Http.RegisterAlumno.store(alumno).then((response) => {
                if (response.success) {
                    showAlert('success', response.message || 'Registro exitoso. Tu cuenta está pendiente de aprobación.');
                    $form[0].reset();
                } else {
                    showAlert('danger', response.message || 'Error al registrar.');
                }
            }).catch((error) => {
                showAlert('danger', error.message || 'Error al registrar.');
            });
        });

        $username.on('blur', () => {
            checkUsername();
        });

        $('.captcha-title .btn').on('click', () => {
            $('.captcha-image').attr('src', App.Utils.Url.siteUrl('captcha') + '?t=' + new Date().getTime());
        });
    }

    /**
     * Validate the form.
     *
     * @return {Boolean}
     */
    function validate() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $alert.removeClass('alert-danger alert-success').addClass('d-none');

        try {
            if (!$firstName.val()) {
                $firstName.addClass('is-invalid');
                throw new Error('El nombre es obligatorio.');
            }

            if (!$lastName.val()) {
                $lastName.addClass('is-invalid');
                throw new Error('El apellido es obligatorio.');
            }

            if (!$email.val() || !App.Utils.Validation.email($email.val())) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            if ($phoneNumber.val() && !App.Utils.Validation.phone($phoneNumber.val())) {
                $phoneNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            if (!$username.val()) {
                $username.addClass('is-invalid');
                throw new Error('El nombre de usuario es obligatorio.');
            }

            if ($username.attr('already-exists') === 'true') {
                $username.addClass('is-invalid');
                throw new Error(lang('username_already_exists'));
            }

            if (!$password.val() || $password.val().length < vars('min_password_length')) {
                $password.addClass('is-invalid');
                $passwordConfirmation.addClass('is-invalid');
                throw new Error(lang('password_length_notice').replace('$number', vars('min_password_length')));
            }

            if ($password.val() !== $passwordConfirmation.val()) {
                $password.addClass('is-invalid');
                $passwordConfirmation.addClass('is-invalid');
                throw new Error(lang('passwords_mismatch'));
            }

            return true;
        } catch (error) {
            showAlert('danger', error.message);
            return false;
        }
    }

    /**
     * Check if username exists.
     */
    function checkUsername() {
        const username = $username.val();

        if (!username) {
            return;
        }

        $.post(App.Utils.Url.siteUrl('account/check_username'), {
            csrf_token: vars('csrf_token'),
            username: username,
        }).then((response) => {
            if (!response.is_available) {
                $username.attr('already-exists', 'true');
            } else {
                $username.removeAttr('already-exists');
            }
        });
    }

    /**
     * Show alert message.
     *
     * @param {String} type
     * @param {String} message
     */
    function showAlert(type, message) {
        $alert.removeClass('alert-danger alert-success d-none').addClass('alert-' + type).text(message).show();
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        App.Pages.RegisterAlumno.addEventListeners();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        addEventListeners,
    };
})();
