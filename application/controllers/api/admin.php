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
class Admin extends REST_Controller {

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

    public function kategori_post()
    {
        $CI =& get_instance();
        $token = $this->post('token');
        $kategori = $this->post('kategori');
        $decrypt = array(
            'n'     => $CI->config->item('key_rsa'), 
            'd'     => $CI->config->item('key_d'), 
            'token' => $token
        );
        $validation = array();
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 10000000);
        if ($validation['JWT']->status == 'admin') {
            $data = array('nama_kategori' => $kategori);
            $cekKategori = $this->M_user->count('kategori_produk', $data);
            if ($cekKategori) {
                $validation['message'] = 'Kategori Sudah Ada!';
            }else{
                $insertKategori = $this->M_user->create('kategori_produk', $data);
                $validation['message'] = 'Sukses Masukan Kategori';
            }
        }else{
            return false;
        }
        return $this->response($validation, REST_Controller::HTTP_OK);
    }

    public function vendor_post()
    {
        $token = $this->post('token');
        $nama_vendor = $this->post('nama_vendor');
        $email = $this->post('email');
        $nomor_hp = $this->post('nomor_hp');
        $alamat = $this->post('alamat');

        $config = [
            [
                'field' => 'email',
                'label' => 'Email',
                'rules' => 'required|valid_email|is_unique[vendor.email]|max_length[256]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'valid_email' => '%s Tidak Valid',
                    'is_unique' => '%s Sudah digunakan',
                    'max_length' => '%s Kelebihan karakter',
                ],
            ],
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
            [
                'field' => 'nomor_hp',
                'label' => 'Nomor HP',
                'rules' => 'required|max_length[13]|min_length[10]|is_unique[vendor.nomor_hp]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'is_unique' => '%s Telah Digunakan',
                    'max_length' => '%s Kelebihan karakter',
                    'min_length' => '%s Kekurangan karakter',
                ],
            ],
        ];

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array(
                    'nama_vendor' => $nama_vendor,
                    'email' => $email,
                    'nomor_hp' => $nomor_hp,
                    'alamat' => $alamat,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                );
                if($this->M_user->create('vendor', $data)) {
                    $validation['message'] = 'Sukses Insert Vendor';
                }else{
                    $validation['message'] = 'Gagal Insert Vendor';
                }
            }else{
                return false;
            }
            return $this->set_response($validation, REST_Controller::HTTP_OK); 
        }
    }
    
    public function produk_post()
    {
        $image_path = $_FILES['img_cover']['tmp_name'];

        $token = $this->post('token');
        $vendor_id = $this->post('vendor_id');
        $nama_produk = $this->post('nama_produk');
        $kategori_produk = $this->post('kategori_produk');
        $slug_ = $this->post('slug');
        $slug = str_replace(" ", "-", $nama_produk);
        $imageCover = $this->base64($image_path);
        
        $config = [
            [
                'field' => 'vendor_id',
                'label' => 'Vendor',
                'rules' => 'required|max_length[256]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'max_length' => '%s Kelebihan karakter',
                ],
            ],
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
        ];

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);

        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array(
                    'vendor_id' => $vendor_id,
                    'nama_produk' => $nama_produk,
                    'kategori_produk' => $kategori_produk,
                    'slug' => $slug,
                    'img_cover' => $imageCover,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                );
                if($this->M_user->create('produk', $data)) {
                    $validation['message'] = 'Sukses Insert Produk';
                }else{
                    $validation['message'] = 'Gagal Insert Produk';
                }
            }else{
                return false;
            }
            return $this->set_response($validation, REST_Controller::HTTP_OK); 
        }
    }




    public function base64($params)
    {
        $info = pathinfo($params, PATHINFO_EXTENSION);
        $data = file_get_contents($params);
        $bas64 = 'data:image/' . $info . ';base64, ' . base64_encode($data);
        return $bas64;
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
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 10000000);
        return $validation['JWT'];
    }
    

}

?>