<?php
    $order = wc_get_order(WC_BILLDER_CONNECT_ORDER_ID);
?>


<?= get_custom_logo() ?>
<h3><?= __('Hello',WC_BILLDER_CONNECT_DOMAIN) ?> <?= $order->get_formatted_billing_full_name() ?></h3>
<p><?= __('Here is your invoice for your order',WC_BILLDER_CONNECT_DOMAIN) ?> #<?= $order->get_id() ?></p>
<a href="<?= WC_BILLDER_CONNECT_PDF_INVOICE_URL ?>" style="background:#bd254b;padding:10px 30px;color:#FFF;text-decoration: none;border-radius:2em;margin:2em 0;text-transform:uppercase;display:inline-block;"><?= __('View your invoice',WC_BILLDER_CONNECT_DOMAIN) ?></a>
<p><?= __('Best regards', WC_BILLDER_CONNECT_DOMAIN) ?>,<br /><?= get_bloginfo('name') ?></p>