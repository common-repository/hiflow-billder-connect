<?php


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Billder_Connect_Order')) {

    class WC_Billder_Connect_Order
    {
        public function __construct()
        {
        }

        public static function addWidgetInAdminOrder()
        {
            add_action('woocommerce_admin_order_data_after_order_details', 'my_custom_order_manipulation_function');
            function my_custom_order_manipulation_function($order)
            {
                define('WC_BILLDER_CONNECT_ORDER_ID', $order->get_id());
                include_once(__DIR__ . '/../views/billder-connect-order-data.php');
            }
        }


        public static function handleValidPayment()
        {
            add_action('woocommerce_payment_complete', 'WC_payment_complete');
            function WC_payment_complete($order_id)
            {
                $order = wc_get_order($order_id);
                $connector = new WC_Billder_Connect_Gateway();
                $settings = new WC_Billder_Connect_Settings();

                $vatModel = new WC_Billder_Connect_Vattypes();



                $billderSettings = $settings->getSettings();

                // check if email is already linked to a billder account

                global $wpdb;

                $table_name   = $wpdb->prefix . "billder_connect_account_links";

                $billder_link = $wpdb->get_row($wpdb->prepare("SELECT id_billder FROM $table_name WHERE email LIKE %s;", array($order->get_billing_email())));

                $company = trim($order->get_billing_company());
                $name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

                $userParams   = array(
                    'name'       => $company !== '' ? $company : $name,
                    'email'      => $order->get_billing_email(),
                    'adress'     => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                    'cp'         => $order->get_billing_postcode(),
                    'city'       => $order->get_billing_city(),
                    'id_country' => $order->get_billing_country(),
                    'phone'      => $order->get_billing_phone(),
                    'is_company' => $company !== '' ? true : false
                );


                $isset_vat = $order->get_meta('_vat_number_is_valid');

                if ($isset_vat) {
                    $userParams['vat'] = $order->get_meta('_vat_number');
                }

                if ($billder_link && $billder_link->id_billder > 0) {
                    $billder_user_id = $billder_link->id_billder;
                    $connector->updateCustomer($billder_user_id, json_encode($userParams));
                } else {
                    $billder_user_id = $connector->createCustomer(json_encode($userParams));
                    $wpdb->insert(
                        $table_name,
                        array(
                            'id_billder'    => $billder_user_id,
                            'email'         => $order->get_billing_email()
                        )
                    );
                }

                add_post_meta($order_id, 'Billder_user_id', $billder_user_id);

                // CREATING INVOICE

                $items = $order->get_items();

                $taxes = $order->get_items('tax');



                $params = array(
                    'id_customer'       => $billder_user_id,
                    'invoice_date'      => date('Y-m-d'),
                    'invoice_duedate'   => date('Y-m-d'),
                    'id_status'         => 50,
                    'entries'           => array()
                );

                $amountOfItems = 0;

                foreach ($items as $id => $item) {
                    $amountOfItems += $item->get_quantity();
                }

                foreach ($items as $id => $item) {
                    // virtual product = billing country else shop country
                    $country =  wc_get_base_location()['country'];

                    $product = $item->get_product();

                    if ($product->get_virtual()) {
                        $country = $order->get_billing_country();
                    }

                    $tax_rates = WC_Tax::get_rates($product->get_tax_class());
                    if (!empty($tax_rates)) {
                        $tax_rate = reset($tax_rates);
                    }

                    if ($order->get_meta('is_vat_exempt') === 'yes') {
                        $tax_rate['rate'] = 0;
                    }

                    $unitary_price = wc_get_price_excluding_tax($product);

                    $idVat = $vatModel->getVatTypeId($tax_rate['rate'] ? ($tax_rate['rate']) : null, $country);


                    $productLine = array(
                        'label'         => $item->get_name(),
                        'quantity'      => $item->get_quantity(),
                        'unitary_product_price' => wc_get_price_excluding_tax($product),
                        'unitary_price' => floatval(wc_get_price_excluding_tax($product)),
                        //'unitary_price' => ($item->get_total()),
                        'id_vat_type'   => $idVat,
                        'id_unit'       => 4,
                        'virtual'       => $product->get_virtual(),
                        "vat_country"   => $country,
                        "rate"          => $tax_rate['rate'],
                        "total" => $item->get_total()
                    );



                    // loop through order items "coupon"
                    foreach ($order->get_items('coupon') as  $couponDatas) {
                        // Get the coupon array data in an unprotected array
                        $coupon = new WC_Coupon($couponDatas->get_code());
                        $data = $coupon->get_data();
                        $type = $coupon->get_discount_type();

                        $unitary_price = $productLine['unitary_price'];
                        $amount = $data['amount'];

                        if ($type === 'percent') {
                            if ($amount > 0) {
                                $unitary_price = $unitary_price - ($unitary_price / 100 * $amount);
                            }
                        } else if ($type === 'fixed_cart') {
                            if ($amountOfItems > 0) {
                                $unitary_price = $unitary_price - ($amount / $amountOfItems);
                            }
                        } else if ($type === 'fixed_product') {
                            $unitary_price = $unitary_price - $amount;
                        } else if ($type === 'percent_product') {
                            $unitary_price = $unitary_price - ($unitary_price / 100 * $amount);
                        }


                        $productLine['unitary_price'] = $unitary_price;
                    }

                    $productLine['unitary_price'] = number_format($productLine['unitary_price'], 3, '.', '');
                    $params['entries'][] = $productLine;
                }

                // add shipping method
                $params['entries'][] = array(
                    'label'         => "Livraison : " . $order->get_shipping_method(),
                    'quantity'      => 1,
                    'unitary_price' => $order->get_shipping_total(),
                    'id_vat_type'   => $vatModel->getVatTypeId(null,  wc_get_base_location()['country']), // make sure to fall to default rate for shop country
                    'id_unit'       => 4
                );

                $invoiceDatas = array(
                    'order_id' => $order_id,
                    'tax_rate' => $tax_rate['rate'],
                    'tax_rates' => $tax_rates,
                    'billing_country' => $order->get_billing_country(),
                    'vat_id' => $idVat
                );

                WC_Billder_Connect_Logs::setLog('WC Invoice datas', '-', '-', json_encode($invoiceDatas), '/');

                $invoice = $connector->createInvoice(json_encode($params));
                add_post_meta($order_id, 'billder_invoice', $invoice);


                // GENERATING PDF INVOICE

                if ($billderSettings['invoice_email']) {
                    $pdf = $connector->generateInvoicePdf($invoice);
                    $attachments[] = $pdf['dir'];

                    define('WC_BILLDER_CONNECT_ORDER_ID', $order_id);
                    define('WC_BILLDER_CONNECT_PDF_INVOICE_URL', $pdf['url']);

                    ob_start();
                    include_once(__DIR__ . '/../views/templates/invoice.php');
                    $contentInvoiceMail = ob_get_contents();
                    ob_end_clean();

                    $to = $userParams['email'];
                    $subject = 'Invoice for your order #' . $order_id;
                    $body = $contentInvoiceMail;
                    $headers = array('Content-Type: text/html; charset=UTF-8');

                    try {
                        wp_mail($to, $subject, $body, $headers, $attachments);
                        WC_Billder_Connect_Logs::setLog('sending order email', '-', 'POST', 'email send to ' . $to, '/');
                    } catch (Exception $e) {
                        WC_Billder_Connect_Logs::setLog('sending order email', 500, 'POST', $e->getMessage(), '/');
                    }
                }
            }
        }

        public static function creatingBillderAccountAssociationTable()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_account_links";
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              id_billder mediumint(9) NOT NULL,
              email tinytext NOT NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }


        public static function generateManualBillderDatas($order_id)
        {

            $order = wc_get_order($order_id);
            $connector = new WC_Billder_Connect_Gateway();
            $settings = new WC_Billder_Connect_Settings();
            $vatModel = new WC_Billder_Connect_Vattypes();



            $billderSettings = $settings->getSettings();

            // check if email is already linked to a billder account

            global $wpdb;

            $table_name   = $wpdb->prefix . "billder_connect_account_links";

            $billder_link = $wpdb->get_row($wpdb->prepare("SELECT id_billder FROM $table_name WHERE email LIKE %s;", array($order->get_billing_email())));

            $company = trim($order->get_billing_company());
            $name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

            $userParams   = array(
                'name'       => $company !== '' ? $company : $name,
                'email'      => $order->get_billing_email(),
                'adress'     => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                'cp'         => $order->get_billing_postcode(),
                'city'       => $order->get_billing_city(),
                'id_country' => $order->get_billing_country(),
                'phone'      => $order->get_billing_phone(),
                'is_company' => $company !== '' ? true : false
            );


            $isset_vat = $order->get_meta('_vat_number_is_valid');

            if ($isset_vat) {
                $userParams['vat'] = $order->get_meta('_vat_number');
            }

            if ($billder_link && $billder_link->id_billder > 0) {
                $billder_user_id = $billder_link->id_billder;
                $connector->updateCustomer($billder_user_id, json_encode($userParams));
            } else {
                $billder_user_id = $connector->createCustomer(json_encode($userParams));
                $wpdb->insert(
                    $table_name,
                    array(
                        'id_billder'    => $billder_user_id,
                        'email'         => $order->get_billing_email()
                    )
                );
            }

            add_post_meta($order_id, 'Billder_user_id', $billder_user_id);

            // CREATING INVOICE

            $items = $order->get_items();

            $taxes = $order->get_items('tax');



            $params = array(
                'id_customer'       => $billder_user_id,
                'invoice_date'      => date('Y-m-d'),
                'invoice_duedate'   => date('Y-m-d'),
                'id_status'         => 50,
                'entries'           => array()
            );

            $amountOfItems = 0;

            foreach ($items as $id => $item) {
                $amountOfItems += $item->get_quantity();
            }

            foreach ($items as $id => $item) {
                // virtual product = billing country else shop country
                $country =  wc_get_base_location()['country'];

                $product = $item->get_product();

                if ($product->get_virtual()) {
                    $country = $order->get_billing_country();
                }

                $tax_rates = WC_Tax::get_rates($product->get_tax_class());
                if (!empty($tax_rates)) {
                    $tax_rate = reset($tax_rates);
                }

                if ($order->get_meta('is_vat_exempt') === 'yes') {
                    $tax_rate['rate'] = 0;
                }

                $unitary_price = wc_get_price_excluding_tax($product);

                $idVat = $vatModel->getVatTypeId($tax_rate['rate'] ? ($tax_rate['rate']) : null, $country);


                $productLine = array(
                    'label'         => $item->get_name(),
                    'quantity'      => $item->get_quantity(),
                    'unitary_product_price' => wc_get_price_excluding_tax($product),
                    'unitary_price' => floatval(wc_get_price_excluding_tax($product)),
                    //'unitary_price' => ($item->get_total()),
                    'id_vat_type'   => $idVat,
                    'id_unit'       => 4,
                    'virtual'       => $product->get_virtual(),
                    "vat_country"   => $country,
                    "rate"          => $tax_rate['rate'],
                    "total" => $item->get_total()
                );


                // loop through order items "coupon"
                foreach ($order->get_items('coupon') as  $couponDatas) {
                    // Get the coupon array data in an unprotected array
                    $coupon = new WC_Coupon($couponDatas->get_code());
                    $data = $coupon->get_data();
                    $type = $coupon->get_discount_type();

                    $unitary_price = $productLine['unitary_price'];
                    $amount = $data['amount'];

                    if ($type === 'percent') {
                        if ($amount > 0) {
                            $unitary_price = $unitary_price - ($unitary_price / 100 * $amount);
                        }
                    } else if ($type === 'fixed_cart') {
                        if ($amountOfItems > 0) {
                            $unitary_price = $unitary_price - ($amount / $amountOfItems);
                        }
                    } else if ($type === 'fixed_product') {
                        $unitary_price = $unitary_price - $amount;
                    } else if ($type === 'percent_product') {
                        $unitary_price = $unitary_price - ($unitary_price / 100 * $amount);
                    }


                    $productLine['unitary_price'] = $unitary_price;
                }

                $productLine['unitary_price'] = number_format($productLine['unitary_price'], 3, '.', '');

                $params['entries'][] = $productLine;
            }

            // add shipping method
            $params['entries'][] = array(
                'label'         => "Livraison : " . $order->get_shipping_method(),
                'quantity'      => 1,
                'unitary_price' => $order->get_shipping_total(),
                'id_vat_type'   => $vatModel->getVatTypeId(null,  wc_get_base_location()['country'], true), // make sure to fall to default rate for shop country
                'id_unit'       => 4
            );


            $invoiceDatas = array(
                'order_id' => $order_id,
                'tax_rate' => $tax_rate['rate'],
                'tax_rates' => $tax_rates,
                'billing_country' => $order->get_billing_country(),
                'vat_id' => $idVat
            );

            WC_Billder_Connect_Logs::setLog('WC Invoice datas', '-', '-', json_encode($invoiceDatas), '/');

            $invoice = $connector->createInvoice(json_encode($params));
            add_post_meta($order_id, 'billder_invoice', $invoice);


            // GENERATING PDF INVOICE

            if ($billderSettings['invoice_email']) {
                $pdf = $connector->generateInvoicePdf($invoice);
                $attachments[] = $pdf['dir'];

                define('WC_BILLDER_CONNECT_ORDER_ID', $order_id);
                define('WC_BILLDER_CONNECT_PDF_INVOICE_URL', $pdf['url']);

                ob_start();
                include_once(__DIR__ . '/../views/templates/invoice.php');
                $contentInvoiceMail = ob_get_contents();
                ob_end_clean();

                $to = $userParams['email'];
                $subject = 'Invoice for your order #' . $order_id;
                $body = $contentInvoiceMail;
                $headers = array('Content-Type: text/html; charset=UTF-8');

                try {
                    wp_mail($to, $subject, $body, $headers, $attachments);
                    WC_Billder_Connect_Logs::setLog('sending order email', '-', 'POST', 'email send to ' . $to, '/');
                } catch (Exception $e) {
                    WC_Billder_Connect_Logs::setLog('sending order email', 500, 'POST', $e->getMessage(), '/');
                }
            }
        }
    }
}
