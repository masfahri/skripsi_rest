<?php
// require APPPATH . '/helpers/urlsafe_helper.php';
class JWT
{
    public static function encode($header, $payload)
	{
		$CI =& get_instance();
		// Segment 1
		// $header = ;
		// End Segment 1
		$key = $CI->config->item('key_rsa');
		$segments = array();
		$segments[] = Urlsafe::urlsafeB64Encode(self::jsonEncode($header));
		$segments[] = Urlsafe::urlsafeB64Encode(self::jsonEncode($payload));
		$signing_input = implode('.', $segments);
		

		$signature = Crypt::encrypt_($signing_input, $key);
		$segments[] = Urlsafe::urlsafeB64Encode($signature);
		// var_dump($segments);die;

		return implode('.', $segments);
	}

	public static function decode($jwt, $verify = true)
	{
		$tks = explode('.', $jwt);
		if (count($tks) != 3) {
			return false;
		}
		list($headb64, $bodyb64, $cryptob64) = $tks;
		// var_dump(self::jsonDecode(Urlsafe::urlsafeB64Decode($headb64)));
		// var_dump(self::jsonDecode(Urlsafe::urlsafeB64Decode($bodyb64)));
		// var_dump(Urlsafe::urlsafeB64Decode($cryptob64));
		if (null === ($header = self::jsonDecode(Urlsafe::urlsafeB64Decode($headb64)))) {
			return false;
		}
		// var_dump(self::jsonDecode(Urlsafe::urlsafeB64Decode($bodyb64)));die;
		if (null === $payload = self::jsonDecode(Urlsafe::urlsafeB64Decode($bodyb64))) {
			return false;
		}
		$sig = Urlsafe::urlsafeB64Decode($cryptob64);
		if ($verify) {
			if (empty($header)) {
				return false;
			}
			// if ($sig != Crypt::decrypt("$headb64.$bodyb64")) {
			// 	throw new UnexpectedValueException('Signature verification failed');
			// }
		}
		return $payload;

	}

	public static function jsonEncode($input)
	{
		$json = json_encode($input);
		if (function_exists('json_last_error') && $errno = json_last_error()) {
			JWT::_handleJsonError($errno);
		} else if ($json === 'null' && $input !== null) {
			throw new DomainException('Null result with non-null input');
		}
		return $json;
	}

	public static function jsonDecode($input)
	{
		$obj = json_decode($input);
		if (function_exists('json_last_error') && $errno = json_last_error()) {
			JWT::_handleJsonError($errno);
		} else if ($obj === null && $input !== 'null') {
			throw new DomainException('Null result with non-null input');
		}
		return $obj;
	}

	private static function _handleJsonError($errno)
	{
		$messages = array(
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
		);
		throw new DomainException(
			isset($messages[$errno])
			? $messages[$errno]
			: 'Unknown JSON error: ' . $errno
		);
	}
}