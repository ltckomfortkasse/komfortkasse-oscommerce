<?php

/**
 * Komfortkasse
 * Main Class, multi-shop
 */


include_once 'Komfortkasse_Config.php';
include_once 'Komfortkasse_Order.php';
class Komfortkasse {
	const plugin_version = '1.0.10';

	const maxlen_ssl = 117;
	const len_mcrypt = 16;

	// ------------- readorders --------------------
	public function readorders() {
		if (! Komfortkasse_Config::getConfig ( Komfortkasse_Config::activate_export )) {
			return;
		}

		// accesscode pr�fen
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
				if (! $order) {
					continue;
                }

				$o = $o . http_build_query($order);
				$o = $o . "\n";
				// echo $o;
			}
            $cry = Komfortkasse::kkencrypt($o);
            if ($cry === false) {
                Komfortkasse::output(Komfortkasse::kkcrypterror());
            } else {
                Komfortkasse::output($cry);
            }
        }
     // end if
    }

 // end read()

	// ------------- init --------------------
	public function test() {
		$dec = self::kkdecrypt (  Komfortkasse_Config::getRequestParameter ( 'test' ) ) ;
		// echo $dec; die;

		$enc = self::kkencrypt ( $dec );

		echo $enc;
	}

    /**
     * Init.
     *
     * @return void
     */
    public static function init()
    {
        Komfortkasse::output('connection:connectionsuccess|');

        Komfortkasse::output('accesskey:');
        // Set access code.
        $hashed = md5(Komfortkasse_Config::getRequestParameter('accesscode'));
        $current = Komfortkasse_Config::getConfig(Komfortkasse_Config::accesscode);
        if ($current != '' && $current !== 'undefined' && $current != $hashed) {
            Komfortkasse::output('Access Code already set! Shop ' . $current . ', given (hash) ' . $hashed);
            return;
        }

        if ($hashed != Komfortkasse_Config::getRequestParameter('accesscode_hash')) {
            Komfortkasse::output('MD5 Hashes do not match! Shop ' . $hashed . ' given ' . Komfortkasse_Config::getRequestParameter('accesscode_hash'));
            return;
        }

        Komfortkasse_Config::setConfig(Komfortkasse_Config::accesscode, $hashed);
        Komfortkasse::output('accesskeysuccess|');

        Komfortkasse::output('apikey:');
        // Set API key.
        $apikey = Komfortkasse_Config::getRequestParameter('apikey');
        if (Komfortkasse_Config::getConfig(Komfortkasse_Config::apikey) != '' && Komfortkasse_Config::getConfig(Komfortkasse_Config::apikey) !== 'undefined' && Komfortkasse_Config::getConfig(Komfortkasse_Config::apikey) !== $apikey) {
            Komfortkasse::output('API Key already set! Shop ' . Komfortkasse_Config::getConfig(Komfortkasse_Config::apikey) . ', given ' . $apikey);
            return;
        }

        Komfortkasse_Config::setConfig(Komfortkasse_Config::apikey, $apikey);
        Komfortkasse::output('apikeysuccess|');

        Komfortkasse::output('encryption:');
        $encryptionstring = null;
        // Look for openssl encryption.
        if (extension_loaded('openssl') === true) {

            // Look for public&privatekey encryption.
            $kpriv = Komfortkasse_Config::getRequestParameter('privateKey');
            $kpub = Komfortkasse_Config::getRequestParameter('publicKey');
            Komfortkasse_Config::setConfig(Komfortkasse_Config::privatekey, $kpriv);
            Komfortkasse_Config::setConfig(Komfortkasse_Config::publickey, $kpub);

            // Try with rsa.
            $crypttest = Komfortkasse_Config::getRequestParameter('testSSLEnc');
            $decrypt = Komfortkasse::kkdecrypt($crypttest, 'openssl');
            if ($decrypt === 'Can you hear me?') {
                $encryptionstring = 'openssl#' . OPENSSL_VERSION_TEXT . '#' . OPENSSL_VERSION_NUMBER . '|';
                Komfortkasse_Config::setConfig(Komfortkasse_Config::encryption, 'openssl');
            }
        }

        if (!$encryptionstring && extension_loaded('mcrypt') === true) {
            // Look for mcrypt encryption.
            $sec = Komfortkasse_Config::getRequestParameter('mCryptSecretKey');
            $iv = Komfortkasse_Config::getRequestParameter('mCryptIV');
            Komfortkasse_Config::setConfig(Komfortkasse_Config::privatekey, $sec);
            Komfortkasse_Config::setConfig(Komfortkasse_Config::publickey, $iv);

            // Try with mcrypt.
            $crypttest = Komfortkasse_Config::getRequestParameter('testMCryptEnc');
            $decrypt = Komfortkasse::kkdecrypt($crypttest, 'mcrypt');
            if ($decrypt === 'Can you hear me?') {
                $encryptionstring = 'mcrypt|';
                Komfortkasse_Config::setConfig(Komfortkasse_Config::encryption, 'mcrypt');
            }
        }

        // Fallback: base64.
        if (!$encryptionstring) {
            // Try with base64 encoding.
            $crypttest = Komfortkasse_Config::getRequestParameter('testBase64Enc');
            $decrypt = Komfortkasse::kkdecrypt($crypttest, 'base64');
            if ($decrypt === 'Can you hear me?') {
                $encryptionstring = 'base64|';
                Komfortkasse_Config::setConfig(Komfortkasse_Config::encryption, 'base64');
            }
        }

        if (!$encryptionstring) {
            $encryptionstring = 'ERROR:no encryption possible|';
        }

        Komfortkasse::output($encryptionstring);

        Komfortkasse::output('decryptiontest:');
        $decrypt = Komfortkasse::kkdecrypt($crypttest, Komfortkasse_Config::getConfig(Komfortkasse_Config::encryption));
        if ($decrypt === 'Can you hear me?') {
            Komfortkasse::output('ok');
        } else {
            Komfortkasse::output(Komfortkasse::kkcrypterror());
        }

        Komfortkasse::output('|encryptiontest:');
        $encrypt = Komfortkasse::kkencrypt('Yes, I see you!', Komfortkasse_Config::getConfig(Komfortkasse_Config::encryption));
        if ($encrypt !== false) {
            Komfortkasse::output($encrypt);
        } else {
            Komfortkasse::output(Komfortkasse::kkcrypterror());
        }

    }

 // end init()

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

			if (empty ( $id ))
			    continue;

			$order = Komfortkasse_Order::getOrder($id);

			if ($id != $order['number'])
				continue;

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
    protected static function output($s)
    {
        echo $s;

    }
}

?>