<?php


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('WC_Billder_Connect_Logs')) {

    class WC_Billder_Connect_Logs extends WP_List_Table
    {

        /** Class constructor */
        public function __construct()
        {

            parent::__construct([
                'singular'  => __('Log', WC_BILLDER_CONNECT_DOMAIN), //singular name of the listed records
                'plural'    => __('Logs', WC_BILLDER_CONNECT_DOMAIN), //plural name of the listed records
                'ajax'      => false //should this table support ajax?

            ]);
        }

        public static function registerPage()
        {

            add_action('admin_menu', function () {
                add_submenu_page('woocommerce', WC_Billder_Connect_Settings::getProductName() . ' - logs', WC_Billder_Connect_Settings::getProductName() . ' | logs', 'manage_options', 'billder-connect-logs', function () {
                    include_once(plugin_dir_path(__DIR__) . 'views/billder-connect-logs.php');
                });
            }, 99);
        }


        public static function creatingLogsTable()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_logs";
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
              action tinytext NOT NULL,
              status tinytext NOT NULL,
              method tinytext NOT NULL,
              content text NOT NULL,
              url varchar(55) DEFAULT '' NOT NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        public static function setLog($action, $status, $method, $content, $url)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . "billder_connect_logs";

            $wpdb->insert(
                $table_name,
                array(
                    'time'      => current_time('mysql'),
                    'action'    => $action,
                    'status'    => $status,
                    'content'   => $content,
                    'method'    => $method,
                    'url'       => $url
                )
            );
        }

        public static function get_logs($per_page = 5, $page_number = 1)
        {
            global $wpdb;

            $table_name     = $wpdb->prefix . "billder_connect_logs";
            $per_page       = is_numeric($per_page) ? $per_page : 5;
            $page_number    = is_numeric($page_number) ? $page_number : 1;

            //$sql = "SELECT *, CONCAT(method,' : ',url) as url, DATE_FORMAT(time, '%e/%m/%y - %H:%i:%s') as time FROM ".esc_sql($table_name);
            $sql = "SELECT *, CONCAT(method,' : ',url) as url FROM " . esc_sql($table_name);

            if (isset($_POST['s'])) {
                $sql .= " WHERE content LIKE '%" . esc_sql($_POST['s']) . "%' ";
            }

            if (!empty($_REQUEST['orderby'])) {
                $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
                $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' DESC';
            } else {
                $sql .= ' ORDER BY time DESC';
            }

            $sql .= " LIMIT " . esc_sql($per_page);

            if ($page_number > 1) {
                $sql .= ' OFFSET ' . esc_sql(($page_number - 1) * $per_page);
            }

            $result = $wpdb->get_results($sql, 'ARRAY_A');

            return $result;
        }

        /**
         * Delete a customer record.
         *
         * @param int $id customer ID
         */
        public static function delete_log($id)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_logs";

            $wpdb->delete(
                $table_name,
                ['ID' => $id],
                ['%d']
            );
        }

        /**
         * Returns the count of records in the database.
         *
         * @return null|string
         */
        public static function record_count()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "billder_connect_logs";

            $sql = "SELECT COUNT(*) FROM " . esc_sql($table_name);

            if (isset($_POST['s'])) {
                $sql .= " WHERE content LIKE '%" . esc_sql($_POST['s']) . "%' ";
            }

            return $wpdb->get_var($sql);
        }

        /** Text displayed when no customer data is available */
        public function no_items()
        {
            _e('No logs available.', WC_BILLDER_CONNECT_DOMAIN);
        }

        public function column_status($item)
        {

            $status = '';

            if ((int)$item['status'] >= 200 && (int) $item['status'] <= 300) {
                $status = 'success';
            } elseif ((int)$item['status'] >= 400 && (int) $item['status'] <= 500) {
                $status = 'failure';
            }
            
            return '<span class="tag ' . $status . '">' . ($item['status'] !== '' ? $item['status'] : '-') . '</span>';
        }


        /**
         *  Associative array of columns
         *
         * @return array
         *
         *
         */
        function get_columns()
        {
            $columns = [
                'cb'      => '<input type="checkbox" />',
                'time'      => __('Date', WC_BILLDER_CONNECT_DOMAIN),
                'status'    => __('status', WC_BILLDER_CONNECT_DOMAIN),
                'action'    => __('Action', WC_BILLDER_CONNECT_DOMAIN),
                'content'   => __('Return', WC_BILLDER_CONNECT_DOMAIN),
                'url'       => __('Url', WC_BILLDER_CONNECT_DOMAIN),
            ];

            return $columns;
        }

        /**
         * Columns to make sortable.
         *
         * @return array
         */
        public function get_sortable_columns()
        {
            $sortable_columns = array(
                'time'      => array('time', true),
                'status'    => array('status', true),
                'action'    => array('action', true),
                'content'   => array('content', false),
                'url'       => array('url', false)
            );

            return $sortable_columns;
        }


        /**
         * Method for name column
         *
         * @param array $item an array of DB data
         *
         * @return string
         */
        function column_name($item)
        {

            // create a nonce
            $delete_nonce = wp_create_nonce('delete_log');

            $title = '<strong>' . sanitize_text_field($item['action']) . '</strong>';

            $actions = [
                'delete' => sprintf('<a href="?page=%s&action=%s&log=%s&_wpnonce=%s">' . __('Delete', WC_BILLDER_CONNECT_DOMAIN) . '</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['ID']), $delete_nonce)
            ];

            return $title . $this->row_actions($actions);
        }

        public function column_default($item, $column_name)
        {
            if ($column_name === 'content') {

                if (json_decode($item['content'])) {
                    echo '<a class="more" onClick="jQuery(this).next()[0].classList.toggle(\'active\');">Voir les informations</a>';
                    echo '<pre class="details">';
                    print_r(json_encode(json_decode($item['content']), JSON_PRETTY_PRINT));
                    echo '</pre>';
                } else {
                    return $item[$column_name];
                }

                if ($item['action'] === 'Creating Invoice') {
                }
            } else {
                return $item[$column_name];
            }
        }

        /**
         * Returns an associative array containing the bulk action
         *
         * @return array
         */
        public function get_bulk_actions()
        {
            $actions = [
                'bulk-delete' => __('Delete', WC_BILLDER_CONNECT_DOMAIN)
            ];

            return $actions;
        }

        /**
         * Render the bulk edit checkbox
         *
         * @param array $item
         *
         * @return string
         */
        function column_cb($item)
        {
            return sprintf(
                '<input type="checkbox" name="bulk-delete[]" value="%s" />',
                $item['id']
            );
        }

        /**
         * Handles data query and filter, sorting, and pagination.
         */
        public function prepare_items()
        {

            $this->_column_headers = [
                $this->get_columns(),
                [], // hidden columns
                $this->get_sortable_columns(),
                $this->get_primary_column_name(),
            ];


            /** Process bulk action */
            $this->process_bulk_action();


            $per_page = $this->get_items_per_page('logs_per_page', 25);
            $current_page = $this->get_pagenum();
            $total_items = self::record_count();

            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page' => $per_page
            ]);

            $this->items = self::get_logs($per_page, $current_page);
        }



        public function process_bulk_action()
        {

            //Detect when a bulk action is being triggered...
            if ('delete' === $this->current_action()) {

                // In our file that handles the request, verify the nonce.
                $nonce = esc_attr($_REQUEST['_wpnonce']);

                if (!wp_verify_nonce($nonce, 'delete_log')) {
                    die('Go get a life script kiddies');
                } else {
                    self::delete_log(absint($_GET['log']));

                    wp_redirect(esc_url(add_query_arg()));
                    exit;
                }
            }

            // If the delete bulk action is triggered
            if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
                || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
            ) {

                $delete_ids = esc_sql($_POST['bulk-delete']);

                // loop over the array of record IDs and delete them
                foreach ($delete_ids as $id) {
                    self::delete_log($id);
                }

                wp_redirect(esc_url(add_query_arg()));
                exit;
            }
        }
    }
}
