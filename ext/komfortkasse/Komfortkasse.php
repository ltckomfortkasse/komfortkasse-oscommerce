<?php

/** 
 * Komfortkasse 
 * Main Class, multi-shop
 */


include_once 'Komfortkasse_Config.php';
include_once 'Komfortkasse_Order.php';
class Komfortkasse {
	const plugin_version = '1.0.8';
	
	const maxlen_ssl = 117;
	const len_mcrypt = 16;
	
	// ------------- readorders --------------------
	public function readorders() {
		if (! Komfortkasse_Config::getConfig ( Komfortkasse_Config::activate_export )) {
			return;
		}
		
		// accesscode prüfen
		self::check();
		
		// schritt 1: alle IDs ausgeben
		$param = Komfortkasse_Config::getRequestParameter ( 'o' ) ;
		$param = self::kkdecrypt ( $param );
		
		if ($param == "all") {
			$o = '';
			foreach ( Komfortkasse_Order::getOpenIDs () as $id ) {
				$o = $o . self::kk_csv ( $id );
			}
			echo self::kkencrypt ( $o );
		} else {
			$o = '';
			$ex = explode ( ';', $param );
			foreach ( $ex as $id ) {
				$id = trim ( $id );
				// schritt 2: details pro auftrag ausgeben
				$order = Komfortkasse_Order::getOrder ( $id );
				if (! $order)
					continue;
					
					// echo "<pre>"; var_dump ($order); echo "</pre>";
				$o = $o . self::kk_csv ( $order ['number'] );
				$o = $o . self::kk_csv ( $order ['email'] );
				$o = $o . self::kk_csv ( $order ['customer_number'] );
				$o = $o . self::kk_csv ( $order ['payment_method'] );
				$o = $o . self::kk_csv ( $order ['amount'] );
				$o = $o . self::kk_csv ( $order ['currency_code'] );
				$o = $o . self::kk_csv ( $order ['exchange_rate'] );
				$o = $o . self::kk_csv ( $order ['language_code'] );
				$o = $o . self::kk_csv ( $order ['date'] );
				$o = $o . self::kk_csv ( $order ['delivery_firstname'] );
				$o = $o . self::kk_csv ( $order ['delivery_lastname'] );
				$o = $o . self::kk_csv ( $order ['delivery_company'] );
				$o = $o . self::kk_csv ( $order ['billing_firstname'] );
				$o = $o . self::kk_csv ( $order ['billing_lastname'] );
				$o = $o . self::kk_csv ( $order ['billing_company'] );
				
				foreach ( $order ['products'] as $product ) {
					$o = $o . self::kk_csv ( $product );
				}
				
				$o = $o . "\n";
				// echo $o;
			}
			$cry = self::kkencrypt ( $o );
			if ($cry === false)
				echo self::kkcrypterror ();
			else
				echo $cry;
		}
	}
	
	// ------------- init --------------------
	public function test() {
		$dec = self::kkdecrypt (  Komfortkasse_Config::getRequestParameter ( 'test' ) ) ;
		// echo $dec; die;
		
		$enc = self::kkencrypt ( $dec );
		
		echo $enc;
	}
	
	// ------------- init --------------------
	public function init() {
		echo "connection:connectionsuccess|";
		
		echo "accesskey:";
		// set access code
		$hashed = md5 ( Komfortkasse_Config::getRequestParameter ( 'accesscode' ) );
		if (Komfortkasse_Config::getConfig ( Komfortkasse_Config::accesscode ) != '' && Komfortkasse_Config::getConfig ( Komfortkasse_Config::accesscode ) != 'undefined' && Komfortkasse_Config::getConfig ( Komfortkasse_Config::accesscode ) != $hashed) {
			echo "Access Code already set! Shop " . Komfortkasse_Config::getConfig ( Komfortkasse_Config::accesscode ) . ", given (hash) " . $hashed;
			return;
		}
		
		if ($hashed != Komfortkasse_Config::getRequestParameter ( 'accesscode_hash' )) {
			echo "MD5 Hashes do not match! Shop " . $hashed . " given " . Komfortkasse_Config::getRequestParameter ( 'accesscode_hash' );
			return;
		}
		
		Komfortkasse_Config::setConfig ( Komfortkasse_Config::accesscode, $hashed );
		echo "accesskeysuccess|";
		
		echo "apikey:";
		// set API key
		$apikey = Komfortkasse_Config::getRequestParameter ( 'apikey' );
		if (Komfortkasse_Config::getConfig ( Komfortkasse_Config::apikey ) != '' && Komfortkasse_Config::getConfig ( Komfortkasse_Config::apikey ) != 'undefined' && Komfortkasse_Config::getConfig ( Komfortkasse_Config::apikey ) != $apikey) {
			echo "API Key already set! Shop " . Komfortkasse_Config::getConfig ( Komfortkasse_Config::apikey ) . ", given " . $apikey;
			return;
		}
		
		Komfortkasse_Config::setConfig ( Komfortkasse_Config::apikey, $apikey );
		echo "apikeysuccess|";
			
		// test for openssl encryption
		echo "encryption:";
		if (extension_loaded ( 'openssl' )) {
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::encryption, 'openssl' );
			
			// test for public&privatekey encryption
			$kpriv = Komfortkasse_Config::getRequestParameter ( 'privateKey' );
			$kpub = Komfortkasse_Config::getRequestParameter ( 'publicKey' );
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::privatekey, $kpriv );
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::publickey, $kpub );
			
			echo "openssl#" . OPENSSL_VERSION_TEXT . "#" . OPENSSL_VERSION_NUMBER . "|";
			
			// test with rsa
			$crypttest =  Komfortkasse_Config::getRequestParameter ( 'testSSLEnc' ) ;
		} else if (extension_loaded ( 'mcrypt' )) {
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::encryption, 'mcrypt' );
			
			// test for mcrypt encryption
			$sec = Komfortkasse_Config::getRequestParameter ( 'mCryptSecretKey' );
			$iv = Komfortkasse_Config::getRequestParameter ( 'mCryptIV' );
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::privatekey, $sec );
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::publickey, $iv );
			
			echo "mcrypt|";
			
			// test with mcrypt
			$crypttest =  Komfortkasse_Config::getRequestParameter ( 'testMCryptEnc' ) ;
		} else {
			Komfortkasse_Config::setConfig ( Komfortkasse_Config::encryption, 'base64' );
			// $db->execute ( "update " . TABLE_PLUGIN_CONFIGURATION . " set config_value='base64' where config_key='KOMFORTKASSE_ENCRYPTION'" );
			echo "base64|";
			
			// test with base64 encoding
			$crypttest =  Komfortkasse_Config::getRequestParameter ( 'testBase64Enc' ) ;
		}
		
		echo "decryptiontest:";
		$decrypt = self::kkdecrypt ( $crypttest, Komfortkasse_Config::getConfig ( Komfortkasse_Config::encryption ) );
		if ($decrypt == "Can you hear me?")
			echo "ok";
		else
			echo self::kkcrypterror ();
		
		echo "|encryptiontest:";
		$encrypt = self::kkencrypt ( "Yes, I see you!", Komfortkasse_Config::getConfig ( Komfortkasse_Config::encryption ) );
		if ($encrypt !== FALSE)
			echo $encrypt;
		else
			echo self::kkcrypterror ();
	}
	
	// ------------------ updateorders ------------------------------
	public function updateorders() {
		if (! Komfortkasse_Config::getConfig ( Komfortkasse_Config::activate_update )) {
			return;
		}
		
		self::check();
		
		$param =  Komfortkasse_Config::getRequestParameter ( 'o' ) ;
		$param = self::kkdecrypt ( $param );
		
		$openids = Komfortkasse_Order::getOpenIDs();
		
		$o = '';
		$lines = explode ( "\n", $param );
		foreach ( $lines as $line ) {
			$col = explode ( ';', $line );
			$id = trim ( $col [0] );
			$status = trim ( $col [1] );
			$callbackid = trim ( $col [2] );
			
			$order = Komfortkasse_Order::getOrder($id);
			
			if (empty ( $id ) || $id != $order['number']) {
				continue;
			}
			
			$newstatus = self::getNewStatus ( $status );
			if (empty($newstatus))
				continue;
			
			// test if order is still open
			if (!in_array($order['number'], $openids)) {
				continue;
			}
			
			Komfortkasse_Order::updateOrder($order, $newstatus, $callbackid);
			
			$o = $o . self::kk_csv ( $id );
			// echo $o;
		}
		$cry = self::kkencrypt ( $o );
		if ($cry === false)
			echo self::kkcrypterror ();
		else
			echo $cry;
	}
	
	// ------------------ notifyorder ------------------------------
	public function notifyorder($id) {
		
		if (! Komfortkasse_Config::getConfig ( Komfortkasse_Config::activate_export )) {
			return;
		}
		
		// test if order is relevant
		$openids = Komfortkasse_Order::getOpenIDs();
		if (!in_array($id, $openids)) {
			return;
		}
		
		$order = Komfortkasse_Order::getOrder($id);
		
		$query_raw = http_build_query ($order);
		
		$query_enc = self::kkencrypt($query_raw);
		
		$query = http_build_query (array('q' => $query_enc, 'hash' => Komfortkasse_Config::getConfig(Komfortkasse_Config::accesscode), 'key' => Komfortkasse_Config::getConfig(Komfortkasse_Config::apikey)));
		
		$contextData = array (
				'method' => 'POST',
				'timeout' => 2,
				'header' => "Connection: close\r\n".
				"Content-Length: ".strlen($query)."\r\n",
				'content'=> $query );
		
		$context = stream_context_create (array ( 'http' => $contextData ));
		
		$result = file_get_contents (
				'http://api.komfortkasse.eu/api/shop/neworder.jsf',
				false,
				$context);
	}
	
	public function info() {
		self::check();
		
		$version = Komfortkasse_Config::getVersion();
		
		$o = '';
		$o = $o . self::kk_csv ( $version );
		$o = $o . self::kk_csv ( self::plugin_version );
		
		$cry = self::kkencrypt ( $o );
		if ($cry === false)
			echo self::kkcrypterror ();
		else
			echo $cry;
	}
	
	
	
	// ------------- helper & encryption functions --------------------
	protected function getNewStatus($status) {
		switch($status) {
			case 'PAID':
				return Komfortkasse_Config::getConfig ( Komfortkasse_Config::status_paid );;
			case 'CANCELLED':
				return Komfortkasse_Config::getConfig ( Komfortkasse_Config::status_cancelled );;
		}
		return null;
	}
	
	protected function check() {
		$ac = Komfortkasse_Config::getRequestParameter ( 'accesscode' );
		
		if (! $ac)
			return;
		if (md5 ( $ac ) != Komfortkasse_Config::getConfig ( Komfortkasse_Config::accesscode ))
			return;
	}
	protected function kkencrypt($s, $encryption = null, $keystring = null) {
		if (! $encryption)
			$encryption = Komfortkasse_Config::getConfig ( Komfortkasse_Config::encryption );
		if (! $keystring)
			$keystring = Komfortkasse_Config::getConfig ( Komfortkasse_Config::publickey );
		
		switch ($encryption) {
			case 'openssl' :
				return self::kkencrypt_openssl ( $s, $keystring );
			case 'mcrypt' :
				return self::kkencrypt_mcrypt ( $s );
			case 'base64' :
				return self::kkencrypt_base64 ( $s );
		}
	}
	protected function kkdecrypt($s, $encryption = null, $keystring = null) {
		if (! $encryption)
			$encryption = Komfortkasse_Config::getConfig ( Komfortkasse_Config::encryption );
		if (! $keystring)
			$keystring = Komfortkasse_Config::getConfig ( Komfortkasse_Config::privatekey );
		
		switch ($encryption) {
			case 'openssl' :
				return self::kkdecrypt_openssl ( $s, $keystring );
			case 'mcrypt' :
				return self::kkdecrypt_mcrypt ( $s );
			case 'base64' :
				return self::kkdecrypt_base64 ( $s );
		}
	}
	protected function kkcrypterror($encryption) {
		if (! $encryption)
			$encryption = Komfortkasse_Config::getConfig ( Komfortkasse_Config::encryption );
		
		switch ($encryption) {
			case 'openssl' :
				return str_replace(":", ";", openssl_error_string ());
		}
	}
	protected function kkencrypt_base64($s) {
		return base64_encode ( $s );
	}
	protected function kkdecrypt_base64($s) {
		return base64_decode ( $s );
	}
	protected function kkencrypt_mcrypt($s) {
		$key = Komfortkasse_Config::getConfig ( Komfortkasse_Config::privatekey );
		$iv = Komfortkasse_Config::getConfig ( Komfortkasse_Config::publickey );
		$td = mcrypt_module_open ( 'rijndael-128', ' ', 'cbc', $iv );
		$init = mcrypt_generic_init ( $td, $key, $iv );
		
		$padlen = (strlen ( $s ) + self::len_mcrypt) % self::len_mcrypt;
		$s = str_pad ( $s, strlen ( $s ) + $padlen, ' ' );
		$encrypted = mcrypt_generic ( $td, $s );
		
		mcrypt_generic_deinit ( $td );
		mcrypt_module_close ( $td );
		
		return base64_encode ( $encrypted );
	}
	protected function kkencrypt_openssl($s, $keystring) {
		$ret = '';
		
		$pubkey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split ($keystring, 64, "\n") . "-----END PUBLIC KEY-----\n";
		$key = openssl_get_publickey ( $pubkey );
		if ($key === FALSE)
			return FALSE;
		
		do {
			$current = substr ( $s, 0, self::maxlen_ssl );
			$s = substr ( $s, self::maxlen_ssl );
			if (openssl_public_encrypt ( $current, $encrypted, $key ) === FALSE)
				return FALSE;
			$ret = $ret . "\n" . base64_encode ( $encrypted );
		} while ( $s );
		
		openssl_free_key ( $key );
		return $ret;
	}
	protected function kkdecrypt_openssl($s, $keystring) {
		$ret = '';
		
		$privkey = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split ($keystring, 64, "\n") . "-----END RSA PRIVATE KEY-----\n";
		$key = openssl_get_privatekey ( $privkey );
		if ($key === FALSE)
			return FALSE;
		
		$parts = explode ( "\n", $s );
		foreach ( $parts as $part ) {
			if ($part) {
				if (openssl_private_decrypt ( base64_decode ( $part ), $decrypted, $key ) === FALSE)
					return FALSE;
				$ret = $ret . $decrypted;
			}
		}
		openssl_free_key ( $key );
		return $ret;
	}
	protected function kkdecrypt_mcrypt($s) {
		$key = Komfortkasse_Config::getConfig ( Komfortkasse_Config::privatekey );
		$iv = Komfortkasse_Config::getConfig ( Komfortkasse_Config::publickey );
		$td = mcrypt_module_open ( 'rijndael-128', ' ', 'cbc', $iv );
		$init = mcrypt_generic_init ( $td, $key, $iv );
		
		$ret = '';
		
		$parts = explode ( "\n", $s );
		foreach ( $parts as $part ) {
			if ($part) {
				$decrypted = mdecrypt_generic ( $td, base64_decode ( $part ) );
				$ret = $ret . trim ( $decrypted );
			}
		}
		mcrypt_generic_deinit ( $td );
		mcrypt_module_close ( $td );
		return $ret;
	}
	protected function kk_csv($s) {
		return "\"" . str_replace ( "\"", "", str_replace ( ";", ",", $s ) ) . "\";";
	}
}

?>