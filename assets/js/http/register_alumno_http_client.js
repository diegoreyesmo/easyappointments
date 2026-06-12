/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Register Alumno HTTP client.
 *
 * This module implements the alumno registration related HTTP requests.
 */
App.Http.RegisterAlumno = (function () {
    /**
     * Store a new alumno.
     *
     * @param {Object} alumno
     *
     * @return {Object}
     */
    function store(alumno) {
        const url = App.Utils.Url.siteUrl('booking/store_alumno');

        const data = {
            csrf_token: vars('csrf_token'),
            ...alumno,
        };

        return $.post(url, data);
    }

    return {
        store,
    };
})();
