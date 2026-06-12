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
 * Alumnos HTTP client.
 *
 * This module implements the alumnos related HTTP requests.
 */
App.Http.Alumnos = (function () {
    /**
     * Save (create or update) an alumno.
     *
     * @param {Object} alumno
     *
     * @return {Object}
     */
    function save(alumno) {
        return alumno.id ? update(alumno) : store(alumno);
    }

    /**
     * Create an alumno.
     *
     * @param {Object} alumno
     *
     * @return {Object}
     */
    function store(alumno) {
        const url = App.Utils.Url.siteUrl('alumnos/store');

        const data = {
            csrf_token: vars('csrf_token'),
            alumno: alumno,
        };

        return $.post(url, data);
    }

    /**
     * Update an alumno.
     *
     * @param {Object} alumno
     *
     * @return {Object}
     */
    function update(alumno) {
        const url = App.Utils.Url.siteUrl('alumnos/update');

        const data = {
            csrf_token: vars('csrf_token'),
            alumno: alumno,
        };

        return $.post(url, data);
    }

    /**
     * Delete an alumno.
     *
     * @param {Number} alumnoId
     *
     * @return {Object}
     */
    function destroy(alumnoId) {
        const url = App.Utils.Url.siteUrl('alumnos/destroy');

        const data = {
            csrf_token: vars('csrf_token'),
            alumno_id: alumnoId,
        };

        return $.post(url, data);
    }

    /**
     * Search alumnos by keyword.
     *
     * @param {String} keyword
     * @param {Number} [limit]
     * @param {Number} [offset]
     * @param {String} [orderBy]
     *
     * @return {Object}
     */
    function search(keyword, limit = null, offset = null, orderBy = null) {
        const url = App.Utils.Url.siteUrl('alumnos/search');

        const data = {
            csrf_token: vars('csrf_token'),
            keyword,
            limit,
            offset,
            order_by: orderBy || undefined,
        };

        return $.post(url, data);
    }

    /**
     * Find an alumno.
     *
     * @param {Number} alumnoId
     *
     * @return {Object}
     */
    function find(alumnoId) {
        const url = App.Utils.Url.siteUrl('alumnos/find');

        const data = {
            csrf_token: vars('csrf_token'),
            alumno_id: alumnoId,
        };

        return $.post(url, data);
    }

    return {
        save,
        store,
        update,
        destroy,
        search,
        find,
    };
})();
