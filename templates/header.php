<h3><?= $title ?></h3>
<?php if( ! $this->is_valid_currency() ) : ?>
    <div class="inline error"><?= $this->msg ?></div>
<?php else : ?>
    <div class="inline">
        <button type="button" class="button woocommerce-save-button" id="bootpay-test-btn">결제 테스트</button>
    </div>
    <table class="form-table"><?= $this->generate_settings_html() ?></table>
<?php endif; ?>
