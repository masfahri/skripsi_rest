<?php
class JWT
{
    public static function validateTimeStamp($data, $timeout)
	{
		$CI =& get_instance();
		$round = count((array)$data); 
		$output = [];
		if ($data != false && (now() - $data->timestamp < ($CI->config->item('token_otp_time_out') * $timeout))) {
            return $data;
        }
        return false;
	}

	public static function otp($timestamp, $auth)
	{
		$authBersih = preg_replace('/[^\\pL\d_]+/u', '', $auth);
		return str_shuffle($timestamp.str_replace("=/\s+/", "", $authBersih));
	}
}