<?php
require APPPATH . '/helpers/urlsafe_helper.php';
class Crypt
{
    public static function encrypt_($data)
    {
        $hasil="";
        $e = 5;
        $n = 611;
        $jse = json_encode($data);
        for($i=0;$i<strlen($jse);++$i){
            //rumus enkripsi <enkripsi>=<pesan>^<e>mod<n>
            $hasil .= gmp_strval(gmp_mod(gmp_pow(ord($jse[$i]),$e),$n));
            //antar tiap karakter dipisahkan dengan "."
            if($i!=strlen($jse)-1){
                $hasil.=".";
            }
        }
        $base = strtr(base64_encode(addslashes(gzcompress(serialize($hasil),9))), '+/=', '-_,');
        $safe = Urlsafe::urlsafeB64Encode($base);
        return $safe;

    }

    public static function decrypt_($data)
    {
        $hasil = "";
        $d = 221;
        $n = 611;
        if (empty($data['token'])) {
            return false;
        }else{
            $safe = Urlsafe::urlsafeB64Decode($data['token']);
            $cipher = unserialize(gzuncompress(stripslashes(base64_decode(strtr($safe, '-_,', '+/=')))));
    
            $teks = explode(".",$cipher);
            foreach($teks as $nilai){
                //rumus enkripsi <pesan>=<enkripsi>^<d>mod<n>
                $hasil .= chr(gmp_strval(gmp_mod(gmp_pow($nilai,$d),$n)));
            }
            return json_decode($hasil);
        }
    }

    public static function randomString($length = 10, $data)
    {
        // $token = 
    }
}

?>