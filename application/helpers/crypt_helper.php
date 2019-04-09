<?php
require APPPATH . '/helpers/urlsafe_helper.php';
class Crypt
{
    public static function encrypt_($data, $key)
    {
        $hasil="";
        $e = 5;
        $jse = json_encode($data);
        for($i=0;$i<strlen($jse);++$i){
            //rumus enkripsi <enkripsi>=<pesan>^<e>mod<n>
            $hasil .= gmp_strval(gmp_mod(gmp_pow(ord($jse[$i]),$e),$key));
            //antar tiap karakter dipisahkan dengan "."
            if($i!=strlen($jse)-1){
                $hasil.=".";
            }
        }
        $base = strtr(base64_encode(addslashes(gzcompress(serialize($hasil),9))), '+/=', '-_,');
        $safe = Urlsafe::urlsafeB64Encode($base);
        return $safe;

    }

    public static function decrypt($data)
    {
        $hasil = array();
        $d = 221;
        $n = 611;
        $safe = Urlsafe::urlsafeB64Decode($data);
        $tks = explode('.', $data);
        for ($i=0; $i < count($tks); $i++) { 
            $hasil[] = JWT::jsonDecode(Urlsafe::urlsafeB64Decode($tks[$i]));
        }
        return(json_encode($hasil));
    }

    public static function decrypt_($data)
    {
        $hasil = "";
        $d = 221;
        $n = 611;
        $safe = Urlsafe::urlsafeB64Decode($data);
        $tks = explode('.', $data);
        var_dump(JWT::jsonDecode(Urlsafe::urlsafeB64Decode($tks[0])));
        var_dump(JWT::jsonDecode(Urlsafe::urlsafeB64Decode($tks[1])));die;

        $cipher = unserialize(gzuncompress(stripslashes(base64_decode(strtr($tks[0], '-_,', '+/=')))));

        $teks = explode(".",$safe);
        foreach($teks as $nilai){
            //rumus enkripsi <pesan>=<enkripsi>^<d>mod<n>
            $hasil .= chr(gmp_strval(gmp_mod(gmp_pow($nilai,$d),$n)));
        }
        return json_decode($hasil);
    }
}

?>