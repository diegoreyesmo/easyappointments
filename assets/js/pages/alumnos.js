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
 * Alumnos page.
 *
 * This module implements the functionality of the alumnos page.
 */
App.Pages.Alumnos = (function () {
    const $alumnos = $('#alumnos');
    const $id = $('#id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $mobileNumber = $('#mobile-number');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $notes = $('#notes');
    const $language = $('#language');
    const $timezone = $('#timezone');
    const $username = $('#username');
    const $password = $('#password');
    const $passwordConfirmation = $('#password-confirm');
    const $notifications = $('#notifications');
    const $isApproved = $('#is-approved');
    const $appointmentQuota = $('#appointment-quota');
    const $filterAlumnos = $('#filter-alumnos');
    let filterResults = {};
    let filterLimit = 20;

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        $alumnos.on('submit', '#filter-alumnos form', (event) => {
            event.preventDefault();
            const key = $('#filter-alumnos .key').val();
            $('.selected').removeClass('selected');
            App.Pages.Alumnos.resetForm();
            App.Pages.Alumnos.filter(key);
        });

        $alumnos.on('click', '.alumno-row', (event) => {
            if ($filterAlumnos.find('.filter').prop('disabled')) {
                $filterAlumnos.find('.results').css('color', '#AAA');
                return;
            }

            const alumnoId = $(event.currentTarget).attr('data-id');
            const alumno = filterResults.find((filterResult) => Number(filterResult.id) === Number(alumnoId));

            App.Pages.Alumnos.display(alumno);
            $filterAlumnos.find('.selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-alumno, #delete-alumno').prop('disabled', false);

            $('#alumnos-page').addClass('editing');
            $alumnos.find('.add-edit-delete-group').hide();
            $alumnos.find('.save-cancel-group').show();
            $alumnos.find('#delete-alumno').show();
            $filterAlumnos.find('button').prop('disabled', true);
            $filterAlumnos.find('.results').css('color', '#AAA');
            $alumnos.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $alumnos.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#alumno-services input:checkbox, #alumno-providers input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services, #select-all-providers, #select-none-providers').prop('disabled', false);
        });

        $alumnos.on('click', '#add-alumno', () => {
            App.Pages.Alumnos.resetForm();
            $('#alumnos-page').addClass('editing');
            $filterAlumnos.find('button').prop('disabled', true);
            $filterAlumnos.find('.results').css('color', '#AAA');
            $alumnos.find('.add-edit-delete-group').hide();
            $alumnos.find('.save-cancel-group').show();
            $alumnos.find('#delete-alumno').hide();
            $alumnos.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $alumnos.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').addClass('required');
            $('#alumno-services input:checkbox, #alumno-providers input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services, #select-all-providers, #select-none-providers').prop('disabled', false);
        });

        $alumnos.on('click', '#edit-alumno', () => {
            $('#alumnos-page').addClass('editing');
            $alumnos.find('.add-edit-delete-group').hide();
            $alumnos.find('.save-cancel-group').show();
            $filterAlumnos.find('button').prop('disabled', true);
            $filterAlumnos.find('.results').css('color', '#AAA');
            $alumnos.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $alumnos.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#alumno-services input:checkbox, #alumno-providers input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services, #select-all-providers, #select-none-providers').prop('disabled', false);
        });

        $alumnos.on('click', '#delete-alumno', () => {
            const alumnoId = $id.val();

            const buttons = [
                {
                    text: lang('cancel'),
                    click: (event, messageModal) => {
                        messageModal.hide();
                    },
                },
                {
                    text: lang('delete'),
                    click: (event, messageModal) => {
                        App.Pages.Alumnos.remove(alumnoId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(lang('delete_customer'), lang('delete_record_prompt'), buttons);
        });

        $alumnos.on('click', '#save-alumno', () => {
            const alumno = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                mobile_number: $mobileNumber.val(),
                phone_number: $phoneNumber.val(),
                address: $address.val(),
                city: $city.val(),
                state: $state.val(),
                zip_code: $zipCode.val(),
                notes: $notes.val(),
                language: $language.val(),
                timezone: $timezone.val(),
                is_approved: Number($isApproved.prop('checked')),
                settings: {
                    username: $username.val(),
                    notifications: Number($notifications.prop('checked')),
                    appointment_quota: $appointmentQuota.val() || 0,
                },
            };

            alumno.allowed_services = [];
            $('#alumno-services input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    alumno.allowed_services.push($(checkboxEl).attr('data-id'));
                }
            });

            alumno.allowed_providers = [];
            $('#alumno-providers input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    alumno.allowed_providers.push($(checkboxEl).attr('data-id'));
                }
            });

            if ($password.val() !== '') {
                alumno.settings.password = $password.val();
            }

            if ($id.val() !== '') {
                alumno.id = $id.val();
            }

            if (!App.Pages.Alumnos.validate()) {
                return;
            }

            App.Pages.Alumnos.save(alumno);
        });

        $alumnos.on('click', '#cancel-alumno', () => {
            const id = $('#filter-alumnos .selected').attr('data-id');
            App.Pages.Alumnos.resetForm();
            $('#alumnos-page').removeClass('editing');
            if (id) {
                App.Pages.Alumnos.select(id, true);
            }
        });

        $alumnos.on('click', '#select-all-services', () => {
            $('#alumno-services input:checkbox').prop('checked', true);
        });

        $alumnos.on('click', '#select-none-services', () => {
            $('#alumno-services input:checkbox').prop('checked', false);
        });

        $alumnos.on('click', '#select-all-providers', () => {
            $('#alumno-providers input:checkbox').prop('checked', true);
        });

        $alumnos.on('click', '#select-none-providers', () => {
            $('#alumno-providers input:checkbox').prop('checked', false);
        });
    }

    function save(alumno) {
        App.Http.Alumnos.save(alumno).then((response) => {
            App.Layouts.Backend.displayNotification(lang('customer_saved'));
            App.Pages.Alumnos.resetForm();
            $('#alumnos-page').removeClass('editing');
            $('#filter-alumnos .key').val('');
            App.Pages.Alumnos.filter('', response.id, true);
        });
    }

    function remove(id) {
        App.Http.Alumnos.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('customer_deleted'));
            App.Pages.Alumnos.resetForm();
            $('#alumnos-page').removeClass('editing');
            App.Pages.Alumnos.filter($('#filter-alumnos .key').val());
        });
    }

    function validate() {
        $alumnos.find('.is-invalid').removeClass('is-invalid');
        $alumnos.find('.form-message').removeClass('alert-danger').hide();

        try {
            let missingRequired = false;

            $alumnos.find('.required').each((index, requiredFieldEl) => {
                if (!$(requiredFieldEl).val()) {
                    $(requiredFieldEl).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            if ($password.val() !== $passwordConfirmation.val()) {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('passwords_mismatch'));
            }

            if ($password.val().length < vars('min_password_length') && $password.val() !== '') {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('password_length_notice').replace('$number', vars('min_password_length')));
            }

            if (!App.Utils.Validation.email($email.val())) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            const phoneNumber = $phoneNumber.val();
            if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
                $phoneNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            const mobileNumber = $mobileNumber.val();
            if (mobileNumber && !App.Utils.Validation.phone(mobileNumber)) {
                $mobileNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            if ($username.attr('already-exists') === 'true') {
                $username.addClass('is-invalid');
                throw new Error(lang('username_already_exists'));
            }

            return true;
        } catch (error) {
            $('#alumnos .form-message').addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    function resetForm() {
        $filterAlumnos.find('.selected').removeClass('selected');
        $filterAlumnos.find('button').prop('disabled', false);
        $filterAlumnos.find('.results').css('color', '');

        $alumnos.find('.add-edit-delete-group').show();
        $alumnos.find('.save-cancel-group').hide();
        $alumnos.find('.record-details h4 a').remove();
        $alumnos.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        $alumnos.find('.record-details .form-label span').prop('hidden', true);
        $alumnos.find('.record-details #language').val(vars('default_language'));
        $alumnos.find('.record-details #timezone').val(vars('default_timezone'));
        $alumnos.find('.record-details #is-approved').prop('checked', false);
        $alumnos.find('.record-details #appointment-quota').val('');
        $alumnos.find('.record-details #notifications').prop('checked', true);
        $alumnos.find('.record-details .is-invalid').removeClass('is-invalid');
        $alumnos.find('.record-details .form-message').hide();

        $('#edit-alumno, #delete-alumno').prop('disabled', true);
        $('#alumno-services input:checkbox, #alumno-providers input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-services, #select-none-services, #select-all-providers, #select-none-providers').prop('disabled', true);
        $('#alumno-services a, #alumno-providers a').remove();
    }

    function display(alumno) {
        $id.val(alumno.id);
        $firstName.val(alumno.first_name);
        $lastName.val(alumno.last_name);
        $email.val(alumno.email);
        $mobileNumber.val(alumno.mobile_number);
        $phoneNumber.val(alumno.phone_number);
        $address.val(alumno.address);
        $city.val(alumno.city);
        $state.val(alumno.state);
        $zipCode.val(alumno.zip_code);
        $notes.val(alumno.notes);
        $language.val(alumno.language);
        $timezone.val(alumno.timezone);

        $username.val(alumno.settings.username);
        $notifications.prop('checked', Boolean(Number(alumno.settings.notifications)));
        $isApproved.prop('checked', Boolean(Number(alumno.is_approved)));
        $appointmentQuota.val(alumno.settings.appointment_quota || '');

        $('#alumno-services a, #alumno-providers a').remove();
        $('#alumno-services input:checkbox, #alumno-providers input:checkbox').prop('checked', false);

        if (alumno.allowed_services) {
            alumno.allowed_services.forEach((serviceId) => {
                const $checkbox = $('#alumno-services input[data-id="' + serviceId + '"]');
                if ($checkbox.length) {
                    $checkbox.prop('checked', true);
                }
            });
        }

        if (alumno.allowed_providers) {
            alumno.allowed_providers.forEach((providerId) => {
                const $checkbox = $('#alumno-providers input[data-id="' + providerId + '"]');
                if ($checkbox.length) {
                    $checkbox.prop('checked', true);
                }
            });
        }
    }

    function filter(keyword, selectId = null, show = false) {
        App.Http.Alumnos.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterAlumnos.find('.results').empty();
            response.forEach((alumno) => {
                $('#filter-alumnos .results').append(App.Pages.Alumnos.getFilterHtml(alumno)).append($('<hr/>'));
            });

            if (!response.length) {
                $filterAlumnos.find('.results').append(
                    $('<em/>', {
                        'text': lang('no_records_found'),
                    }),
                );
            } else if (response.length === filterLimit) {
                $('<button/>', {
                    'type': 'button',
                    'class': 'btn btn-outline-secondary w-100 load-more text-center',
                    'text': lang('load_more'),
                    'click': () => {
                        filterLimit += 20;
                        App.Pages.Alumnos.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-alumnos .results');
            }

            if (selectId) {
                App.Pages.Alumnos.select(selectId, show);
            }
        });
    }

    function getFilterHtml(alumno) {
        const name = alumno.first_name + ' ' + alumno.last_name;
        let info = alumno.email;
        info = alumno.mobile_number ? info + ', ' + alumno.mobile_number : info;
        info = alumno.phone_number ? info + ', ' + alumno.phone_number : info;

        return $('<div/>', {
            'class': 'alumno-row entry',
            'data-id': alumno.id,
            'html': [
                $('<strong/>', {
                    'text': name,
                }),
                $('<br/>'),
                $('<small/>', {
                    'class': 'text-muted',
                    'text': info,
                }),
                $('<br/>'),
            ],
        });
    }

    function select(id, show = false) {
        $filterAlumnos.find('.alumno-row[data-id="' + id + '"]').addClass('selected');

        if (show) {
            const alumno = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));
            App.Pages.Alumnos.display(alumno);
            $('#edit-alumno, #delete-alumno').prop('disabled', false);
        }
    }

    function initialize() {
        App.Pages.Alumnos.resetForm();
        App.Pages.Alumnos.filter('');
        App.Pages.Alumnos.addEventListeners();

        vars('services').forEach((service) => {
            const checkboxId = `alumno-service-${service.id}`;
            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'id': checkboxId,
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': service.id,
                                'prop': {
                                    'disabled': true,
                                },
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': service.name,
                                'for': checkboxId,
                            }),
                        ],
                    }),
                ],
            }).appendTo('#alumno-services');
        });

        vars('providers').forEach((provider) => {
            const checkboxId = `alumno-provider-${provider.id}`;
            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'id': checkboxId,
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': provider.id,
                                'prop': {
                                    'disabled': true,
                                },
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': provider.first_name + ' ' + provider.last_name,
                                'for': checkboxId,
                            }),
                        ],
                    }),
                ],
            }).appendTo('#alumno-providers');
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        validate,
        getFilterHtml,
        resetForm,
        display,
        select,
        addEventListeners,
    };
})();
