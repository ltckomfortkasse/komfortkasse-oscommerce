<?php

// in KK, an Order is an Array providing the following members:
// number, date, email, customer_number, payment_method, amount, currency_code, exchange_rate, language_code
// delivery_ and billing_: _firstname, _lastname, _company
// products: an Array of item numbers

/**
 * Komfortkasse
 * Config Class
 *
 * @version 1.0.9-osc2.4
 */
class Komfortkasse_Order {
	
	// return all order numbers that are "open" and relevant for tranfer to kk
	public static function getOpenIDs() {
		
		// get payment module names
		// hint: when called while placing an order, the constants will already be defined. wehen called from external, this will only work for orders made in the default language.
		$lang_q = tep_db_query("SELECT l.directory FROM " . TABLE_CONFIGURATION . " c join " . TABLE_LANGUAGES . " l on c.configuration_value=l.code  where configuration_key='DEFAULT_LANGUAGE'");
		$lang_a = tep_db_fetch_array($lang_q);
		$lang = $lang_a ['directory'];
		
		$paycodes = preg_split('/,/', Komfortkasse_Config::getConfig(Komfortkasse_Config::payment_methods));
		foreach ( $paycodes as $paycode ) {
			include_once DIR_FS_CATALOG . DIR_WS_LANGUAGES . $lang.'/modules/payment/'.$paycode.'.php';
				$name = constant('MODULE_PAYMENT_' . strtoupper($paycode) . '_TEXT_TITLE');
			if ($name)
				$paynames [] = $name;
		}
		
		// get order ids
		$ret = array ();
		
		$sql = "select orders_id from " . TABLE_ORDERS . " where orders_status in (" . Komfortkasse_Config::getConfig(Komfortkasse_Config::status_open) . ") and ( ";
		for($i = 0; $i < count($paynames); $i++) {
			$sql .= " payment_method like '" . $paynames [$i] . "' ";
			if ($i < count($paynames) - 1) {
				$sql .= " or ";
			}
		}
		$sql .= " )";
		$orders_q = tep_db_query($sql);
		
		while ( $orders_a = tep_db_fetch_array($orders_q) ) {
			$ret [] = $orders_a ['orders_id'];
		}
		
		return $ret;
	}
	public static function getOrder($number) {
		require_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
		
		$order = new order($number);
		if (empty($number) || empty($order)) {
			return null;
		}
		
		$total_q = tep_db_query("SELECT value FROM " . TABLE_ORDERS_TOTAL . " where orders_id=" . $number . " and class='ot_total'");
		$total_a = tep_db_fetch_array($total_q);
		$total = $total_a ['value'];
		
		$country_bill_q = tep_db_query("SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " where countries_name='" . $order->billing ['country']['title'] . "'");
		$country_bill_a = tep_db_fetch_array($country_bill_q);
		$country_del_q = tep_db_query("SELECT countries_iso_code_2 FROM " . TABLE_COUNTRIES . " where countries_name='" . $order->delivery ['country']['title'] . "'");
		$country_del_a = tep_db_fetch_array($country_del_q);


		if (array_key_exists('languages_id', $_SESSION)) {
			$lang_q = tep_db_query("SELECT l.code FROM " . TABLE_LANGUAGES . " l where l.languages_id=" . $_SESSION ['languages_id']);
			$lang_a = tep_db_fetch_array($lang_q);
			$lang = $lang_a ['code'];
		} else {
			$lang = '';
		}
		
		$ret = array ();
		$ret ['number'] = $number;
		$ret ['date'] = date("d.m.Y", strtotime($order->info ['date_purchased']));
		$ret ['email'] = $order->customer ['email_address'];
		$ret ['customer_number'] = $order->customer ['id'];
		$ret ['payment_method'] = $order->info ['payment_method'];
		$ret ['amount'] = $total;
		$ret ['currency_code'] = $order->info ['currency'];
		$ret ['exchange_rate'] = $order->info ['currency_value'];
		$ret ['language_code'] = $lang . "-" .  $country_bill_a ['countries_iso_code_2'];
		$ret ['delivery_firstname'] = '';
		$ret ['delivery_lastname'] = $order->delivery ['name'];
		$ret ['delivery_company'] = $order->delivery ['company'];
        $ret ['delivery_street'] = $order->delivery ['street_address'];
        $ret ['delivery_postcode'] = $order->delivery ['postcode'];
        $ret ['delivery_city'] = $order->delivery ['city'];
        $ret ['delivery_countrycode'] = $country_del_a ['countries_iso_code_2'];
		$ret ['billing_firstname'] = '';
		$ret ['billing_lastname'] = $order->billing ['name'];
		$ret ['billing_company'] = $order->billing ['company'];
        $ret ['billing_street'] = $order->billing ['street_address'];
        $ret ['billing_postcode'] = $order->billing ['postcode'];
        $ret ['billing_city'] = $order->billing ['city'];
        $ret ['billing_countrycode'] = $country_bill_a ['countries_iso_code_2'];
		
		$order_products = $order->products;
		foreach ( $order_products as $product ) {
			if ($product ['model']) {
				$ret ['products'] [] = $product ['model'];
			} else {
				$ret ['products'] [] = $product ['name'];
			}
		}
		
		return $ret;
	}
	
	public static function updateOrder($order, $status, $callbackid) {
		tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $status . "', last_modified = now() where orders_id = '" . $order ['number'] . "'");
		tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . $order ['number'] . "', '" . $status . "', now(), '0', 'Komfortkasse ID " . $callbackid . "')");
	}
}

?>