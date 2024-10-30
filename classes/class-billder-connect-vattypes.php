<?php


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Billder_Connect_Vattypes')) {

    class WC_Billder_Connect_Vattypes
    {

        public static function registerVatTable()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_vattypes";
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              id_external int NOT NULL,
              percentage DECIMAL(10,2),
              id_country varchar(2) NOT NULL,
              default_vat BOOLEAN NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        public static function updateVatTypes()
        {
            // set vat table
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_vattypes";
            $vattypes = $wpdb->query($wpdb->prepare("SELECT * FROM $table_name"));

            if (empty($vattypes)) {
                $connector = new WC_Billder_Connect_Gateway();
                $types = $connector->getVatTypes();
                if (!empty($types)) {
                    foreach ($types as $k => $type) {

                        $wpdb->insert(
                            $table_name,
                            array(
                                'id_external' => $type->id,
                                'percentage' => $type->percentage,
                                'id_country' => $type->id_country,
                                'default_vat' => $type->isdefault
                            )
                        );
                    }
                }
            }
        }

        public function getVatTypeId($pct = null, $country = null, $log = false)
        {

            if ($log) {
                WC_Billder_Connect_Logs::setLog('WC vat type log', '-', '-', json_encode(array('pct' => $pct, 'country' => $country)), '/');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_vattypes";

            if ($pct !== null && !empty($country)) {

                if ($pct === 0) {

                    if ($this->isUECountry($country)) {
                        return 1;
                    } else {
                        return 2;
                    }
                } else {


                    $sql = "SELECT * FROM " . esc_sql($table_name) . " WHERE percentage = " . esc_sql($pct) . " AND id_country = '" . esc_sql($country) . "';";
                    $result =  $wpdb->get_row($wpdb->prepare($sql));

                    // pct and country association mismatch with billder, automatically return the default vat
                    if (empty($result)) {
                        $sql = "SELECT * FROM " . esc_sql($table_name) . " WHERE  default_vat = 1 AND id_country = '" . esc_sql($country) . "';";
                        $result = $wpdb->get_row($wpdb->prepare($sql));
                    }
                }
            } else {

                $shop_country = !empty($country) ? $country : wc_get_base_location()['country'];
                $sql = "SELECT * FROM " . esc_sql($table_name) . " WHERE  default_vat = 1 AND id_country = '" . esc_sql($shop_country) . "';";

                $result = $wpdb->get_row($wpdb->prepare($sql));
            }

            if (!empty($result)) {
                return $result->id_external;
            } else {
                return 0;
            }
        }


        public function isUECountry($id_country)
        {
            return in_array(strtoupper($id_country), array(
                'BE',
                'B',
                'BG',
                'CZ',
                'DK',
                'DE',
                'D',
                'EE',
                'IE',
                'IRL',
                'EL',
                'EL',
                'ES',
                'E',
                'FR',
                'F',
                'HR',
                'IT',
                'I',
                'CY',
                'LV',
                'LT',
                'LU',
                'L',
                'HU',
                'MT',
                'NL',
                'NL',
                'AT',
                'A',
                'PL',
                'PT',
                'P',
                'RO',
                'SI',
                'SK',
                'FI',
                'FIN',
                'SE',
                'S',
                'UK'
            ));
        }
    }
}
