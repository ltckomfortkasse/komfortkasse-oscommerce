<?php
/**
 * Komfortkasse
 * Installer
 *
 * @version 1.0.8-osc2.4
 * 
 * use these SQL statements to delete the configuration entries in order to re-install the plugin:
 * delete from configuration_group where configuration_group_title='Komfortkasse';
 * delete from configuration where configuration_key like 'KOMFORTKASSE%';
 */
// error_reporting ( E_ALL );
// ini_set ( 'display_errors', '1' );

?>
<html>
<head>
<title>Komfortkasse Installer</title>
</head>
<body>
	<font face="Verdana,Arial,Helvetica"> <img
		src="images/komfortkasse_eu.png" border="0"><br />
		<h3>Auto Installer</h3>
<?php $steps = 8; $step=0; ?>
Note: if the installer exits before step <?php echo $steps; ?> without an error message, enable error reporting in this install.php file. (Uncomment lines 13 and 14.)

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Including files...

<?php
require_once ('../../includes/configure.php');
require_once (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'filenames.php');
require_once (DIR_FS_CATALOG . DIR_WS_INCLUDES . 'database_tables.php');
require_once (DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'database.php');
require (DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'general.php');
require_once ('Komfortkasse_Config.php');
?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Connecting to database...
<?php
tep_db_connect() or die('Unable to connect to database server!');
// global $$link;
?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Determining Language...

<?php
// set application wide parameters
$configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
while ( $configuration = tep_db_fetch_array($configuration_query) ) {
	define($configuration ['cfgKey'], $configuration ['cfgValue']);
}

require_once (DIR_FS_CATALOG . DIR_WS_CLASSES . 'language.php');
$lng = new language();
$lng->get_browser_language();
echo $lng->language ['directory'];
?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Determining Configuration Group ID...

<?php
$config_group_q = tep_db_query("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " where configuration_group_title='Komfortkasse'");
$config_group_a = tep_db_fetch_array($config_group_q);
$config_group_id = $config_group_a ['configuration_group_id'];
if ($config_group_id) {
	echo 'ERROR: Configuration group ID for "Komfortkasse" already exists. Probably the Module is already installed. This Installer will exit now.';
	die();
}

$config_group_q1 = tep_db_query("SELECT max(configuration_group_id) as maxid FROM " . TABLE_CONFIGURATION_GROUP);
$config_group_a1 = tep_db_fetch_array($config_group_q1);
$config_group_id1 = $config_group_a1 ['maxid'] + 1;

$config_group_q2 = tep_db_query("SELECT max(configuration_group_id) as maxid FROM " . TABLE_CONFIGURATION);
$config_group_a2 = tep_db_fetch_array($config_group_q2);
$config_group_id2 = $config_group_a2 ['maxid'] + 1;

$config_group_id = max($config_group_id1, $config_group_id2);

echo $config_group_id;
?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Creating Configuration Group...
<?php
$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_group_title' => 'Komfortkasse',
		'configuration_group_description' => 'Komfortkasse Konfiguration',
		'sort_order' => $config_group_id,
		'visible' => 1 
);
tep_db_perform(TABLE_CONFIGURATION_GROUP, $sql_data_array);

?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Creating Configuration ...

<?php
$sort_order = 1;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::activate_export,
		'configuration_value' => 'true',
		'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'),',
		'configuration_title' => 'Export orders',
		'configuration_description' => 'Activate export of orders',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::activate_update,
		'configuration_value' => 'true',
		'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'),',
		'configuration_title' => 'Update orders',
		'configuration_description' => 'Activate update of orders',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::payment_methods,
		'configuration_value' => 'moneyorder,eustandardtransfer',
		'configuration_title' => 'Payment type codes',
		'configuration_description' => 'All payment type codes that should be exported. Syntax: moneyorder,eustandardtransfer',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::status_open,
		'configuration_value' => '1',
		'configuration_title' => 'State open',
		'configuration_description' => 'Order state that should be exported (open orders)',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::status_paid,
		'configuration_value' => '2',
		'use_function' => 'tep_get_order_status_name',
		'set_function' => 'tep_cfg_pull_down_order_statuses(',
		'configuration_title' => 'State paid',
		'configuration_description' => 'Order state that should be set when a payment has been received.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::status_cancelled,
		'configuration_value' => '4',
		'use_function' => 'tep_get_order_status_name',
		'set_function' => 'tep_cfg_pull_down_order_statuses(',
		'configuration_title' => 'State cancelled',
		'configuration_description' => 'Order state that should be set when an order has been cancelled.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::encryption,
		'configuration_value' => '',
		'configuration_title' => 'Encryption',
		'configuration_description' => 'Encryption technology. Do not change! Is set automatically by komfortkasse.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::accesscode,
		'configuration_value' => '',
		'configuration_title' => 'Access code (encrypted)',
		'configuration_description' => 'Encrypted access code. Do not change! Is set automatically by komfortkasse.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::publickey,
		'configuration_value' => '',
		'configuration_title' => 'Public key',
		'configuration_description' => 'Key for encrypting data that is sent to komfortkasse. Do not change! Is set automatically by komfortkasse.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::privatekey,
		'configuration_value' => '',
		'configuration_title' => 'Private key',
		'configuration_description' => 'Key for decrypting data that is received from komfortkasse. Do not change! Is set automatically by komfortkasse.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

$sql_data_array = array (
		'configuration_group_id' => $config_group_id,
		'configuration_key' => Komfortkasse_Config::apikey,
		'configuration_value' => '',
		'configuration_title' => 'API Key',
		'configuration_description' => 'Key for accessing the komfortkasse API. Do not change! Is set automatically by komfortkasse.',
		'sort_order' => $sort_order 
);
tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
$sort_order++;

?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
Modifying .htaccess file...
<?php
$ok = 0;
if (rename('.htaccess', '_htaccess.beforeinstall') === TRUE) {
	if (rename('_htaccess.afterinstall', '.htaccess') === TRUE) {
		$ok = 1;
	}
}
if ($ok) {
	echo "ok";
} else {
	echo "Important: your .htaccess file could not be changed. For improved security, please change your .htaccess file so that the install.php script cannot be executed, or rename install.php.";
}

?>

<br /> <br /> <b><?php echo ++$step;?>/<?php echo $steps;?></b>
		Finished. <a
		href="<?php echo HTTP_SERVER?>/admin/configuration.php?gID=<?php echo $config_group_id; ?>"
		target="_new">Please check the configuration now.</a><br /> (If you
		cannot access this link, please login to your admin panel and open the
		Komfortkasse configuration from the menu.)<br /> <br /> <br />
		<h3>Instant order transmission</h3> New orders will be read
		periodically from your online shop. Additionally, you can activate <b>instant
			order transmission</b>, which will transmit any new order
		immediately. This way, your customer will receive payment information
		immediately. We encourage you to activate instant order transmission.
		In order to activate instant order transmission, edit the following
		files:<br /> <br /> <b>/admin/orders.php</b>, around line 120: <pre>
          tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " ...
		<b>
// BEGIN Komfortkasse
include_once '../ext/komfortkasse/Komfortkasse.php';
$k = new Komfortkasse();
$k->notifyorder($oID);
// END Komfortkasse
</b>
$order_updated = true;
</pre> <br /> <b>/includes/modules/payment/moneyorder.php</b>,
		or any other payment module that will be used with Komfortkasse (e.g.
		moneyorder, eustandardtransfer), in function after_process, at the end of
		the function: <pre>
<b>
// BEGIN Komfortkasse
global $insert_id;
include_once './ext/komfortkasse/Komfortkasse.php';
$k = new Komfortkasse();
$k->notifyorder($insert_id);
// END Komfortkasse
</b>
</pre> <br /> <b>/lang/[your
			languages]/modules/payment/moneyorder.php</b>, or any other
		payment module that will be used with Komfortkasse (e.g. banktransfer,
		eustandardtransfer), change the MODULE_PAYMENT_[...]_TEXT_DESCRIPTION constant
		(e.g. MODULE_PAYMENT_MONEYORDER_TEXT_DESCRIPTION): <pre>
<b>
// german
define('MODULE_PAYMENT_MONEYORDER_TEXT_DESCRIPTION', '&lt;br /&gt;Sie erhalten nach Bestellannahme die Kontodaten in einer gesonderten E-Mail.');

// english
define('MODULE_PAYMENT_MONEYORDER_TEXT_DESCRIPTION', '&lt;br /&gt;After your order is confirmed, you will receive payment details in a separate e-mail.');
</b>
</pre>

	</font>
</body>
</html>

