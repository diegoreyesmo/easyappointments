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

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\Preference;

/**
 * MercadoPago client library.
 *
 * Handles integration with MercadoPago payment gateway.
 *
 * @package Libraries
 */
class Mercadopago_client
{
    protected EA_Controller|CI_Controller $CI;
    private string $access_token = '';
    private bool $sdk_initialized = false;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function initialize(string $access_token): void
    {
        $this->access_token = $access_token;
        MercadoPagoConfig::setAccessToken($this->access_token);
        $this->sdk_initialized = true;
    }

    public function is_initialized(): bool
    {
        return $this->sdk_initialized;
    }

    /**
     * Create a payment preference.
     *
     * @param string $title Item title
     * @param float|int $amount Amount to charge
     * @param string $currency Currency code (e.g. 'CLP')
     * @param string $payer_email Payer email
     * @param string $payer_name Payer full name
     * @param string $external_reference External reference (usually appointment hash)
     * @param string $success_url Success callback URL
     * @param string $failure_url Failure callback URL
     * @param string $pending_url Pending callback URL
     * @return array Returns ['preference_id' => ..., 'init_point' => ..., 'sandbox_init_point' => ...]
     * @throws Exception
     */
    public function create_preference(
        string $title,
        $amount,
        string $currency,
        string $payer_email,
        string $payer_name,
        string $external_reference,
        string $success_url,
        string $failure_url,
        string $pending_url,
    ): array {
        if (!$this->sdk_initialized) {
            throw new RuntimeException('MercadoPago SDK is not initialized. Call initialize() first.');
        }

        $client = new PreferenceClient();

        $name_parts = explode(' ', $payer_name, 2);
        $first_name = $name_parts[0] ?? 'Customer';
        $last_name = $name_parts[1] ?? '';

        $preference = $client->create([
            'items' => [
                [
                    'title' => $title,
                    'unit_price' => (float) $amount,
                    'quantity' => 1,
                    'currency_id' => $currency,
                ],
            ],
            'payer' => [
                'email' => $payer_email,
                'name' => $first_name,
                'surname' => $last_name,
            ],
            'external_reference' => $external_reference,
            'back_urls' => [
                'success' => $success_url,
                'failure' => $failure_url,
                'pending' => $pending_url,
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => setting('mercadopago_statement_descriptor') ?: 'EasyAppointments',
        ]);

        return [
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point,
        ];
    }

    /**
     * Get payment information by payment ID.
     *
     * @param string $payment_id MercadoPago payment ID
     * @return array Returns payment status info
     * @throws Exception
     */
    public function get_payment_info(string $payment_id): array
    {
        if (!$this->sdk_initialized) {
            throw new RuntimeException('MercadoPago SDK is not initialized. Call initialize() first.');
        }

        $client = new PaymentClient();
        $payment = $client->get((int) $payment_id);

        return [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'status_detail' => $payment->status_detail ?? '',
            'transaction_amount' => $payment->transaction_amount,
            'currency_id' => $payment->currency_id,
            'payer_email' => $payment->payer?->email ?? '',
            'external_reference' => $payment->external_reference ?? '',
            'date_approved' => $payment->date_approved ?? null,
        ];
    }

    /**
     * Verify webhook signature from MercadoPago.
     *
     * @param array $data POST data from webhook
     * @param array $headers Request headers
     * @return bool True if signature is valid
     */
    public function verify_webhook(array $data, array $headers): bool
    {
        $client_secret = setting('mercadopago_client_secret');

        if (empty($client_secret)) {
            log_message('error', 'MercadoPago webhook verification: client_secret not configured.');
            return false;
        }

        $x_signature = $headers['x-signature'] ?? $this->get_header($headers, 'x-signature') ?? '';
        $request_url = $this->get_request_url();

        if (empty($x_signature)) {
            log_message('error', 'MercadoPago webhook: missing x-signature header.');
            return false;
        }

        $parts = [];
        foreach (explode(',', $x_signature) as $part) {
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $parts[trim($key)] = trim($value);
            }
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if (empty($ts) || empty($v1)) {
            log_message('error', 'MercadoPago webhook: invalid signature format.');
            return false;
        }

        $manifest = "id:{$data['id']};request-url:{$request_url}";
        $computed_hash = hash_hmac('sha256', $manifest, $client_secret);

        return hash_equals($computed_hash, $v1);
    }

    /**
     * Get a header value case-insensitively.
     */
    private function get_header(array $headers, string $name): ?string
    {
        $name_lower = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name_lower) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }
        return null;
    }

    /**
     * Get current request URL.
     */
    private function get_request_url(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . '://' . $host . $uri;
    }
}
