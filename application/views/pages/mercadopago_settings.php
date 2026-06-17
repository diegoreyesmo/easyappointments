<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="mercadopago-settings-page" class="container backend-page py-3">
    <div id="mercadopago-settings">
        <div class="row">
            <div class="col-sm-3">
                <?php component('settings_nav'); ?>
            </div>
            <div class="col-sm-9">
                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= lang('mercadopago_settings') ?>
                            </h4>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light"><?= lang('payment_configuration') ?></h5>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="mercadopago-enabled"
                                               data-field="mercadopago_enabled"
                                               <?= vars('mercadopago_settings')['mercadopago_enabled'] === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mercadopago-enabled">
                                            <?= lang('mercadopago_enabled') ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" id="mercadopago-sandbox"
                                               data-field="mercadopago_sandbox"
                                               <?= vars('mercadopago_settings')['mercadopago_sandbox'] === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mercadopago-sandbox">
                                            <?= lang('mercadopago_sandbox') ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-access-token">
                                        <?= lang('mercadopago_access_token') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" id="mercadopago-access-token" data-field="mercadopago_access_token"
                                               class="form-control" value="<?= e(vars('mercadopago_settings')['mercadopago_access_token'] ?? '') ?>">
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="mercadopago-access-token">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text text-muted">
                                        <small><?= lang('mercadopago_access_token_hint') ?></small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-public-key">
                                        <?= lang('mercadopago_public_key') ?>
                                    </label>
                                    <input type="text" id="mercadopago-public-key" data-field="mercadopago_public_key"
                                           class="form-control" value="<?= e(vars('mercadopago_settings')['mercadopago_public_key'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-client-id">
                                        <?= lang('mercadopago_client_id') ?>
                                    </label>
                                    <input type="text" id="mercadopago-client-id" data-field="mercadopago_client_id"
                                           class="form-control" value="<?= e(vars('mercadopago_settings')['mercadopago_client_id'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-client-secret">
                                        <?= lang('mercadopago_client_secret') ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" id="mercadopago-client-secret" data-field="mercadopago_client_secret"
                                               class="form-control" value="<?= e(vars('mercadopago_settings')['mercadopago_client_secret'] ?? '') ?>">
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="mercadopago-client-secret">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-currency">
                                        <?= lang('mercadopago_currency') ?>
                                    </label>
                                    <select id="mercadopago-currency" data-field="mercadopago_currency" class="form-select">
                                        <option value="CLP" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? 'CLP') === 'CLP' ? 'selected' : '' ?>>CLP - Chilean Peso</option>
                                        <option value="ARS" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'ARS' ? 'selected' : '' ?>>ARS - Argentine Peso</option>
                                        <option value="BRL" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'BRL' ? 'selected' : '' ?>>BRL - Brazilian Real</option>
                                        <option value="MXN" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'MXN' ? 'selected' : '' ?>>MXN - Mexican Peso</option>
                                        <option value="COP" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'COP' ? 'selected' : '' ?>>COP - Colombian Peso</option>
                                        <option value="UYU" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'UYU' ? 'selected' : '' ?>>UYU - Uruguayan Peso</option>
                                        <option value="PEN" <?= (vars('mercadopago_settings')['mercadopago_currency'] ?? '') === 'PEN' ? 'selected' : '' ?>>PEN - Peruvian Sol</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-statement-descriptor">
                                        <?= lang('mercadopago_statement_descriptor') ?>
                                    </label>
                                    <input type="text" id="mercadopago-statement-descriptor" data-field="mercadopago_statement_descriptor"
                                           class="form-control" maxlength="20"
                                           value="<?= e(vars('mercadopago_settings')['mercadopago_statement_descriptor'] ?? '') ?>">
                                    <div class="form-text text-muted">
                                        <small><?= lang('mercadopago_statement_descriptor_hint') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light"><?= lang('webhook_configuration') ?></h5>

                                <div class="mb-3">
                                    <label class="form-label" for="mercadopago-webhook-url">
                                        <?= lang('payment_webhook_url') ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="mercadopago-webhook-url" class="form-control" readonly
                                               value="<?= e(vars('mercadopago_webhook_url')) ?>">
                                        <button type="button" class="btn btn-outline-secondary" id="copy-webhook-url">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="form-text text-muted">
                                        <small><?= lang('payment_webhook_url_hint') ?></small>
                                    </div>
                                </div>

                                <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <div class="mb-3">
                                    <button type="button" id="test-connection" class="btn btn-outline-primary">
                                        <i class="fas fa-plug me-2"></i>
                                        <?= lang('test_connection') ?>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light"><?= lang('setup_instructions') ?></h5>
                                <ol class="text-muted small">
                                    <li><?= lang('mp_instruction_1') ?></li>
                                    <li><?= lang('mp_instruction_2') ?></li>
                                    <li><?= lang('mp_instruction_3') ?></li>
                                    <li><?= lang('mp_instruction_4') ?></li>
                                    <li><?= lang('mp_instruction_5') ?></li>
                                </ol>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
$(document).ready(function () {
    $('#save-settings').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true);

        const settings = {};
        $('[data-field]').each(function () {
            const $field = $(this);
            const field = $field.data('field');
            let value = $field.val();

            if ($field.is(':checkbox')) {
                value = $field.is(':checked') ? '1' : '0';
            }

            settings[field] = value;
        });

        $.post(App.Utils.Url.siteUrl('mercadopago_settings/save'), {
            csrf_token: vars('csrf_token'),
            mercadopago_settings: settings,
        })
            .done(function (response) {
                App.Utils.Message.show(lang('success'), response.message || lang('settings_saved_successfully'), [
                    { text: lang('close'), click: function (event, messageModal) { messageModal.hide(); } }
                ]);
            })
            .fail(function (jqXHR) {
                const response = jqXHR.responseJSON;
                App.Utils.Message.show(lang('error'), response?.message || lang('error_occurred'), [
                    { text: lang('close'), click: function (event, messageModal) { messageModal.hide(); } }
                ]);
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $('#test-connection').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + '<?= lang('testing') ?>');

        const accessToken = $('#mercadopago-access-token').val();

        $.post(App.Utils.Url.siteUrl('mercadopago_settings/test_connection'), {
            csrf_token: vars('csrf_token'),
            access_token: accessToken,
        })
            .done(function (response) {
                App.Utils.Message.show(lang('success'), response.message || lang('connection_successful'), [
                    { text: lang('close'), click: function (event, messageModal) { messageModal.hide(); } }
                ]);
            })
            .fail(function (jqXHR) {
                const response = jqXHR.responseJSON;
                App.Utils.Message.show(lang('error'), response?.message || lang('connection_failed'), [
                    { text: lang('close'), click: function (event, messageModal) { messageModal.hide(); } }
                ]);
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="fas fa-plug me-2"></i><?= lang('test_connection') ?>');
            });
    });

    $('.toggle-password').on('click', function () {
        const $target = $('#' + $(this).data('target'));
        const $icon = $(this).find('i');
        if ($target.attr('type') === 'password') {
            $target.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $target.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#copy-webhook-url').on('click', function () {
        const $input = $('#mercadopago-webhook-url');
        $input.select();
        navigator.clipboard.writeText($input.val()).then(() => {
            App.Utils.Message.show(lang('success'), lang('copied_to_clipboard'), [
                { text: lang('close'), click: function (event, messageModal) { messageModal.hide(); } }
            ]);
        });
    });
});
</script>

<?php end_section('scripts'); ?>
