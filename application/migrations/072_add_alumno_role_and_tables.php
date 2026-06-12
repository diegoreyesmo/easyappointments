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

class Migration_Add_alumno_role_and_tables extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        // 1. Add 'alumno' role
        $this->db->insert('roles', [
            'name' => 'Alumno',
            'slug' => 'alumno',
            'is_admin' => false,
            'appointments' => 1,
            'customers' => 0,
            'services' => 0,
            'users' => 0,
            'system_settings' => 0,
            'user_settings' => 1,
        ]);

        // 2. Add is_approved column to ea_users
        $this->dbforge->add_column('users', [
            'is_approved' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => 0,
                'after' => 'id_roles',
            ],
        ]);

        // 3. Add appointment_quota column to ea_user_settings
        $this->dbforge->add_column('user_settings', [
            'appointment_quota' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
                'after' => 'salt',
            ],
        ]);

        // 4. Create ea_alumnos_services table
        $this->dbforge->add_field([
            'id_users' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
            'id_services' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
        ]);
        $this->dbforge->add_key(['id_users', 'id_services'], true);
        $this->dbforge->create_table('alumnos_services', true, ['engine' => 'InnoDB']);

        // 5. Create ea_alumnos_providers table
        $this->dbforge->add_field([
            'id_users' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
            'id_users_provider' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
        ]);
        $this->dbforge->add_key(['id_users', 'id_users_provider'], true);
        $this->dbforge->create_table('alumnos_providers', true, ['engine' => 'InnoDB']);
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('alumnos_providers', true);
        $this->dbforge->drop_table('alumnos_services', true);
        
        $this->dbforge->drop_column('user_settings', 'appointment_quota');
        $this->dbforge->drop_column('users', 'is_approved');
        
        $this->db->delete('roles', ['slug' => 'alumno']);
    }
}
