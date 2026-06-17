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
 * MercadoPago settings controller.
 *
 * Handles MercadoPago payment settings.
 *
 * @package Controllers
 */
class Mercadopago_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->library('accounts');
    }

    /**
     * Render the MercadoPago settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('mercadopago_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $mp_settings = [];
        $all_settings = $this->settings_model->get_batch();
        $mp_keys = [
            'mercadopago_enabled',
            'mercadopago_access_token',
            'mercadopago_public_key',
            'mercadopago_client_id',
            'mercadopago_client_secret',
            'mercadopago_sandbox',
            'mercadopago_currency',
            'mercadopago_statement_descriptor',
        ];

        foreach ($all_settings as $setting) {
            if (in_array($setting['name'], $mp_keys, true)) {
                $mp_settings[$setting['name']] = $setting['value'];
            }
        }

        $webhook_url = site_url('payments/webhook');

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'mercadopago_settings' => $mp_settings,
            'mercadopago_webhook_url' => $webhook_url,
        ]);

        html_vars([
            'page_title' => lang('mercadopago_settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/mercadopago_settings');
    }

    /**
     * Save MercadoPago settings.
     */
    public function save(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('mercadopago_settings', 'array|null');

            $settings = request('mercadopago_settings') ?: [];

            $allowed_keys = [
                'mercadopago_enabled',
                'mercadopago_access_token',
                'mercadopago_public_key',
                'mercadopago_client_id',
                'mercadopago_client_secret',
                'mercadopago_sandbox',
                'mercadopago_currency',
                'mercadopago_statement_descriptor',
            ];

            foreach ($allowed_keys as $key) {
                $value = $settings[$key] ?? '';

                if ($key === 'mercadopago_enabled' || $key === 'mercadopago_sandbox') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                }

                $this->settings_model->set_setting($key, $value);
            }

            json_response([
                'success' => true,
                'message' => lang('settings_saved_successfully'),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Test MercadoPago connection.
     */
    public function test_connection(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('access_token', 'string');

            $access_token = request('access_token');

            if (empty($access_token)) {
                throw new RuntimeException('Access token is required.');
            }

            $this->load->library('mercadopago_client');
            $this->mercadopago_client->initialize($access_token);

            $result = $this->mercadopago_client->create_preference(
                'Test Connection',
                1,
                'CLP',
                'test@test.com',
                'Test User',
                'test-' . time(),
                site_url('payments/success'),
                site_url('payments/failure'),
                site_url('payments/pending'),
            );

            json_response([
                'success' => true,
                'message' => lang('connection_successful'),
                'preference_id' => $result['preference_id'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
