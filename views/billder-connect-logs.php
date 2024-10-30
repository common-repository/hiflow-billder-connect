
<?php
$logmodel   = new WC_Billder_Connect_Logs();
?>

<div class="billderWrap">
    <header>
        <?php if(WC_BILLDER_CONNECT_PRODUCT === 'BILLDER'): ?>
            <img src="<?= plugins_url('../assets/images/billder_connect.png', __FILE__ ) ?>" id="logo_product"/>
        <?php elseif(WC_BILLDER_CONNECT_PRODUCT === 'HIFLOW'): ?>
            <img src="<?= plugins_url('../assets/images/hiflow_connect.png', __FILE__ ) ?>" id="logo_product"/>
        <?php endif ?>
    </header>
    <div class="billderLogs">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">

                    <form method="post" id="logs_form_table">
                        <?php
                        $logmodel->prepare_items();
                        $logmodel->search_box('search', 'search_id');
                        $logmodel->display();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
