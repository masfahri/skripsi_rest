<?php
class JWT
{
    public static function validateTimeStamp($data)
	{
		$CI =& get_instance();
		$round = count((array)$data); 
		$output = [];
		if ($data != false && (now() - $data->timestamp < ($CI->config->item('token_otp_time_out') * 6000))) {
			
            return $data;
        }
        return false;
	}

	public static function otp($timestamp, $auth)
	{
		return str_shuffle($timestamp.str_replace("=", "", $auth));
	}
}