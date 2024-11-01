<h2 class="woocommerce-order-details__title"><?= __( '결제정보', 'bootpay-with-woocommerce' ) ?></h2>
<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
    <tbody>
    <tr class="woocommerce-table__line-item order_item">
        <th><?= __( '결제 PG명', 'bootpay-with-woocommerce' ) ?></th>
        <td><?= $pg_name ?></td>
    </tr>
    <tr class="woocommerce-table__line-item order_item">
        <th><?= __( '결제 방법', 'bootpay-with-woocommerce' ) ?></th>
        <td><?= $method_name ?></td>
    </tr>
	<?php if( $account ) : ?>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '입금할 은행명', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $bankname ?></td>
        </tr>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '계좌번호', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $account ?></td>
        </tr>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '계좌주', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $holder ?></td>
        </tr>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '입금자명', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $username ?></td>
        </tr>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '입금금액', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $price ?> <?= get_woocommerce_currency_symbol() ?></td>
        </tr>
        <tr class="woocommerce-table__line-item order_item">
            <th><?= __( '입금기한', 'bootpay-with-woocommerce' ) ?></th>
            <td><?= $expire ?> <?= __( '까지 입금해주세요', 'bootpay-with-woocommerce' ) ?></td>
        </tr>
	<?php endif; ?>
    </tbody>
</table>