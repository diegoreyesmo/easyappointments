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
 * Alumnos API v1 controller.
 *
 * @package Controllers
 */
class Alumnos_api_v1 extends EA_Controller
{
    /**
     * Alumnos_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');
        $this->load->library('webhooks_client');
        $this->load->model('users_model');
        $this->load->model('roles_model');

        $this->api->auth();
    }

    /**
     * Get an alumno collection.
     */
    public function index(): void
    {
        try {
            $keyword = $this->api->request_keyword();
            $limit = $this->api->request_limit();
            $offset = $this->api->request_offset();
            $order_by = $this->api->request_order_by();
            $fields = $this->api->request_fields();
            $with = $this->api->request_with();

            $alumnos = empty($keyword)
                ? $this->users_model->get(null, $limit, $offset, $order_by)
                : $this->users_model->search($keyword, $limit, $offset, $order_by);

            $filtered_alumnos = [];
            foreach ($alumnos as $alumno) {
                $role = $this->roles_model->find($alumno['id_roles']);
                if (isset($role['slug']) && $role['slug'] === 'alumno') {
                    $alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno['id']);
                    $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno['id']);
                    
                    $this->users_model->api_encode($alumno);

                    if (!empty($fields)) {
                        $this->users_model->only($alumno, $fields);
                    }

                    if (!empty($with)) {
                        $this->users_model->load($alumno, $with);
                    }
                    
                    $filtered_alumnos[] = $alumno;
                }
            }

            json_response($filtered_alumnos);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single alumno.
     *
     * @param int|null $id Alumno ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $alumno = $this->users_model->find($id);
            
            $role = $this->roles_model->find($alumno['id_roles']);
            if (!isset($role['slug']) || $role['slug'] !== 'alumno') {
                response('', 404);
                return;
            }

            $fields = $this->api->request_fields();
            $with = $this->api->request_with();

            $alumno['allowed_services'] = $this->users_model->get_allowed_services($id);
            $alumno['allowed_providers'] = $this->users_model->get_allowed_providers($id);

            $this->users_model->api_encode($alumno);

            if (!empty($fields)) {
                $this->users_model->only($alumno, $fields);
            }

            if (!empty($with)) {
                $this->users_model->load($alumno, $with);
            }

            json_response($alumno);
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
            $alumno = request();

            $this->users_model->api_decode($alumno);

            if (array_key_exists('id', $alumno)) {
                unset($alumno['id']);
            }

            $role = $this->roles_model->find_record_id(['slug' => 'alumno']);
            $alumno['id_roles'] = $role;

            if (!array_key_exists('settings', $alumno)) {
                throw new InvalidArgumentException('No settings property provided.');
            }

            $alumno_id = $this->users_model->save($alumno);

            if (!empty($alumno['allowed_services'])) {
                $this->users_model->save_allowed_services($alumno_id, $alumno['allowed_services']);
            }

            if (!empty($alumno['allowed_providers'])) {
                $this->users_model->save_allowed_providers($alumno_id, $alumno['allowed_providers']);
            }

            $created_alumno = $this->users_model->find($alumno_id);
            $created_alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno_id);
            $created_alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $created_alumno);

            $this->users_model->api_encode($created_alumno);

            json_response($created_alumno, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update an alumno.
     *
     * @param int $id Alumno ID.
     */
    public function update(int $id): void
    {
        try {
            $original_alumno = $this->users_model->find($id);
            
            $role = $this->roles_model->find($original_alumno['id_roles']);
            if (!isset($role['slug']) || $role['slug'] !== 'alumno') {
                response('', 404);
                return;
            }

            $alumno = request();

            $this->users_model->api_decode($alumno, $original_alumno);

            $alumno_id = $this->users_model->save($alumno);

            if (isset($alumno['allowed_services'])) {
                $this->users_model->save_allowed_services($alumno_id, $alumno['allowed_services']);
            }

            if (isset($alumno['allowed_providers'])) {
                $this->users_model->save_allowed_providers($alumno_id, $alumno['allowed_providers']);
            }

            $updated_alumno = $this->users_model->find($alumno_id);
            $updated_alumno['allowed_services'] = $this->users_model->get_allowed_services($alumno_id);
            $updated_alumno['allowed_providers'] = $this->users_model->get_allowed_providers($alumno_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $updated_alumno);

            $this->users_model->api_encode($updated_alumno);

            json_response($updated_alumno);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an alumno.
     *
     * @param int $id Alumno ID.
     */
    public function destroy(int $id): void
    {
        try {
            $original_alumno = $this->users_model->find($id);
            
            $role = $this->roles_model->find($original_alumno['id_roles']);
            if (!isset($role['slug']) || $role['slug'] !== 'alumno') {
                response('', 404);
                return;
            }

            $this->users_model->delete($id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_DELETE, $original_alumno);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
