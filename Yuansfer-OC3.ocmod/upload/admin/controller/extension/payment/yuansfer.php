<?php
class ControllerExtensionPaymentYuansfer extends Controller {
    private $staging_url = 'https://mapi.yuansfer.yunkeguan.com/';
    private $production_url = 'https://mapi.yuansfer.com/';
    private $version = '1.0.0';

    private $error = array();

    public function index() {
        $data = $this->load->language('extension/payment/yuansfer');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_yuansfer', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['merchant_id'])) {
            $data['error_merchant_id'] = $this->error['merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }

        if (isset($this->error['store_id'])) {
            $data['error_store_id'] = $this->error['store_id'];
        } else {
            $data['error_store_id'] = '';
        }

        if (isset($this->error['token'])) {
            $data['error_token'] = $this->error['token'];
        } else {
            $data['error_token'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/yuansfer', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/yuansfer', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_yuansfer_merchant_id'])) {
            $data['payment_yuansfer_merchant_id'] = $this->request->post['payment_yuansfer_merchant_id'];
        } else {
            $data['payment_yuansfer_merchant_id'] = $this->config->get('payment_yuansfer_merchant_id');
        }

        if (isset($this->request->post['payment_yuansfer_store_id'])) {
            $data['payment_yuansfer_store_id'] = $this->request->post['payment_yuansfer_store_id'];
        } else {
            $data['payment_yuansfer_store_id'] = $this->config->get('payment_yuansfer_store_id');
        }

        if (isset($this->request->post['payment_yuansfer_token'])) {
            $data['payment_yuansfer_token'] = $this->request->post['payment_yuansfer_token'];
        } else {
            $data['payment_yuansfer_token'] = $this->config->get('payment_yuansfer_token');
        }

        if (isset($this->request->post['payment_yuansfer_test'])) {
            $data['payment_yuansfer_test'] = $this->request->post['payment_yuansfer_test'];
        } else {
            $data['payment_yuansfer_test'] = $this->config->get('payment_yuansfer_test');
        }
        
        $data['payment_methods'] = ['alipay', 'alipay_hk', 'alipay_cn', 'wechatpay', 'paypal', 'unionpay', 'venmo', 'creditcard',
                                    'truemoney', 'tng', 'gcash', 'dana', 'kakaopay', 'bkash', 'easypaisa'];
        
        if (isset($this->request->post['payment_yuansfer_payment_method'])) {
            $data['payment_yuansfer_payment_method'] = $this->request->post['payment_yuansfer_payment_method'];
        } elseif ($this->config->get('payment_yuansfer_payment_method')) {
            $data['payment_yuansfer_payment_method'] = $this->config->get('payment_yuansfer_payment_method');
        } else {
            $data['payment_yuansfer_payment_method'] = array();
        }

        if (isset($this->request->post['payment_yuansfer_total'])) {
            $data['payment_yuansfer_total'] = $this->request->post['payment_yuansfer_total'];
        } else {
            $data['payment_yuansfer_total'] = $this->config->get('payment_yuansfer_total');
        }

        if (isset($this->request->post['payment_yuansfer_completed_status_id'])) {
            $data['payment_yuansfer_completed_status_id'] = $this->request->post['payment_yuansfer_completed_status_id'];
        } else {
            $data['payment_yuansfer_completed_status_id'] = $this->config->get('payment_yuansfer_completed_status_id');
        }

        if (isset($this->request->post['payment_yuansfer_pending_status_id'])) {
            $data['payment_yuansfer_pending_status_id'] = $this->request->post['payment_yuansfer_pending_status_id'];
        } else {
            $data['payment_yuansfer_pending_status_id'] = $this->config->get('payment_yuansfer_pending_status_id');
        }
        
        if (isset($this->request->post['payment_yuansfer_refunded_status_id'])) {
            $data['payment_yuansfer_refunded_status_id'] = $this->request->post['payment_yuansfer_refunded_status_id'];
        } else {
            $data['payment_yuansfer_refunded_status_id'] = $this->config->get('payment_yuansfer_refunded_status_id');
        }

        if (isset($this->request->post['payment_yuansfer_failed_status_id'])) {
            $data['payment_yuansfer_failed_status_id'] = $this->request->post['payment_yuansfer_failed_status_id'];
        } else {
            $data['payment_yuansfer_failed_status_id'] = $this->config->get('payment_yuansfer_failed_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_yuansfer_geo_zone_id'])) {
            $data['payment_yuansfer_geo_zone_id'] = $this->request->post['payment_yuansfer_geo_zone_id'];
        } else {
            $data['payment_yuansfer_geo_zone_id'] = $this->config->get('payment_yuansfer_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_yuansfer_status'])) {
            $data['payment_yuansfer_status'] = $this->request->post['payment_yuansfer_status'];
        } else {
            $data['payment_yuansfer_status'] = $this->config->get('payment_yuansfer_status');
        }

        if (isset($this->request->post['payment_yuansfer_sort_order'])) {
            $data['payment_yuansfer_sort_order'] = $this->request->post['payment_yuansfer_sort_order'];
        } else {
            $data['payment_yuansfer_sort_order'] = $this->config->get('payment_yuansfer_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/yuansfer', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/yuansfer')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_yuansfer_merchant_id']) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }

        if (!$this->request->post['payment_yuansfer_store_id']) {
            $this->error['store_id'] = $this->language->get('error_store_id');
        }

        if (!$this->request->post['payment_yuansfer_token']) {
            $this->error['token'] = $this->language->get('error_token');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/payment/yuansfer');

        $this->model_extension_payment_yuansfer->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/yuansfer');

        $this->model_extension_payment_yuansfer->uninstall();
    }

    public function order() {
        if ($this->config->get('payment_yuansfer_status')) {
            $this->load->model('extension/payment/yuansfer');

            $yuansfer_order = $this->model_extension_payment_yuansfer->getOrder($this->request->get['order_id']);

            if (!empty($yuansfer_order)) {
                $this->load->language('extension/payment/yuansfer');

                $data['yuansfer_order'] = $yuansfer_order;

                $data['text_payment_info'] = $this->language->get('text_payment_info');
                $data['text_reference'] = $this->language->get('text_reference');
                $data['text_order_total'] = $this->language->get('text_order_total');
                $data['text_cancelled_status'] = $this->language->get('text_cancelled_status');
                $data['text_refund_status'] = $this->language->get('text_refund_status');
                $data['text_transactions'] = $this->language->get('text_transactions');
                $data['text_yes'] = $this->language->get('text_yes');
                $data['text_no'] = $this->language->get('text_no');
                $data['text_column_amount'] = $this->language->get('text_column_amount');
                $data['text_column_type'] = $this->language->get('text_column_type');
                $data['text_column_date_added'] = $this->language->get('text_column_date_added');
                $data['button_refund'] = $this->language->get('button_refund');
                $data['text_confirm_refund'] = $this->language->get('text_confirm_refund');
                $data['text_refund_amount'] = $this->language->get('text_refund_amount');
        
                $data['order_id'] = $this->request->get['order_id'];
                $data['user_token'] = $this->request->get['user_token'];
        
                return $this->load->view('extension/payment/yuansfer_order', $data); 
            }
        }
    }

    public function refund() {
        $json = array();

        $this->load->language('extension/payment/yuansfer');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        
        $this->load->model('extension/payment/yuansfer');

        if (isset($this->request->post['refund_amount'])) {
            $refund_amount = $this->request->post['refund_amount'];
        } else {
            $refund_amount = 0;
        }

        $yuansfer_order_info = $this->model_extension_payment_yuansfer->getOrder($order_id);

        if ($yuansfer_order_info) {
            if ($this->config->get('payment_yuansfer_test')) {
                $curl_url = $this->staging_url . 'app-data-search/v3/refund';
            } else {
                $curl_url = $this->production_url . 'app-data-search/v3/refund';
            }

            $params = array(
                'merchantNo'     => $this->config->get('payment_yuansfer_merchant_id'),
                'storeNo'        => $this->config->get('payment_yuansfer_store_id'),
                'refundAmount'   => $refund_amount,
                'currency'       => $yuansfer_order_info['currency_code'],
                'settleCurrency' => $yuansfer_order_info['settlement_currency_code'],
                'transactionNo'  => $yuansfer_order_info['yuansfer_transaction_no']
            );

            ksort($params, SORT_STRING);
            $str = '';

            foreach ($params as $key => $value) {
                $str .= $key . '=' . $value . '&';
            }

            $params['verifySign'] = md5($str . md5($this->config->get('payment_yuansfer_token')));

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $curl_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

            $response = curl_exec($curl);

            if (!$response) {
                $json['error'] = $this->language->get('error_payment_gateway');
            } else {
                $response = json_decode($response, true);

                if (isset($response['ret_code']) && isset($response['ret_msg'])) {
                    if ($response['ret_code'] == '000100') {
                        $this->model_extension_payment_yuansfer->addTransaction($yuansfer_order_info['yuansfer_order_id'], 'refunded', $refund_amount);

                        $message = sprintf($this->language->get('text_refunded'), $yuansfer_order_info['currency_code'] . ' ' . $refund_amount, $yuansfer_order_info['order_id']);

                        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET order_id = '" . (int)$yuansfer_order_info['order_id'] . "', order_status_id = '" . (int)$this->config->get('payment_yuansfer_refunded_status_id') . "', comment = '" . $this->db->escape($message) . "', date_added = NOW()");
                        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$this->config->get('payment_yuansfer_refunded_status_id') . "' WHERE order_id = '" . (int)$yuansfer_order_info['order_id'] . "'");

                        $json['success'] = $message;
                    } else {
                        $json['error'] = $response['ret_code'] . ': ' . $response['ret_msg'];
                    }
                } else {
                    $json['error'] = $this->language->get('error_unknown');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}