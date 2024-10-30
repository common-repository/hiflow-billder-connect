<?php


if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Billder_Connect_Gateway')) {

    class WC_Billder_Connect_Gateway
    {

        protected $url      = '';
        protected $token    = '';

        public function __construct()
        {

            $credentials        = get_option('billder-connect');

            if (isset($credentials['apikey'])) {
                $payload        = self::parseJWT($credentials['apikey']);
                $this->url      = 'https://' . $payload['account'] . '.hiflow.net';
                $this->token    = $credentials['apikey'];
            }
        }

        protected function makeRequest($method, $path, $action, $datas = array(), $return = 'object', $log = true, $token = '')
        {

            $args = array(
                'headers' => array(
                    'Authorization' => ' Bearer ' . $this->token,
                    'Content-Type'   => 'application/json',
                ),
                'body' => json_encode($datas),
                'method' => $method
            );


            if ($token !== '') {
                $payload = self::parseJWT($token);
                $account = isset($payload['account']) ? $payload['account'] : null;
                $url   = 'https://' . $account . '.hiflow.net/rest' . $path;

                $args['headers']['Authorization'] = ' Bearer ' . $token;
            } else {
                $url    = $this->url . '/rest' . $path;
            }

            if ($log) {
                //WC_Billder_Connect_Logs::setLog('REQUEST API', '-', $method, $url, $path);
            }

            $args = !empty($args) ? $args : array();

            if (isset($args['body']) && empty($args['body'])) {
                unset($args['body']);
            } elseif (gettype($args['body'])) {
                $args['body'] = json_decode($args['body']);
            }

            $response = wp_remote_request($url, $args);

            $body      = wp_remote_retrieve_body($response);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($log) {
                if ($return === 'object') {
                    WC_Billder_Connect_Logs::setLog($action, $http_code, $method, json_encode($datas), $path);
                } else {
                    $returnResponse = explode('/', json_decode($body));
                    WC_Billder_Connect_Logs::setLog($action, $http_code, $method, 'Return ID : ' . $returnResponse[count($returnResponse) - 1], $path);
                }
            }


            if ($return === 'id') {
                $response = explode('/', json_decode($body));
                return $response[count($response) - 1];
            } else {
                return json_decode($body);
            }
        }

        public static function parseJWT($token)
        {
            $token = \explode('.', $token, 3);
            $payload = (array) self::urlSafeDecode($token[1]);
            return $payload;
        }

        protected static function urlSafeDecode($data, $asJson = true)
        {
            if (!$asJson) {
                return \base64_decode(\strtr($data, '-_', '+/'));
            }

            $data = \json_decode(\base64_decode(\strtr($data, '-_', '+/')));

            return $data;
        }

        public function checkToken($token)
        {
            return $this->makeRequest('GET', '/apikeycheck/', 'Check API KEY', array(), $return = 'object', true, $token);
        }

        public function getConfig()
        {
            return  $this->makeRequest('GET', '/config', 'Get config', array(), 'object', false, '');
        }


        public function getVatTypes()
        {
            return  $this->makeRequest('GET', '/invoice/vat_type', 'Get Vat Types', array(), 'object', false, '');
        }

        public function updateCustomer($id, $customerArray)
        {
            return $this->makeRequest('PUT', '/customer/' . $id, 'Customer updated', $customerArray, 'object', true);
        }

        public function createCustomer($customerArray)
        {
            WC_Billder_Connect_Logs::setLog('Creating customer', '-', 'POST', json_encode($customerArray), '/customer');
            return $this->makeRequest('POST', '/customer', 'Customer created', $customerArray, 'id', true);
        }

        public function createInvoice($invoiceArray)
        {
            WC_Billder_Connect_Logs::setLog('Creating Invoice', '-', 'POST', $invoiceArray, '/invoice');
            return $this->makeRequest('POST', '/invoice', 'Invoice created', $invoiceArray, 'id', true);
        }

        public function generateInvoicePdf($idInvoice)
        {

            $args           = array(
                'headers' => array(
                    'Authorization' => ' Bearer ' . $this->token,
                    'Content-Type'   => 'application/json'
                )
            );
            $url            = $this->url . '/ajax/invoice/pdf?id=' . $idInvoice;
            $invoicePdf     = wp_remote_get($url, $args);
            $uploadDirWp    = wp_get_upload_dir();
            $uploadDir      = $uploadDirWp['basedir'] . '/billder-connect/invoices/';

            @mkdir($uploadDir, 0755, true);

            $filename       = md5('invoice-' . $idInvoice) . '.pdf';

            file_put_contents($uploadDir . '/' . $filename, $invoicePdf['body']);

            return array('dir' => $uploadDirWp['basedir'] . '/billder-connect/invoices/' . $filename, 'url' => $uploadDirWp['baseurl'] . '/billder-connect/invoices/' . $filename);
        }
    }
}
