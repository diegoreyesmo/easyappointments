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

class Migration_Add_mercadopago_payment_support extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_appointments' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'id_users_customer' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'preference_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'payment_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'mp_response' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'create_datetime' => [
                'type' => 'DATETIME',
            ],
            'update_datetime' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_appointments');
        $this->dbforge->add_key('payment_id');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('payment_transactions', true);

        $this->dbforge->add_column('appointments', [
            'payment_required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'payment_status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'none',
            ],
        ]);

        $settings = [
            ['name' => 'mercadopago_enabled', 'value' => '0'],
            ['name' => 'mercadopago_access_token', 'value' => ''],
            ['name' => 'mercadopago_public_key', 'value' => ''],
            ['name' => 'mercadopago_client_id', 'value' => ''],
            ['name' => 'mercadopago_client_secret', 'value' => ''],
            ['name' => 'mercadopago_sandbox', 'value' => '1'],
            ['name' => 'mercadopago_currency', 'value' => 'CLP'],
            ['name' => 'mercadopago_statement_descriptor', 'value' => ''],
        ];

        $this->db->insert_batch('settings', $settings);
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('payment_transactions', true);

        $this->dbforge->drop_column('appointments', 'payment_required');
        $this->dbforge->drop_column('appointments', 'payment_status');

        $this->db->where_in('name', [
            'mercadopago_enabled',
            'mercadopago_access_token',
            'mercadopago_public_key',
            'mercadopago_client_id',
            'mercadopago_client_secret',
            'mercadopago_sandbox',
            'mercadopago_currency',
            'mercadopago_statement_descriptor',
        ])->delete('settings');
    }
}
