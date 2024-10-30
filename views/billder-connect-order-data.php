
<?php
    $settings = new WC_Billder_Connect_Settings();

    $order              = wc_get_order(WC_BILLDER_CONNECT_ORDER_ID);
    $user               = $order->get_user();
    $pdf                = "";
    $credentials        = $settings->getCredentials();

    $billder_invoice_id = get_post_meta(WC_BILLDER_CONNECT_ORDER_ID,'billder_invoice',true);
    $billder_user_id    = get_post_meta(WC_BILLDER_CONNECT_ORDER_ID,'Billder_user_id',true);

    $url                = isset($credentials['account'])?$credentials['account']:'';

    $connector          = new WC_Billder_Connect_Gateway();

    if($billder_invoice_id){
        $pdf = $connector->generateInvoicePdf($billder_invoice_id);
    }


    if(isset($_REQUEST['regen_billder_datas'])){
        WC_Billder_Connect_Order::generateManualBillderDatas(WC_BILLDER_CONNECT_ORDER_ID);
    }


?>

<div class="order-billder">


    <h3><?= sprintf(__('%s informations',WC_BILLDER_CONNECT_DOMAIN),WC_BILLDER_CONNECT_PRODUCT) ?></h3>
    <?php if($billder_user_id && $url != ''): ?>
        <a target="_blank" href="<?= esc_url_raw($url) ?>.<?= strtolower(WC_BILLDER_CONNECT_PRODUCT) ?>.net/ui/customer/<?= $billder_user_id ?>/overview"><?= __('customer',WC_BILLDER_CONNECT_DOMAIN) ?> #<?= $billder_user_id ?></a>
    <?php endif; ?>
    <?php if($billder_invoice_id): ?>
        <div class="invoice">
            <span><?= __('An invoice is available for this order.',WC_BILLDER_CONNECT_DOMAIN) ?></span>
            <a target="_blank" href="<?= esc_url_raw($pdf['url']); ?>" class="button primary"><?= __('View the invoice',WC_BILLDER_CONNECT_DOMAIN) ?></a>
        </div>
    <?php endif ?>

    <form action="#" method="get">
        <input type="hidden" name="regen_billder_datas" value="1" />
        <input type="hidden" name="post" value="<?= $_GET['post']; ?>" />
        <input type="hidden" name="action" value="<?= $_GET['action']; ?>" />
        <input type="submit" value="Générer les données" />
    </form>
</div>
