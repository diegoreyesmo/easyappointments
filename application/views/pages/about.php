<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="about-page" class="container backend-page py-3">
    <div id="about" class="col-lg-8 offset-lg-2">

        <div class="text-center my-5">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="AgendaRRF Logo" class="mb-5">

            <h3>
                AgendaRRF
            </h3>
            <h6 class="text-primary">
                Sistema de Agendamiento
            </h6>
        </div>

        <p class="mb-5">
            <?= lang('about_app_info') ?>
        </p>

    </div>
</div>

<?php end_section('content'); ?>

