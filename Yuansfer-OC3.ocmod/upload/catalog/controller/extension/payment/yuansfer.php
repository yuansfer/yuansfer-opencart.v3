<?php
class ControllerExtensionPaymentYuansfer extends Controller {
    private $staging_url = 'https://mapi.yuansfer.yunkeguan.com/';
    private $production_url = 'https://mapi.yuansfer.com/';

    public function index() {
        $data = $this->load->language('extension/payment/yuansfer');

        $this->load->model('extension/payment/yuansfer');

        $data['payment_methods'] = $this->model_extension_payment_yuansfer->getPaymentMethods();

        $data['testmode'] = $this->config->get('payment_yuansfer_test');

        $this->load->model('checkout/order');

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            return $this->load->view('extension/payment/yuansfer', $data);
        }
    }

    public function submitPayment() {
        $this->load->model('extension/payment/yuansfer');

        $json = array();

        $this->load->language('extension/payment/yuansfer');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('checkout/order');

            if (isset($this->session->data['order_id'])) {
                $order_id = $this->session->data['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if (!empty($this->request->post['yuansfer_merchant']) && $order_info) {
                $vendor = $this->request->post['yuansfer_merchant'];

                $payment_methods = $this->model_extension_payment_yuansfer->getPaymentMethods();

                if (!in_array($vendor, $payment_methods)) {
                    $json['error'] = $this->language->get('error_unknown');
                } else {
                    $products = array();

                    foreach ($this->cart->getProducts() as $product) {
                        $products[] = array(
                            'goods_name' => htmlspecialchars($product['name']),
                            'quantity'   => $product['quantity']
                        );
                    }
        
                    $goodsInfo = json_encode($products);

                    $this->session->data['yuansfer_order_id'] = $order_info['order_id'] . '-' . time();

                    $params = array(
                        'merchantNo'     => $this->config->get('payment_yuansfer_merchant_id'),
                        'storeNo'        => $this->config->get('payment_yuansfer_store_id'),
                        'amount'         => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
                        'currency'       => $order_info['currency_code'],
                        'settleCurrency' => $this->config->get('config_currency'),
                        'vendor'         => $vendor,
                        'ipnUrl'         => html_entity_decode($this->url->link('extension/payment/yuansfer/ipn', '', true), ENT_QUOTES),
                        'callbackUrl'    => html_entity_decode($this->url->link('extension/payment/yuansfer/callback', 'reference={reference}&status={status}', true), ENT_QUOTES),
                        'terminal'       => $this->request->get['mobile'] ? 'WAP' : 'ONLINE',
                        'reference'      => $this->session->data['yuansfer_order_id'],
                        'goodsInfo'      => $goodsInfo
                    );
                    
                    $params['verifySign'] = $this->sign($params);

                    $result = $this->sendCurl('online/v3/secure-pay', $params);
                    
                    $this->model_extension_payment_yuansfer->editOrderPayment($order_info['order_id'], $this->language->get('text_' . $vendor));

                    if (isset($result['result']['cashierUrl'])) {
                        $json['redirect'] = $result['result']['cashierUrl'];
                    } else {
                        $json['error'] = $this->language->get('error_curl');

                        if (isset($result['ret_code']) && isset($result['ret_msg'])) {
                            $json['error'] .= ' (' . $result['ret_code'] . ') ' . $result['ret_msg'];
                        }
                    }
                }
            } else {
                $json['error'] = $this->language->get('error_merchant');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ipn() {
        $this->load->language('extension/payment/yuansfer');
        
        if (isset($this->request->post['reference'])) {
            $order_id = explode('-', $this->request->post['reference']);
            $order_id = $order_id[0];
        } else {
            $order_id = 0;
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);
        
        $data = $this->request->post;
        unset($data['verifySign']);
        $signature = $this->sign($data);

        if (isset($this->request->post['verifySign']) && $signature == $this->request->post['verifySign'] && $order_info && isset($this->request->post['transactionNo']) && $this->request->post['transactionNo']) {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/yuansfer');

            $order_info['yuansfer_transaction_no'] = $this->request->post['transactionNo'];
            $order_info['settlement_currency_code'] = $this->config->get('config_currency');

            $yuansfer_order_id = $this->model_extension_payment_yuansfer->addOrder($order_info);

            if (isset($this->request->post['amount'])) {
                $amount = $this->request->post['amount'];
            } else {
                $amount = 0;
            }

            if (isset($this->request->post['status'])) {
                $status = '';
                $order_status_id = 0;

                switch ($this->request->post['status']) {
                    case 'success':
                        $status = 'success';

                        $order_status_id = $this->config->get('payment_yuansfer_completed_status_id');
                        break;
                    case 'pending':
                        $status = 'pending';
                        
                        $order_status_id = $this->config->get('payment_yuansfer_pending_status_id');
                        break;
                    case 'failed':
                        $status = 'failed';
                        
                        $order_status_id = $this->config->get('payment_yuansfer_failed_status_id');
                        break;
                    case 'init':
                        $status = 'init';

                        $order_status_id = $this->config->get('payment_yuansfer_failed_status_id');
                        break;
                    case 'dealing':
                        $status = 'dealing';
                        
                        $order_status_id = $this->config->get('payment_yuansfer_failed_status_id');
                        break;
                    case 'closed':
                        $status = 'closed';

                        $order_status_id = $this->config->get('payment_yuansfer_failed_status_id');
                        break;
                    default:
                        $status = 'failed';
                        $order_status_id = $order_info['order_status_id'];
                }

                $this->model_extension_payment_yuansfer->addTransaction($yuansfer_order_id, $status, $amount);

                if ((!$order_info['order_status_id'] && $status == 'success') || $order_info['order_status_id']) {
                    $comment = $this->language->get('text_transaction') . $this->request->post['transactionNo'];

                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_info['order_id'] . "' AND order_status_id = '" . (int)$order_status_id . "' AND comment = '" . $this->db->escape($comment) . "'");

                    if (!$query->num_rows) {
                        $this->model_checkout_order->addOrderHistory($order_info['order_id'], $order_status_id, $comment, true);
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_info['order_id'] . "', order_status_id = '" . (int)$order_status_id . "', comment = '', date_added = NOW()");
                }
            }
            
            echo 'success';
        }
    }

    public function callback() {
        $this->load->language('extension/payment/yuansfer');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
          'text' => $this->language->get('text_home'),
          'href' => $this->url->link('common/home')
        );
            
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/yuansfer/callback')
        );

        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');

        if (isset($this->request->get['status']) && $this->request->get['status'] == 'failed') {
            $data['description'] = '';
            $data['failed'] = true;
            $data['message'] = isset($this->request->get['message']) ? html_entity_decode($this->request->get['message'], ENT_QUOTES) : '';
        } else {
            $data['description'] = $this->language->get('text_description_wait');
            $data['failed'] = false;
        }
        
        $data['button_checkout'] = $this->language->get('button_checkout');
        
        $data['checkout'] = $this->url->link('checkout/checkout');
        $data['success_location'] = $this->url->link('checkout/success');
        $data['failed_location'] = html_entity_decode($this->url->link('extension/payment/yuansfer/callback', 'status=failed'), ENT_QUOTES);
        $data['unknown_location'] = html_entity_decode($this->url->link('extension/payment/yuansfer/callback', 'status=failed'), ENT_QUOTES);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/payment/yuansfer_return', $data));
    }

    public function status() {
        $json = array();
        
        $this->load->language('extension/payment/yuansfer');
        
        if (isset($this->session->data['order_id'])) {
            $this->load->model('checkout/order');
            
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            
            if ($order_info && $order_info['payment_code'] == 'yuansfer') {
                $data = array(
                    'merchantNo' => $this->config->get('payment_yuansfer_merchant_id'),
                    'storeNo'    => $this->config->get('payment_yuansfer_store_id'),
                    'reference'  => $this->session->data['yuansfer_order_id']
                );
                
                $data['verifySign'] = $this->sign($data);
            
                $result = $this->sendCurl('app-data-search/v3/tran-query', $data);

                if ($result) {
                    $result = $result['result'];
                    
                    if ($result['status'] == 'init' || $result['status'] == 'dealing' || $result['status'] == 'pending') {
                        $json['waiting'] = true;
                    } elseif ($result['status'] == 'success') {
                        $json['status'] = true;
                        $json['message'] = $this->language->get('text_description_success') . '<br /><br />';
                    } else {
                        $json['status'] = false;
                        $json['message'] = $this->language->get('text_description_failed') . '<br /><br />';
                    }
                } else {
                    $json['failed'] = true;
                }
            } else {
                $json['failed'] = true;
            }
        } else {
            $json['failed'] = true;
        }
        
        $this->response->setOutput(json_encode($json));
    }
    
    private function sign($data) {
        ksort($data, SORT_STRING);

        $sign = '';

        foreach ($data as $key => $value) {
            $sign .= $key . '=' . $value . '&';
        }

        return md5($sign . md5($this->config->get('payment_yuansfer_token')));
    }
    
    private function sendCurl($url, $data) {
        if ($this->config->get('payment_yuansfer_test')) {
            $api_url = $this->staging_url;
        } else {
            $api_url = $this->production_url;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $api_url . $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        $result = curl_exec($curl);

        curl_close($curl);
        
        return json_decode($result, true);
    }
}