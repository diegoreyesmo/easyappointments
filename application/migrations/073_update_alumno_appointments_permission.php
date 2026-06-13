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

class Migration_Update_alumno_appointments_permission extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->db->where('slug', 'alumno')->update('roles', ['appointments' => 3]);
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->db->where('slug', 'alumno')->update('roles', ['appointments' => 1]);
    }
}
