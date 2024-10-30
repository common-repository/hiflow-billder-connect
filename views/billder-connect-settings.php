<?php

$settings = new WC_Billder_Connect_Settings();

if(isset($_POST) && !empty($_POST)){
    $direct_email = isset($_POST['invoice_email'])?true:false;
    $settings->setSettings(array('invoice_email' => $direct_email));
}

$credentials        = $settings->getCredentials();
$billderSettings    = $settings->getSettings();
$apikey             = isset($credentials['apikey'])?$credentials['apikey']:'';
$url                = isset($credentials['account'])?$credentials['account']:'';
$credentialsReturn  = false;
$config             = false;

if($credentials){
    $config = $settings->getConfig();

}elseif (isset($_POST['apikey'])){
        $apikey             = sanitize_text_field($_POST['apikey']);
        $credentialsReturn  = $settings->checkCredentials($apikey);

        // Check and change this when apikey will be deployed on billder
        if($credentialsReturn && $credentialsReturn->success && $credentialsReturn->permissions->invoices->create){
            $settings->setCredentials($apikey);
            $credentials = $settings->getCredentials();
            $url  = isset($credentials['account'])?$credentials['account']:'';

            $config = $settings->getConfig();

        }
}


if (isset($_POST['logout'])){
    $settings->unsetCredentials();
    $credentials          = $settings->getCredentials();
    $credentialsReturn    = false;
    $url                  = isset($credentials['account'])?$credentials['account']:'';
    $apikey               = isset($credentials['token'])?'**********':'';
}

?>

<div class="billderWrap">
    <header>
        <?php if(WC_BILLDER_CONNECT_PRODUCT === 'BILLDER'): ?>
            <img src="<?= plugins_url('../assets/images/billder_connect.png', __FILE__ ) ?>" id="logo_product"/>
        <?php elseif(WC_BILLDER_CONNECT_PRODUCT === 'HIFLOW'): ?>
            <img src="<?= plugins_url('../assets/images/hiflow_connect.png', __FILE__ ) ?>" id="logo_product"/>
        <?php endif ?>
    </header>
    <?php if($credentialsReturn): ?>
        <?php if(isset($credentialsReturn->success) && $credentialsReturn->success): ?>
            <?php if($credentialsReturn->permissions->invoices->create): ?>
                <div class="info success">
                    <?= sprintf(__('Successfully connected to your %s account!',WC_BILLDER_CONNECT_DOMAIN),WC_BILLDER_CONNECT_PRODUCT); ?>
                </div>
                <?php else: ?>
                <div class="info failure">
                    <?= __('You don\'t have the permission to create invoices. Please log-in with a proper account.',WC_BILLDER_CONNECT_PRODUCT); ?>
                </div>
            <?php endif ?>
        <?php else: ?>
            <div class="info failure">
                <?= sprintf(__('Oops! Unable to connect your %s account. Check your API key before continue.',WC_BILLDER_CONNECT_DOMAIN),ucfirst(strtolower(WC_BILLDER_CONNECT_PRODUCT))); ?>
            </div>
        <?php endif ?>
    <?php endif ?>
    <?php if($credentials): ?>
        <?php if($config && $config->success): ?>
            <?php if($config->config->account && $config->config->account->maxinvoices === 3): ?>
                <div class="info limited">
                    <?= __('Your account is currently set as free. You have a limited account and cannot send more than 3 invoices per month.'); ?>
                </div>
            <?php endif ?>
        <?php endif ?>
        <div class="connected">
            <div class="line">
                <h4><?= __('Account',WC_BILLDER_CONNECT_DOMAIN) ?></h4>
                <div class="info_line">
                    <span><?= esc_url_raw($url) ?>.<?= strtolower(WC_BILLDER_CONNECT_PRODUCT) ?>.net</span>
                    <span class="link_ico">
                        <img src="<?= plugins_url('../assets/images/link.png', __FILE__ ) ?>" alt="linked" />
                    </span>
                </div>
            </div>
            <div class="line">
                <h4><?= __('Api Key',WC_BILLDER_CONNECT_DOMAIN) ?></h4>
                <div class="info_line">
                    <span>[...]<?= substr( esc_html($apikey),-16) ?></span>
                </div>
            </div>
            <div class="line">
                <form class="settings" action="" method="post">
                    <h4><?= __('Settings',WC_BILLDER_CONNECT_DOMAIN) ?></h4>
                    <div class="info_line">
                        <input type="hidden" name="settings" />
                        <input type="checkbox" name="invoice_email" id="invoice_email" <?= $billderSettings['invoice_email']?'checked':'' ?>/>
                        <label for="invoice_email"><?= __('Send invoice after finished payment',WC_BILLDER_CONNECT_DOMAIN) ?>
                        </label>
                    </span>
                    </div>
                    <button class="button-primary" type="submit"><?= __('update settings',WC_BILLDER_CONNECT_DOMAIN) ?></button>
                </form>
            </div>
            <form action="" method="post">
                <input type="hidden" name="logout" value="true" />
                <button class="logout" type="submit"><?= __('login with an other account',WC_BILLDER_CONNECT_DOMAIN) ?></button>
            </form>
        </div>
        <a href="<?= admin_url() ?>/admin.php?page=billder-connect-logs" id="show_logs"><?= __('See logs',WC_BILLDER_CONNECT_DOMAIN) ?></a>
        <?php else: ?>
        <form method="POST" action="">
            <div class="row start">
                <span class="step">1</span>
                <p><?= sprintf(__('Connect with your %s account',WC_BILLDER_CONNECT_DOMAIN) ,ucfirst(strtolower(WC_BILLDER_CONNECT_PRODUCT)))?></p>
                <span class="register"><?= sprintf(__("Don't have any %s account yet?",WC_BILLDER_CONNECT_DOMAIN),ucfirst(strtolower(WC_BILLDER_CONNECT_PRODUCT))) ?></span>
                <a href="https://www.<?= strtolower(WC_BILLDER_CONNECT_PRODUCT) ?>.net" target="_blank"><?= __('Create my account',WC_BILLDER_CONNECT_DOMAIN) ?></a>
            </div>
            <div class="row">
                <label for="login"><?= __('Api Key',WC_BILLDER_CONNECT_DOMAIN) ?></label>
                <input id="login" name="apikey" type="text" placeholder="<?= __('Api Key',WC_BILLDER_CONNECT_DOMAIN) ?>"  value="<?= $apikey ?>" />
                <div class="guide"><?=__("Don't have and API key ?", WC_BILLDER_CONNECT_DOMAIN)?> <a target="_blank" href="https://doc.brainmade.io/doc/creer-une-cle-dapi/"><?= __('Follow this guide', WC_BILLDER_CONNECT_DOMAIN)?></a></div>

            </div>
            <div class="row">
                <button type="submit"><?=  sprintf(__('Connect with %s',WC_BILLDER_CONNECT_DOMAIN),ucfirst(strtolower(WC_BILLDER_CONNECT_PRODUCT)) )?></button>
            </div>
        </form>
    <?php endif ?>
</div>
