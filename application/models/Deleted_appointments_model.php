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
 * Deleted Appointments model.
 *
 * @package Models
 */
class Deleted_appointments_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'is_unavailability' => 'boolean',
        'id_users_provider' => 'integer',
        'id_users_customer' => 'integer',
        'id_services' => 'integer',
        'deleted_by' => 'integer',
    ];

    /**
     * Archive a deleted appointment.
     *
     * @param array $appointment The appointment data to archive.
     * @param string $cancellation_reason Optional cancellation reason.
     * @param int|null $deleted_by Optional user ID who deleted the appointment.
     *
     * @return void
     */
    public function archive(array $appointment, string $cancellation_reason = '', ?int $deleted_by = null): void
    {
        $record = [
            'appointment_id' => $appointment['id'],
            'book_datetime' => $appointment['book_datetime'] ?? null,
            'start_datetime' => $appointment['start_datetime'] ?? null,
            'end_datetime' => $appointment['end_datetime'] ?? null,
            'notes' => $appointment['notes'] ?? null,
            'hash' => $appointment['hash'] ?? null,
            'is_unavailability' => $appointment['is_unavailability'] ?? false,
            'id_users_provider' => $appointment['id_users_provider'] ?? null,
            'id_users_customer' => $appointment['id_users_customer'] ?? null,
            'id_services' => $appointment['id_services'] ?? null,
            'id_google_calendar' => $appointment['id_google_calendar'] ?? null,
            'id_caldav_calendar' => $appointment['id_caldav_calendar'] ?? null,
            'location' => $appointment['location'] ?? null,
            'meeting_link' => $appointment['meeting_link'] ?? null,
            'color' => $appointment['color'] ?? null,
            'status' => $appointment['status'] ?? null,
            'update_datetime' => $appointment['update_datetime'] ?? null,
            'create_datetime' => $appointment['create_datetime'] ?? null,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deleted_by,
            'cancellation_reason' => $cancellation_reason,
        ];

        $this->db->insert('deleted_appointments', $record);
    }

    /**
     * Find a deleted appointment by its original appointment ID.
     *
     * @param int $appointment_id The original appointment ID.
     *
     * @return array The deleted appointment data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $appointment_id): array
    {
        $appointment = $this->db
            ->get_where('deleted_appointments', ['appointment_id' => $appointment_id])
            ->row_array();

        if (!$appointment) {
            throw new InvalidArgumentException(
                'The provided deleted appointment ID was not found in the archive: ' . $appointment_id,
            );
        }

        $this->cast($appointment);

        return $appointment;
    }

    /**
     * Get all deleted appointments with optional filters.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of deleted appointments.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $appointments = $this->db
            ->get('deleted_appointments', $limit, $offset)
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Clean up old deleted appointments.
     *
     * @param int $days_retention Number of days to retain deleted appointments.
     *
     * @return void
     */
    public function cleanup(int $days_retention): void
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days_retention . ' days'));

        $this->db->where('deleted_at <', $cutoff_date)->delete('deleted_appointments');
    }
}
