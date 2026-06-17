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
 * Booking Payment page.
 *
 * Handles the MercadoPago payment step in the booking wizard.
 */
App.Pages.BookingPayment = (function () {
    const $payButton = $('#pay-with-mercadopago');
    const $paymentStatus = $('#payment-status');
    const $nextButton = $('#button-next-4');
    const $backButton = $('#button-back-4');

    let pollingInterval = null;
    let pollingTimeout = null;

    function initialize() {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentError = urlParams.get('payment_error');
        const paymentPending = urlParams.get('payment_pending');
        const paymentId = urlParams.get('payment_id');
        const status = urlParams.get('status');

        if (paymentError || status === 'failure') {
            showPaymentError();
        } else if (paymentPending || status === 'pending') {
            showPaymentPending();
            startPolling(window.AppointmentHash);
        } else if (paymentId && status === 'approved') {
            showPaymentApproved();
        }

        $payButton.on('click', initiatePayment);
        $backButton.on('click', handleBack);
    }

    function initiatePayment() {
        const firstName = $('#first-name').val() || '';
        const lastName = $('#last-name').val() || '';
        const email = $('#email').val() || '';

        if (!email) {
            App.Utils.Message.show(
                lang('error'),
                lang('email_required_for_payment'),
                [{ text: lang('close'), click: (e, m) => m.hide() }],
            );
            return;
        }

        $payButton.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>' + lang('payment_processing'),
        );

        App.Http.Booking.createPaymentPreference({
            appointment_hash: window.AppointmentHash,
            amount: window.PaymentAmount,
            currency: window.PaymentCurrency,
            payer_email: email,
            payer_name: firstName + ' ' + lastName,
        })
            .done((response) => {
                if (response.init_point) {
                    window.location.href = response.init_point;
                } else {
                    showPaymentError();
                }
            })
            .fail(() => {
                showPaymentError();
            })
            .always(() => {
                $payButton.prop('disabled', false).html(
                    '<img src="https://img.mp.akamai.com.ar/static/imgs/checkout/checkout-blue-lg.png" alt="MercadoPago" style="max-height: 40px;" onerror="this.style.display=\'none\'; this.parentElement.innerHTML=\'' + lang('pay_with_mercadopago') + '\';">',
                );
            });
    }

    function startPolling(appointmentHash) {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        if (pollingTimeout) {
            clearTimeout(pollingTimeout);
        }

        pollingInterval = setInterval(() => {
            App.Http.Booking.getPaymentStatus(appointmentHash)
                .done((response) => {
                    if (response.payment_status === 'approved') {
                        stopPolling();
                        showPaymentApproved();
                        setTimeout(() => {
                            window.location.href = App.Utils.Url.siteUrl('booking/reschedule/' + appointmentHash);
                        }, 1500);
                    } else if (response.payment_status === 'rejected') {
                        stopPolling();
                        showPaymentError();
                    }
                })
                .fail(() => {});
        }, 3000);

        pollingTimeout = setTimeout(() => {
            stopPolling();
        }, 30000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        if (pollingTimeout) {
            clearTimeout(pollingTimeout);
            pollingTimeout = null;
        }
    }

    function showPaymentProcessing() {
        $paymentStatus.show().html(
            '<div class="text-center">' +
            '<div class="spinner-border text-primary mb-2"></div>' +
            '<p class="text-muted mb-0">' + lang('payment_processing') + '</p>' +
            '</div>',
        );
    }

    function showPaymentApproved() {
        $paymentStatus.show().html(
            '<div class="alert alert-success text-center">' +
            '<i class="fas fa-check-circle fa-2x mb-2"></i>' +
            '<p class="mb-0 fw-bold">' + lang('payment_approved') + '</p>' +
            '<small class="text-muted">' + lang('payment_success_message') + '</small>' +
            '</div>',
        );
        $payButton.hide();
        $nextButton.show();
    }

    function showPaymentError() {
        $paymentStatus.show().html(
            '<div class="alert alert-danger text-center">' +
            '<i class="fas fa-times-circle fa-2x mb-2"></i>' +
            '<p class="mb-0 fw-bold">' + lang('payment_rejected') + '</p>' +
            '<small class="text-muted">' + lang('payment_failure_message') + '</small>' +
            '</div>',
        );
        $payButton.show();
        $nextButton.hide();
    }

    function showPaymentPending() {
        showPaymentProcessing();
        $payButton.hide();
        $nextButton.hide();
    }

    function handleBack() {
        stopPolling();
    }

    $(document).ready(initialize);

    return {
        initiatePayment,
        startPolling,
        stopPolling,
        showPaymentApproved,
        showPaymentError,
    };
})();
