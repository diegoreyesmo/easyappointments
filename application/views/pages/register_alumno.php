<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div class="text-center mb-4">
    <img src="<?= asset_url('assets/img/logo.png') ?>" 
         alt="AgendaRRF" class="shadow mb-3" width="72" height="72">
    <h4 class="text-primary fw-semibold mb-1"><?= lang('register_as_student') ?></h4>
    <p class="small mb-0"><?= lang('customer_information') ?></p>
</div>

<div class="alert d-none"></div>

<form id="register-alumno-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="first-name" class="form-label fw-medium">
                <?= lang('first_name') ?>
                <span class="text-danger">*</span>
            </label>
            <input type="text" id="first-name" class="form-control" required maxlength="256"/>
        </div>

        <div class="col-md-6 mb-3">
            <label for="last-name" class="form-label fw-medium">
                <?= lang('last_name') ?>
                <span class="text-danger">*</span>
            </label>
            <input type="text" id="last-name" class="form-control" required maxlength="512"/>
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-medium">
            <?= lang('email') ?>
            <span class="text-danger">*</span>
        </label>
        <input type="email" id="email" class="form-control" required maxlength="512"/>
    </div>

    <div class="mb-3">
        <label for="phone-number" class="form-label fw-medium">
            <?= lang('phone_number') ?>
        </label>
        <input type="text" id="phone-number" class="form-control" maxlength="128"/>
    </div>

    <div class="mb-3">
        <label for="username" class="form-label fw-medium">
            <?= lang('username') ?>
            <span class="text-danger">*</span>
        </label>
        <input type="text" id="username" class="form-control" required maxlength="256"/>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-medium">
            <?= lang('password') ?>
            <span class="text-danger">*</span>
        </label>
        <input type="password" id="password" class="form-control" required maxlength="512" autocomplete="new-password"/>
    </div>

    <div class="mb-4">
        <label for="password-confirm" class="form-label fw-medium">
            <?= lang('retype_password') ?>
            <span class="text-danger">*</span>
        </label>
        <input type="password" id="password-confirm" class="form-control" required maxlength="512" autocomplete="new-password"/>
    </div>

    <?php if (vars('require_captcha')): ?>
        <?php if (vars('altcha_enabled') === '1'): ?>
            <div class="mb-4">
                <div id="altcha-widget" class="altcha-widget"></div>
                <input type="hidden" id="altcha-payload" value="">
                <span id="altcha-hint" class="help-block text-danger small" style="opacity:0">&nbsp;</span>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <label class="captcha-title form-label fw-medium" for="captcha-text">
                    CAPTCHA
                    <button type="button" class="btn btn-link text-dark text-decoration-none py-0 px-1">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </label>
                <img class="captcha-image d-block mb-2 rounded" src="<?= site_url('captcha') ?>" alt="CAPTCHA">
                <input id="captcha-text" class="captcha-text form-control" type="text" placeholder="<?= lang('enter_captcha_here') ?>"/>
                <span id="captcha-hint" class="help-block text-danger small" style="opacity:0">&nbsp;</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="d-grid gap-2 mb-3">
        <button type="submit" id="register" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>
            <?= lang('register_as_student') ?>
        </button>
    </div>

    <div class="text-center">
        <a href="<?= site_url('login') ?>" class="text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i>
            <?= lang('go_to_login') ?>
        </a>
    </div>
</form>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/register_alumno_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/register_alumno.js') ?>"></script>

<?php end_section('scripts'); ?>
