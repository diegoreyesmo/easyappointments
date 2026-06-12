<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * AgendaRRF - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

use Jsvrcek\ICS\Exception\CalendarEventException;

require_once __DIR__ . '/Google.php';
require_once __DIR__ . '/Caldav.php';

/**
 * Console controller.
 *
 * Handles all the Console related operations.
 */
class Console extends EA_Controller
{
    /**
     * Console constructor.
     */
    public function __construct()
    {
        if (!is_cli()) {
            exit('No direct script access allowed');
        }

        parent::__construct();

        $this->load->dbutil();

        $this->load->library('instance');
        $this->load->library('cleanup');
        $this->load->library('email_messages');
        $this->load->library('ics_file');

        $this->load->model('admins_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('email_queue_model');
        $this->load->model('appointments_model');
        $this->load->model('deleted_appointments_model');
        
        
    }

     /**
     * Process pending email queue jobs.
     *
     * Usage: php index.php cli email_worker process
     */
    public function process(): void
    {
        $limit = 50;
        $max_attempts = 3;
        $jobs = $this->email_queue_model->get_pending_jobs($limit);

        if (empty($jobs)) {
            echo "No pending jobs found.\n";
            return;
        }

        echo "Processing " . count($jobs) . " pending job(s)...\n";

        foreach ($jobs as $job) {
            $job_id = (int) $job['id'];
            $appointment_id = (int) $job['appointment_id'];
            $notification_type = $job['notification_type'];
            $recipient_type = $job['recipient_type'];
            $recipient_email = $job['recipient_email'];

            if ((int) $job['attempts'] >= $max_attempts) {
                echo "Job {$job_id} exceeded max attempts ({$max_attempts}). Marking as failed.\n";
                $this->email_queue_model->mark_as_failed($job_id, 'Max attempts exceeded');
                continue;
            }

            try {
                try {
                    $appointment = $this->appointments_model->find($appointment_id);
                } catch (InvalidArgumentException $e) {
                    $appointment = $this->deleted_appointments_model->find($appointment_id);
                }

                $provider = $this->providers_model->find($appointment['id_users_provider']);
                $service = $this->services_model->find($appointment['id_services']);
                $customer = $this->customers_model->find($appointment['id_users_customer']);
                $settings = $this->settings_model->get();

                $settings_array = [];
                foreach ($settings as $setting) {
                    $settings_array[$setting['name']] = $setting['value'];
                }

                if ($notification_type === 'appointment_saved') {
                    $manage_mode = false;
                    $customer_link = site_url('booking/reschedule/' . $appointment['hash']);
                    $provider_link = site_url('calendar/reschedule/' . $appointment['hash']);
                    $ics_stream = $this->ics_file->get_stream($appointment, $service, $provider, $customer);

                    if ($recipient_type === 'customer') {
                        $subject = lang('appointment_booked');
                        $message = lang('thank_you_for_appointment');
                        $this->email_messages->send_appointment_saved(
                            $appointment,
                            $provider,
                            $service,
                            $customer,
                            $settings_array,
                            $subject,
                            $message,
                            $customer_link,
                            $recipient_email,
                            $ics_stream,
                            $customer['timezone'] ?? null
                        );
                    } else {
                        $subject = lang('appointment_added_to_your_plan');
                        $message = lang('appointment_link_description');
                        $this->email_messages->send_appointment_saved(
                            $appointment,
                            $provider,
                            $service,
                            $customer,
                            $settings_array,
                            $subject,
                            $message,
                            $provider_link,
                            $recipient_email,
                            $ics_stream,
                            $provider['timezone'] ?? null
                        );
                    }
                } elseif ($notification_type === 'appointment_deleted') {
                    $this->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings_array,
                        $recipient_email,
                        '',
                        $provider['timezone'] ?? null
                    );
                } else {
                    throw new RuntimeException("Unknown notification type: {$notification_type}");
                }

                $this->email_queue_model->mark_as_sent($job_id);
                echo "Job {$job_id} sent successfully to {$recipient_email}.\n";
            } catch (Throwable $e) {
                $error_message = $e->getMessage();
                $this->email_queue_model->mark_as_failed($job_id, $error_message);
                echo "Job {$job_id} failed: {$error_message}\n";
            }
        }

        echo "Processing complete.\n";
    }
    
    /**
     * Perform a console installation.
     *
     * Use this method to install AgendaRRF directly from the terminal.
     *
     * Usage:
     *
     * php index.php console install
     *
     * @throws Exception
     */
    public function install(): void
    {
        $this->instance->migrate('fresh');

        $password = $this->instance->seed();

        response(
            PHP_EOL . '⇾ Installation completed, login with "administrator" / "' . $password . '".' . PHP_EOL . PHP_EOL,
        );
    }

    /**
     * Migrate the database to the latest state.
     *
     * Use this method to upgrade an AgendaRRF instance to the latest database state.
     *
     * Notice:
     *
     * Do not use this method to install the app as it will not seed the database with the initial entries (admin,
     * provider, service, settings etc.).
     *
     * Usage:
     *
     * php index.php console migrate
     *
     * php index.php console migrate fresh
     *
     * @param string $type
     */
    public function migrate(string $type = ''): void
    {
        $this->instance->migrate($type);
    }

    /**
     * Seed the database with test data.
     *
     * Use this method to add test data to your database
     *
     * Usage:
     *
     * php index.php console seed
     * @throws Exception
     */
    public function seed(): void
    {
        $this->instance->seed();
    }

    /**
     * Create a database backup file.
     *
     * Use this method to back up your AgendaRRF data.
     *
     * Usage:
     *
     * php index.php console backup
     *
     * php index.php console backup /path/to/backup/folder
     *
     * @throws Exception
     */
    public function backup(): void
    {
        $this->instance->backup($GLOBALS['argv'][3] ?? null);
    }

    /**
     * Trigger the synchronization of all provider calendars with Google Calendar.
     *
     * Use this method in a cronjob to automatically sync events between AgendaRRF and Google Calendar.
     *
     * Notice:
     *
     * Google syncing must first be enabled for each individual provider from inside the backend calendar page.
     *
     * Usage:
     *
     * php index.php console sync
     *
     * @throws CalendarEventException
     * @throws Exception
     * @throws Throwable
     */
    public function sync(): void
    {
        $providers = $this->providers_model->get();

        foreach ($providers as $provider) {
            if (filter_var($provider['settings']['google_sync'], FILTER_VALIDATE_BOOLEAN)) {
                Google::sync((string) $provider['id']);
            }

            if (filter_var($provider['settings']['caldav_sync'], FILTER_VALIDATE_BOOLEAN)) {
                Caldav::sync((string) $provider['id']);
            }
        }
    }

    /**
     * Clean up old customer data based on data retention settings.
     *
     * Use this method in a cronjob to automatically delete customer data older than the configured retention period.
     *
     * Usage:
     *
     * php index.php console cleanup
     *
     * @throws Exception
     */
    public function cleanup(): void
    {
        $this->cleanup->run();
    }

    /**
     * Show help information about the console capabilities.
     *
     * Use this method to see the available commands.
     *
     * Usage:
     *
     * php index.php console help
     */
    public function help(): void
    {
        $help = [
            '',
            'AgendaRRF ' . config('version'),
            '',
            'Usage:',
            '',
            '⇾ php index.php console [command] [arguments]',
            '',
            'Commands:',
            '',
            '⇾ php index.php console migrate',
            '⇾ php index.php console migrate fresh',
            '⇾ php index.php console migrate up',
            '⇾ php index.php console migrate down',
            '⇾ php index.php console seed',
            '⇾ php index.php console install',
            '⇾ php index.php console backup',
            '⇾ php index.php console sync',
            '⇾ php index.php console cleanup    (cleans sessions, logs, cache, and customer data)',
            '',
            '',
        ];

        response(implode(PHP_EOL, $help));
    }
}
