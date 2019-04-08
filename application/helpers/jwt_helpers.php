<?php
require APPPATH . '/helpers/urlsafe_helper.php';
class JWT
{
    public static function encode($payload, $key, $algo = 'HS256')
	{
		$CI =& get_instance();
		// Segment 1
		$header = ;
		// End Segment 1
		// var_dump($header);die;

		$segments = array();
		$segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($header));
		$segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
		$signing_input = implode('.', $segments);

		$signature = JWT::sign($signing_input, $key, $algo);
		$segments[] = JWT::urlsafeB64Encode($signature);

		return implode('.', $segments);
	}
}