<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * AgendaRRF - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Alumnos controller.
 *
 * Handles the alumnos related operations.
 *
 * @package Controllers
 */
class Alumnos extends EA_Controller
{
    public array $allowed_alumno_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'mobile_number',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        'timezone',
        'language',
        'id_roles',
        'is_approved',
        'settings',
    ];

    public array $optional_alumno_fields = [
        'allowed_services' => [],
        'allowed_providers' => [],
        'is_approved' => 0,
    ];

    public array $allowed_alumno_setting_fields = [
        'username',
        'password',
        'notifications',
        'appointment_quota',
    ];

    public array $optional_alumno_setting_fields = [
        'appointment_quota' => 0,
    ];

    public array $allowed_service_fields = ['id', 'name'];
    public array $allowed_provider_fields = ['id', 'first_name', 'last_name'];

    /**
     * Alumnos constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('users_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
    }

    /**
     * Render the backend alumnos page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('alumnos')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_USERS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $services = $this->services_model->get();
        foreach ($services as &$service) {
            $this->services_model->only($service, $this->allowed_service_fields);
        }

        $providers = $this->providers_model->get();
        foreach ($providers as &$provider) {
            $this->providers_model->only($provider, $this->allowed_provider_fields);
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
            'first_weekday' => setting('first_weekday'),
            'min_password_length' => MIN_PASSWORD_LENGTH,
            'timezones' => $this->timezones->to_array(),
            'services' => $services,
            'providers' => $providers,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
        ]);

        html_vars([
            'page_title' => lang('alumnos'),
            'active_menu' => PRIV_USERS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'services' => $services,
            'providers' => $providers,
        ]);

        $this->load->view('pages/alumnos');
    }

    /**
     * Filter alumnos by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('order_by', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');
            $order_by = request('order_by', 'update_datetime DESC');
            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $alumnos = $this->users_model->search($keyword, $limit, $offset, $order_by);

            $filtered_alumnos = [];
            foreach ($alumnos as $alumno) {
                if (isset($alumno['id_roles'])) {
                    $role = $this->roles_model->find($alumno['id_roles']);
                    if (isset($role['slug']) && $role['slug'] === 'alumno') {
                        $alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno['id']);
                        $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno['id']);
                        $filtered_alumnos[] = $alumno;
                    }
                }
            }

            json_response($filtered_alumnos);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new alumno.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('alumno', 'array');

            $alumno = request('alumno');

            $roles = $this->roles_model->get(['slug' => 'alumno']);
            if (empty($roles)) {
                throw new RuntimeException('Role "alumno" not found.');
            }
            $alumno['id_roles'] = $roles[0]['id'];

            $this->users_model->only($alumno, $this->allowed_alumno_fields);
            $this->users_model->only($alumno['settings'], $this->allowed_alumno_setting_fields);
            $this->users_model->optional($alumno, $this->optional_alumno_fields);
            $this->users_model->optional($alumno['settings'], $this->optional_alumno_setting_fields);

            $alumno_id = $this->users_model->save($alumno);

            if (!empty($alumno['allowed_services'])) {
                $this->users_model->save_allowed_services($alumno_id, $alumno['allowed_services']);
            }

            if (!empty($alumno['allowed_providers'])) {
                $this->users_model->save_allowed_providers($alumno_id, $alumno['allowed_providers']);
            }

            $alumno = $this->users_model->find($alumno_id);
            $alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno_id);
            $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $alumno);

            json_response([
                'success' => true,
                'id' => $alumno_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find an alumno.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('alumno_id', 'numeric');

            $alumno_id = request('alumno_id');

            if (empty($alumno_id) || !filter_var($alumno_id, FILTER_VALIDATE_INT) || $alumno_id <= 0) {
                throw new InvalidArgumentException('Invalid alumno ID provided.');
            }

            $alumno = $this->users_model->find($alumno_id);
            $alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno_id);
            $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno_id);

            json_response($alumno);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update an alumno.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('alumno', 'array');

            $alumno = request('alumno');

            $this->users_model->only($alumno, $this->allowed_alumno_fields);
            $this->users_model->only($alumno['settings'], $this->allowed_alumno_setting_fields);
            $this->users_model->optional($alumno, $this->optional_alumno_fields);
            $this->users_model->optional($alumno['settings'], $this->optional_alumno_setting_fields);

            $alumno_id = $this->users_model->save($alumno);

            if (isset($alumno['allowed_services'])) {
                $this->users_model->save_allowed_services($alumno_id, $alumno['allowed_services']);
            }

            if (isset($alumno['allowed_providers'])) {
                $this->users_model->save_allowed_providers($alumno_id, $alumno['allowed_providers']);
            }

            $alumno = $this->users_model->find($alumno_id);
            $alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno_id);
            $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $alumno);

            json_response([
                'success' => true,
                'id' => $alumno_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove an alumno.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('alumno_id', 'numeric');

            $alumno_id = request('alumno_id');

            if (empty($alumno_id) || !filter_var($alumno_id, FILTER_VALIDATE_INT) || $alumno_id <= 0) {
                throw new InvalidArgumentException('Invalid alumno ID provided.');
            }

            $alumno = $this->users_model->find($alumno_id);

            $this->users_model->delete($alumno_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_DELETE, $alumno);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
