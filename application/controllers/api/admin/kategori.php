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
        $this->load->model('Crud');
        
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

    public function index_get()
    {
        $token = $this->get('token');
        $id = $this->get('id');

        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
        ];

        $data = $this->get();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array('id' => $id);
                if (empty($id)) {
                    $return['kategori_produk'] = $this->Crud->where('kategori_produk', $data);
                }else{
                    $return['kategori_produk'] = array($this->Crud->where('kategori_produk', $data));

                }
            }
        }
        return $this->set_response($return, REST_Controller::HTTP_OK); 
    }

    public function edit_post()
    {
        $token = $this->post('token');
        $nama_kategori = $this->post('nama_kategori');
        $id = $this->post('id');

        $config = [
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
                    'nama_kategori' => $nama_kategori, 
                );
                $key = array('id' => $id);
                $update = $this->Crud->update('kategori_produk', $key, $data);
                if ($update) {
                    $output['message'] = 'sukses update';
                    $code = REST_Controller::HTTP_OK;
                }else{
                    $output['message'] = 'gagal update';
                    $code = REST_Controller::HTTP_BAD_REQUEST;
                }
            }else{
                return false;
            }
            $this->set_response($output, $code);
        }
    }

    public function delete_post()
    {
        $token = $this->post('token');
        $id = $this->post('id');

        $config = [
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
                
                $key = array('id' => $id);
                $update = $this->M_vendor->delete('kategori_produk', $key);
                if ($update) {
                    $output['message'] = 'sukses delete';
                    $code = REST_Controller::HTTP_OK;
                }else{
                    $output['message'] = 'gagal delete';
                    $code = REST_Controller::HTTP_BAD_REQUEST;
                }
            }else{
                return false;
            }
            $this->set_response($output, $code);
        }
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
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 1728000);
        return $validation['JWT'];
    }

    public function cek_kategori($key)
    {
        $data = array('nama_kategori' => $kategori);
        $cekKategori = $this->M_user->count('kategori_produk', $data);
    }

}
