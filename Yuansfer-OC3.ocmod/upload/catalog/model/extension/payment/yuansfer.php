<?php
class ModelExtensionPaymentYuansfer extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/yuansfer');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_yuansfer_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_yuansfer_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_yuansfer_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if (!$this->config->get('payment_yuansfer_merchant_id') || !$this->config->get('payment_yuansfer_store_id') || !$this->config->get('payment_yuansfer_token')) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $payment_methods = $this->getPaymentMethods();

            $selected = array();

            foreach ($payment_methods as $payment_method) {
                $selected[] = $this->language->get('text_' . $payment_method);
            }

            if ($selected) {
                $method_data = array(
                    'code'       => 'yuansfer',
                    'title'      => sprintf($this->language->get('text_title'), implode(' / ', $selected)),
                    'terms'      => '',
                    'sort_order' => $this->config->get('payment_yuansfer_sort_order')
                );
            }
        }

        return $method_data;
    }

    public function addOrder($order_info) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "yuansfer_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `yuansfer_transaction_no` = '" . $this->db->escape($order_info['yuansfer_transaction_no']) . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `settlement_currency_code` = '" . $this->db->escape($order_info['settlement_currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "', `date_added` = NOW(), `date_modified` = NOW()");
    
        return $this->db->getLastId();
    }
    
    public function editOrderPayment($order_id, $payment_method) {
        $this->load->language('extension/payment/yuansfer');
        
        $payment_method = $payment_method . ' (' . $this->language->get('text_yuansfer') . ')';
        
        $this->db->query("UPDATE " . DB_PREFIX . "order SET payment_method = '" . $this->db->escape($payment_method) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function addTransaction($yuansfer_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "yuansfer_order_transaction` SET `yuansfer_order_id` = '" . (int)$yuansfer_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }
    
    public function getPaymentMethods() {
        $payment_methods = ['alipay', 'alipay_hk', 'alipay_cn', 'wechatpay', 'paypal', 'unionpay', 'venmo', 'creditcard',
                            'truemoney', 'tng', 'gcash', 'dana', 'kakaopay', 'bkash', 'easypaisa'];

        $data = [];

        foreach ($payment_methods as $payment_method) {
            if (is_array($this->config->get('payment_yuansfer_payment_method')) && in_array($payment_method, $this->config->get('payment_yuansfer_payment_method'))) {
                if ($payment_method == 'kakaopay' && $this->session->data['currency'] != 'KRW') {
                    continue;
                } elseif ($payment_method == 'gcash' && $this->session->data['currency'] != 'PHP') {
                    continue;
                } elseif ($payment_method == 'dana' && $this->session->data['currency'] != 'IDR') {
                    continue;
                } elseif ($payment_method == 'alipay_hk' && $this->session->data['currency'] != 'HKD') {
                    continue;
                } elseif ($payment_method == 'alipay_cn' && $this->session->data['currency'] != 'CNY') {
                    continue;
                }

                $data[] = $payment_method;
            }
        }

        return $data;
    }
}
