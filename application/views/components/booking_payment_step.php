<?php
/**
 * Local variables.
 *
 * @var float $service_price
 * @var string $service_currency
 * @var string $service_name
 */
?>

<div id="wizard-frame-4" class="wizard-frame p-3 p-md-4" style="display:none;">
    <div class="frame-container py-3" style="min-height: 500px;">
        <h2 class="frame-title fw-light text-center mb-4 text-muted"><?= lang('payment_step') ?></h2>

        <div class="row frame-content m-auto pt-md-4 mb-4" style="max-width: 630px;">
            <div class="col-12 text-center">
                <p class="fs-5 text-muted mb-4"><?= lang('complete_payment_to_confirm') ?></p>

                <div class="card mx-auto mb-4" style="max-width: 400px;">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= e($service_name ?? lang('appointment')) ?></h5>
                        <p class="card-text fs-3 fw-bold text-primary my-3">
                            <?= lang('payment_amount') ?>:
                            <span id="payment-amount-display"><?= e($service_currency === 'CLP' ? '$' : '') ?><?= number_format($service_price, $service_currency === 'CLP' ? 0 : 2, '.', ',') ?></span>
                            <?= e($service_currency) ?>
                        </p>
                        <hr>
                        <div id="payment-status" class="payment-status mb-3" style="display:none;">
                        </div>
                        <button type="button" id="pay-with-mercadopago" class="btn btn-primary btn-lg w-100">
                            <img src="https://img.mp.akamai.com.ar/static/imgs/checkout/checkout-blue-lg.png"
                                 alt="MercadoPago" style="max-height: 40px;"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<?= lang('pay_with_mercadopago') ?>';">
                        </button>
                        <p class="small text-muted mt-3 mb-0">
                            <i class="fas fa-lock me-1"></i> <?= lang('payment_secure_message') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="command-buttons text-center my-3 mx-auto d-md-flex justify-content-md-between">
        <button type="button" id="button-back-4" class="btn button-back btn-outline-secondary" style="min-width: 120px; margin-right: 10px;"
                data-step_index="4">
            <i class="fas fa-chevron-left me-2"></i>
            <?= lang('back') ?>
        </button>
        <button type="button" id="button-next-4" class="btn button-next btn-dark" style="min-width: 120px; display:none;"
                data-step_index="4">
            <?= lang('next') ?>
            <i class="fas fa-chevron-right ms-2"></i>
        </button>
    </div>
</div>
