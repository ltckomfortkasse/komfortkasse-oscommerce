<?php

/** 
 * Komfortkasse
 * Config Class
 * 
 * @version 1.0.8-osc2.4
 */
class Komfortkasse_Config {
	const activate_export = 'KOMFORTKASSE_ACTIVATE_EXPORT';
	const activate_update = 'KOMFORTKASSE_ACTIVATE_UPDATE';
	const payment_methods = 'KOMFORTKASSE_PAYMENT_CODES';
	const status_open = 'KOMFORTKASSE_STATUS_OPEN';
	const status_paid = 'KOMFORTKASSE_STATUS_PAID';
	const status_cancelled = 'KOMFORTKASSE_STATUS_CANCELLED';
	const encryption = 'KOMFORTKASSE_ENCRYPTION';
	const accesscode = 'KOMFORTKASSE_ACCESSCODE';
	const apikey = 'KOMFORTKASSE_APIKEY';
	const publickey = 'KOMFORTKASSE_PUBLICKEY';
	const privatekey = 'KOMFORTKASSE_PRIVATEKEY';
	
	// changing constants at runtime is necessary for init, therefore save them in cache
	private static $cache = array ();
	
	public static function setConfig($constant_key, $value) {
		
		$sql_data_array = array (
				'configuration_value' => $value 
		);
		tep_db_perform(TABLE_CONFIGURATION, $sql_data_array, 'update', "configuration_key='" . $constant_key . "'");
		self::$cache [$constant_key] = $value;
	}
	public static function getConfig($constant_key) {
		if (!array_key_exists($constant_key, self::$cache)) {
			$config_q = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " where configuration_key='" . $constant_key . "'");
			$config_a = tep_db_fetch_array($config_q);
			$config = $config_a ['configuration_value'];
			self::$cache [$constant_key] = $config;
		}
		
		return self::$cache [$constant_key];
	}
	public static function getRequestParameter($key) {
		if (array_key_exists($key, $_POST))
			return urldecode($_POST [$key]);
		else
			return urldecode($_GET [$key]);
	}
	
	public static function getVersion() {
		$v = file_get_contents(DIR_FS_CATALOG . DIR_WS_INCLUDES . 'version.php');
		return $v;
	}
}
?>