<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * AgendaRRF - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Payments controller.
 *
 * Handles MercadoPago payment integration.
 *
 * @package Controllers
 */
class Payments extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('payments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        $this->load->library('mercadopago_client');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('webhooks_client');
    }

    /**
     * Create a payment preference.
     *
     * POST payments/create_preference
     */
    public function create_preference(): void
    {
        try {
            method('post');

            if (!setting('mercadopago_enabled')) {
                throw new RuntimeException('MercadoPago is not enabled.');
            }

            $access_token = setting('mercadopago_access_token');
            if (empty($access_token)) {
                throw new RuntimeException('MercadoPago access token is not configured.');
            }

            $this->mercadopago_client->initialize($access_token);

            check('appointment_hash', 'string');
            check('amount', 'numeric');
            check('currency', 'string');
            check('payer_email', 'string');
            check('payer_name', 'string');

            $appointment_hash = request('appointment_hash');
            $amount = request('amount');
            $currency = request('currency', setting('mercadopago_currency') ?: 'CLP');
            $payer_email = request('payer_email');
            $payer_name = request('payer_name');

            $appointments = $this->appointments_model->get(['hash' => $appointment_hash]);
            if (empty($appointments)) {
                throw new RuntimeException('Appointment not found.');
            }

            $appointment = $appointments[0];

            if ($appointment['payment_status'] !== 'pending') {
                throw new RuntimeException('Appointment is not in pending payment state.');
            }

            $service = $this->services_model->find($appointment['id_services']);

            $sandbox = setting('mercadopago_sandbox') === '1';
            $base_url = site_url('payments');

            $result = $this->mercadopago_client->create_preference(
                $service['name'] ?? 'Appointment',
                $amount,
                $currency,
                $payer_email,
                $payer_name,
                $appointment_hash,
                $base_url . '/success?hash=' . $appointment_hash,
                $base_url . '/failure?hash=' . $appointment_hash,
                $base_url . '/pending?hash=' . $appointment_hash,
            );

            $transaction = [
                'id_appointments' => $appointment['id'],
                'id_users_customer' => $appointment['id_users_customer'],
                'payment_method' => 'mercadopago',
                'preference_id' => $result['preference_id'],
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $currency,
            ];

            $this->payments_model->only($transaction);
            $this->payments_model->save($transaction);

            json_response([
                'preference_id' => $result['preference_id'],
                'init_point' => $sandbox && !empty($result['sandbox_init_point'])
                    ? $result['sandbox_init_point']
                    : $result['init_point'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Success callback from MercadoPago.
     *
     * GET payments/success?hash=...&payment_id=...
     */
    public function success(): void
    {
        $appointment_hash = request('hash') ?? request('external_reference');
        $payment_id = request('payment_id');

        if (empty($appointment_hash)) {
            redirect('booking');
            return;
        }

        $this->process_payment_callback($appointment_hash, $payment_id, 'success');
    }

    /**
     * Failure callback from MercadoPago.
     *
     * GET payments/failure?hash=...
     */
    public function failure(): void
    {
        $appointment_hash = request('hash') ?? request('external_reference');

        if (empty($appointment_hash)) {
            redirect('booking');
            return;
        }

        $this->process_payment_callback($appointment_hash, null, 'failure');
    }

    /**
     * Pending callback from MercadoPago.
     *
     * GET payments/pending?hash=...
     */
    public function pending(): void
    {
        $appointment_hash = request('hash') ?? request('external_reference');

        if (empty($appointment_hash)) {
            redirect('booking');
            return;
        }

        $this->process_payment_callback($appointment_hash, null, 'pending');
    }

    /**
     * Process a payment callback (success, failure, or pending).
     */
    private function process_payment_callback(string $appointment_hash, ?string $payment_id, string $outcome): void
    {
        $appointments = $this->appointments_model->get(['hash' => $appointment_hash]);
        if (empty($appointments)) {
            redirect('booking');
            return;
        }

        $appointment = $appointments[0];

        if ($outcome === 'success' && !empty($payment_id)) {
            $access_token = setting('mercadopago_access_token');
            if (!empty($access_token)) {
                try {
                    $this->mercadopago_client->initialize($access_token);
                    $payment_info = $this->mercadopago_client->get_payment_info($payment_id);

                    if ($payment_info['status'] === 'approved') {
                        $this->finalize_payment($appointment, $payment_info);
                        redirect('booking/reschedule/' . $appointment_hash);
                        return;
                    }
                } catch (Throwable $e) {
                    log_message('error', 'MercadoPago payment info error: ' . $e->getMessage());
                }
            }
        }

        $redirect_url = 'booking/reschedule/' . $appointment_hash;
        if ($outcome === 'failure') {
            $redirect_url .= '?payment_error=1';
        } elseif ($outcome === 'pending') {
            $redirect_url .= '?payment_pending=1';
        }

        redirect($redirect_url);
    }

    /**
     * Webhook endpoint for MercadoPago notifications.
     *
     * POST payments/webhook
     */
    public function webhook(): void
    {
        try {
            $data = $this->input->post();
            $headers = $this->input->request_headers();

            $topic = $data['topic'] ?? $data['type'] ?? '';

            if ($topic === 'payment' || isset($data['data']['id'])) {
                $payment_id = $data['data']['id'] ?? $data['id'] ?? null;

                if (empty($payment_id)) {
                    log_message('error', 'MercadoPago webhook: no payment ID found.');
                    json_response(['status' => 'error']);
                    return;
                }

                $access_token = setting('mercadopago_access_token');
                if (empty($access_token)) {
                    log_message('error', 'MercadoPago webhook: access token not configured.');
                    json_response(['status' => 'error']);
                    return;
                }

                $this->mercadopago_client->initialize($access_token);

                $payment_info = $this->mercadopago_client->get_payment_info($payment_id);
                $external_reference = $payment_info['external_reference'] ?? '';

                if (empty($external_reference)) {
                    log_message('error', 'MercadoPago webhook: no external reference in payment.');
                    json_response(['status' => 'error']);
                    return;
                }

                $appointments = $this->appointments_model->get(['hash' => $external_reference]);
                if (empty($appointments)) {
                    log_message('error', 'MercadoPago webhook: appointment not found for hash ' . $external_reference);
                    json_response(['status' => 'error']);
                    return;
                }

                $appointment = $appointments[0];

                if ($payment_info['status'] === 'approved') {
                    $this->finalize_payment($appointment, $payment_info);
                } elseif (in_array($payment_info['status'], ['rejected', 'cancelled'], true)) {
                    $this->appointments_model->save([
                        'id' => $appointment['id'],
                        'payment_status' => 'rejected',
                    ]);

                    $transaction = $this->payments_model->find_by_payment_id($payment_id);
                    if ($transaction) {
                        $this->payments_model->update_status($transaction['id'], $payment_info['status']);
                    }
                }

                json_response(['status' => 'ok']);
                return;
            }

            json_response(['status' => 'ignored']);
        } catch (Throwable $e) {
            log_message('error', 'MercadoPago webhook error: ' . $e->getMessage());
            json_response(['status' => 'error']);
        }
    }

    /**
     * Get payment status for an appointment.
     *
     * GET payments/status/<appointment_hash>
     */
    public function status(string $appointment_hash = ''): void
    {
        try {
            method('get');

            if (empty($appointment_hash)) {
                throw new RuntimeException('Appointment hash is required.');
            }

            $appointments = $this->appointments_model->get(['hash' => $appointment_hash]);
            if (empty($appointments)) {
                throw new RuntimeException('Appointment not found.');
            }

            $appointment = $appointments[0];

            $transaction = $this->payments_model->find_by_appointment_id($appointment['id']);

            $response = [
                'appointment_id' => $appointment['id'],
                'appointment_hash' => $appointment['hash'],
                'payment_status' => $appointment['payment_status'] ?? 'none',
                'payment_required' => (bool) ($appointment['payment_required'] ?? false),
            ];

            if ($transaction) {
                $response['transaction'] = [
                    'id' => $transaction['id'],
                    'payment_id' => $transaction['payment_id'],
                    'status' => $transaction['status'],
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                ];
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Finalize a payment: update appointment, sync, notify.
     */
    private function finalize_payment(array $appointment, array $payment_info): void
    {
        $this->appointments_model->save([
            'id' => $appointment['id'],
            'payment_status' => 'approved',
        ]);

        $transaction = $this->payments_model->find_by_appointment_id($appointment['id']);
        if ($transaction) {
            $update = [
                'id' => $transaction['id'],
                'payment_id' => $payment_info['payment_id'],
                'status' => 'approved',
                'mp_response' => json_encode($payment_info),
            ];
            $this->payments_model->only($update);
            $this->payments_model->save($update);
        } else {
            $new_transaction = [
                'id_appointments' => $appointment['id'],
                'id_users_customer' => $appointment['id_users_customer'],
                'payment_method' => 'mercadopago',
                'payment_id' => $payment_info['payment_id'],
                'status' => 'approved',
                'amount' => $payment_info['transaction_amount'] ?? 0,
                'currency' => $payment_info['currency_id'] ?? 'CLP',
                'mp_response' => json_encode($payment_info),
            ];
            $this->payments_model->only($new_transaction);
            $this->payments_model->save($new_transaction);
        }

        $appointment['payment_status'] = 'approved';

        $service = $this->services_model->find($appointment['id_services']);
        $provider = $this->providers_model->find($appointment['id_users_provider']);
        $customer = $this->customers_model->find($appointment['id_users_customer']);

        $company_color = setting('company_color');
        $settings = [
            'company_name' => setting('company_name'),
            'company_link' => setting('company_link'),
            'company_email' => setting('company_email'),
            'company_color' => !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];

        $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);
        $this->notifications->notify_payment_approved($appointment, $service, $provider, $customer, $settings);
        $this->webhooks_client->trigger('payment_approved', $appointment);

        log_message('info', 'Payment approved for appointment ' . $appointment['id'] . ' via MercadoPago.');
    }
}
