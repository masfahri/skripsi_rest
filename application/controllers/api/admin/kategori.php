<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/smsgateway/autoload.php';
use SMSGatewayMe\Client\ApiClient;
use SMSGatewayMe\Client\Configuration;
use SMSGatewayMe\Client\Api\MessageApi;
use SMSGatewayMe\Client\Model\SendMessageRequest;

header('Access-Control-Allow-Origin: *');
class Kategori extends REST_Controller {

    public function __construct($config='rest') 
    {
        parent::__construct($config);
        $this->load->library('session');
        $this->load->helper('crypt');
        $this->load->helper('jwt');
        $this->load->model('M_user');
        $this->load->model('M_vendor');
        
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index_post()
    {
        $CI =& get_instance();
        $token = $this->post('token');
        $kategori = $this->post('kategori');

        $validation = array();
        $validation['JWT'] = $this->_decrypt($token);
        if ($validation['JWT']->status == 'admin') {
            $data = array('nama_kategori' => $kategori);
            $cekKategori = $this->M_user->count('kategori_produk', $data);
            if ($cekKategori) {
                $validation['message'] = 'Kategori Sudah Ada!';
                $code = REST_Controller::HTTP_CONFLICT;
            }else{
                $insertKategori = $this->M_user->create('kategori_produk', $data);
                $validation['message'] = 'Sukses Masukan Kategori';
                $code = REST_Controller::HTTP_OK;
            }
        }else{
            return false;
        }
        return $this->response($validation, $code);
    }

    public function _decrypt($token)
    {
        $CI =& get_instance();
        $decrypt = array(
            'n'     => $CI->config->item('key_rsa'), 
            'd'     => $CI->config->item('key_d'), 
            'token' => $token
        );
        $validation = array();
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 172800);
        return $validation['JWT'];
    }

    public function cek_kategori($key)
    {
        $data = array('nama_kategori' => $kategori);
        $cekKategori = $this->M_user->count('kategori_produk', $data);
    }

}
