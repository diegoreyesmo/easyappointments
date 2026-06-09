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
 * Email Queue model.
 *
 * Handles all the database operations of the email queue resource.
 *
 * @package Models
 */
class Email_queue_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'attempts' => 'integer',
    ];

    /**
     * Add a new job to the email queue.
     *
     * @param string $notification_type Notification type (e.g., 'appointment_saved').
     * @param int $appointment_id Appointment ID.
     * @param string $recipient_type Recipient type ('customer', 'provider', 'admin', 'secretary').
     * @param string $recipient_email Recipient email address.
     *
     * @return int Returns the inserted queue ID.
     */
    public function add_to_queue(
        string $notification_type,
        int $appointment_id,
        string $recipient_type,
        string $recipient_email,
    ): int {
        $data = [
            'notification_type' => $notification_type,
            'appointment_id' => $appointment_id,
            'recipient_type' => $recipient_type,
            'recipient_email' => $recipient_email,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('email_queue', $data);
        return (int) $this->db->insert_id();
    }

    /**
     * Get pending jobs from the queue.
     *
     * @param int $limit Maximum number of jobs to fetch.
     *
     * @return array Returns an array of pending queue jobs.
     */
    public function get_pending_jobs(int $limit = 50): array
    {
        return $this->db
            ->select('*')
            ->from('email_queue')
            ->where('status', 'pending')
            ->order_by('created_at', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Mark a queue job as sent.
     *
     * @param int $id Queue job ID.
     */
    public function mark_as_sent(int $id): void
    {
        $this->db->update(
            'email_queue',
            [
                'status' => 'sent',
                'last_attempt' => date('Y-m-d H:i:s'),
            ],
            ['id' => $id],
        );
    }

    /**
     * Mark a queue job as failed.
     *
     * @param int $id Queue job ID.
     * @param string $error_message Error message.
     */
    public function mark_as_failed(int $id, string $error_message): void
    {
        $this->db->set('attempts', 'attempts + 1', false);
        $this->db->set('last_attempt', date('Y-m-d H:i:s'));
        $this->db->set('error_message', $error_message);
        $this->db->where('id', $id);
        $this->db->update('email_queue');
    }

    /**
     * Get the status of a queue job.
     *
     * @param int $id Queue job ID.
     *
     * @return string|null Returns the status or null if not found.
     */
    public function get_status(int $id): ?string
    {
        $job = $this->db->select('status')->from('email_queue')->where('id', $id)->get()->row_array();
        return $job ? $job['status'] : null;
    }
}
