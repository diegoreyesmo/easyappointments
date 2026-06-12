<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="alumnos-page">
    <div class="row" id="alumnos">
        <div id="filter-alumnos" class="filter-records column col-12 mb-4">
            <button id="add-alumno" class="btn btn-primary add-record-btn mb-4">
                <i class="fas fa-plus-square me-2"></i>
                <?= lang('add') ?>
            </button>

            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control" aria-label="keyword">

                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h4 class="mb-3 fw-light">
                <?= lang('alumnos') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">
            <div class="float-md-start mb-4 me-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-alumno" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-alumno" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-alumno" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-alumno" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                </div>

            </div>

            <ul class="nav nav-pills switch-view">
                <li class="nav-item">
                    <a class="nav-link active" href="#details" data-bs-toggle="tab">
                        <?= lang('details') ?>
                    </a>
                </li>
            </ul>

            <div class="form-message alert mt-4" style="display:none;"></div>

            <div class="tab-content">
                <div class="details-view tab-pane fade show active clearfix" id="details">
                    <h4 class="mb-3 fw-light">
                        <?= lang('details') ?>
                    </h4>

                    <input type="hidden" id="id" class="record-id">

                    <div class="row">
                        <div class="details col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="first-name">
                                    <?= lang('first_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="first-name" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="last-name">
                                    <?= lang('last_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="last-name" class="form-control required" maxlength="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">
                                    <?= lang('email') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="email" class="form-control required" max="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="phone-number">
                                    <?= lang('phone_number') ?>
                                </label>
                                <input id="phone-number" class="form-control" max="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="mobile-number">
                                    <?= lang('mobile_number') ?>
                                </label>
                                <input id="mobile-number" class="form-control" maxlength="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="address">
                                    <?= lang('address') ?>
                                </label>
                                <input id="address" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="city">
                                    <?= lang('city') ?>
                                </label>
                                <input id="city" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="state">
                                    <?= lang('state') ?>
                                </label>
                                <input id="state" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="zip-code">
                                    <?= lang('zip_code') ?>
                                </label>
                                <input id="zip-code" class="form-control" maxlength="64" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="notes">
                                    <?= lang('notes') ?>
                                </label>
                                <textarea id="notes" class="form-control" rows="3" disabled></textarea>
                            </div>

                        </div>
                        <div class="settings col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="username">
                                    <?= lang('username') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="username" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">
                                    <?= lang('password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password" class="form-control required"
                                       maxlength="512" autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password-confirm">
                                    <?= lang('retype_password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password-confirm"
                                       class="form-control required" maxlength="512"
                                       autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="language">
                                    <?= lang('language') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <select id="language" class="form-select required" disabled>
                                    <?php foreach (vars('available_languages') as $available_language): ?>
                                        <option value="<?= $available_language ?>">
                                            <?= ucfirst($available_language) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="timezone">
                                    <?= lang('timezone') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <?php component('timezone_dropdown', [
                                    'attributes' => 'id="timezone" class="form-select required" disabled',
                                    'grouped_timezones' => vars('grouped_timezones'),
                                ]); ?>
                            </div>

                            <div>
                                <label class="form-label mb-3">
                                    <?= lang('options') ?>
                                </label>
                            </div>

                            <div class="border rounded mb-3 p-3">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is-approved">
                                    <label class="form-check-label" for="is-approved">
                                        <?= lang('account_approved') ?>
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="appointment-quota">
                                        <?= lang('appointment_quota') ?>
                                    </label>
                                    <input type="number" id="appointment-quota" class="form-control" min="0" disabled>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifications" disabled>
                                    <label class="form-check-label" for="notifications">
                                        <?= lang('receive_notifications') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">
                                    <?= lang('allowed_services') ?>
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="select-all-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_all') ?>
                                    </button>
                                    <button type="button" id="select-none-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_none') ?>
                                    </button>
                                </div>
                            </div>

                            <div id="alumno-services" class="card card-body border mb-3">
                                <!-- JS -->
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">
                                    <?= lang('allowed_providers') ?>
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="select-all-providers" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_all') ?>
                                    </button>
                                    <button type="button" id="select-none-providers" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_none') ?>
                                    </button>
                                </div>
                            </div>

                            <div id="alumno-providers" class="card card-body border">
                                <!-- JS -->
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/account_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/alumnos_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/alumnos.js') ?>"></script>

<?php end_section('scripts'); ?>
