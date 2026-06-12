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

class Migration_Create_deleted_appointments_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'appointment_id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
            'book_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'start_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'end_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'hash' => [
                'type' => 'VARCHAR',
                'constraint' => '12',
                'null' => true,
            ],
            'is_unavailability' => [
                'type' => 'TINYINT',
                'constraint' => '4',
                'default' => '0',
            ],
            'id_users_provider' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
            ],
            'id_users_customer' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
            ],
            'id_services' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
            ],
            'id_google_calendar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'id_caldav_calendar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'location' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'meeting_link' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => true,
            ],
            'update_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'create_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'deleted_by' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
            ],
            'cancellation_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('appointment_id');
        $this->dbforge->add_key('deleted_at');

        $this->dbforge->create_table('deleted_appointments', true, ['engine' => 'InnoDB']);
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('deleted_appointments', true);
    }
}
