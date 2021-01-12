<?php
class ModelExtensionPaymentYuansfer extends Model {
    public function getYuansferOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "yuansfer_order` WHERE `order_id` = '" . (int)$order_id . "'");
    
        return $query->row;
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "yuansfer_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($query->num_rows) {
            $order = $query->row;
            $order['transactions'] = $this->getTransactions($order['yuansfer_order_id']);

            return $order;
        } else {
            return false;
        }
    }

    private function getTransactions($yuansfer_order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "yuansfer_order_transaction` WHERE `yuansfer_order_id` = '" . (int)$yuansfer_order_id . "'");

        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }

    public function addTransaction($yuansfer_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "yuansfer_order_transaction` SET `yuansfer_order_id` = '" . (int)$yuansfer_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }

    public function setYuansferOrderCancelStatus($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "yuansfer_order` SET `cancelled_status` = '1', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'");
    }

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "yuansfer_order` (
              `yuansfer_order_id` int(11) NOT NULL AUTO_INCREMENT,
              `order_id` int(11) NOT NULL,
              `yuansfer_transaction_no` varchar(40) NOT NULL,
              `currency_code` CHAR(3) NOT NULL,
              `settlement_currency_code` CHAR(3) NOT NULL,
              `total` DECIMAL (10,2) NOT NULL,
              `date_added` DATETIME NOT NULL,
              `date_modified` DATETIME NOT NULL,
              PRIMARY KEY (`yuansfer_order_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "yuansfer_order_transaction` (
              `yuansfer_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
              `yuansfer_order_id` INT(11) NOT NULL,
              `date_added` DATETIME NOT NULL,
              `type` ENUM('init', 'dealing', 'success', 'failed', 'pending', 'closed', 'refunded') DEFAULT NULL,
              `amount` DECIMAL (10,2) NOT NULL,
              PRIMARY KEY (`yuansfer_order_transaction_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "yuansfer_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "yuansfer_order_transaction`");
    }
}