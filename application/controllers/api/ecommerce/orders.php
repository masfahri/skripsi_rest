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
class Order extends REST_Controller {

    public function __construct($config='rest') 
    {
        parent::__construct($config);
        $this->load->library('session');
        $this->load->helper('crypt');
        $this->load->helper('jwt');
        $this->load->model('M_user');
        $this->load->model('M_produk');
        
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index_post()
    {
        $CI =& get_instance();
        $token = $this->post('token');
        $alamat = $this->post('alamat');
        
        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required|is_unique[token.token]',
                'errors' => [
                    'required' => '%s diperlukan',
                    'is_unique' => 'Silahkan Selesaikan Pesanan Sebelumnya',
                ],
            ],
            [
                'field' => 'alamat',
                'label' => 'Alamat',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s diperlukan',
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
            $decryptData = array('token' => $token);
            $decrypt     = Crypt::decrypt_($decryptData);
            // var_dump($decrypt);die;
            if ($decrypt != null) {
                if ($decrypt->status = "aktif") {
                    $whereUser = array(
                        'email' => $decrypt->email,
                    );
                    $user = $this->M_user->where('users', $whereUser);
                    $dataToken = array(
                        'user_id' => $user['id'], 
                        'token' => $token, 
                        'created_at' => date('Y-m-d h:i:s'),
                        'updated_at' => date('Y-m-d h:i:s'),
                        'status' => 'order'
                    );
                    $id_token = $this->M_user->create('token', $dataToken);
                    if (!empty($id_token)) {
                        $dataOrder = array(
                            'user_id' => $user['id'], 
                            'token_id' => $id_token, 
                            'alamat' => $alamat,
                            'status' => 'ordered',
                            'created_at' => date('Y-m-d h:i:s'),
                            'updated_at' => date('Y-m-d h:i:s'),
                        );  
                        $id_order = $this->M_user->create('orders', $dataOrder);
                    }
                    return $this->response($decrypt, REST_Controller::HTTP_OK);
                }
            }else{
                return false;
            }
        }
        return false; 
    }

    public function cart_post()
    {
        $token = $this->post('token');
        $produk_id = $this->post('produk_id');
        $qty = $this->post('qty');
        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required|is_unique[token.token]',
                'errors' => [
                    'required' => '%s diperlukan',
                    'is_unique' => '%s expired',
                ],
            ],
            [
                'field' => 'produk_id',
                'label' => 'Produk',
                'rules' => 'required|callback_cekProduk|numeric',
                'errors' => [
                    'required' => '%s diperlukan',
                    'cekProduk' => '%s Tidak Ada',
                ],
            ],
            [
                'field' => 'produk_id',
                'label' => 'Produk',
                'rules' => 'required|callback_cekQty|numeric',
                'errors' => [
                    'required' => '%s diperlukan',
                    'cekQty' => '%s Tidak Ada',
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
            $decryptData = array('token' => $token);
            $decrypt     = Crypt::decrypt_($decryptData);
            if ($decrypt->status = "aktif") {
                $whereUser = array(
                    'email' => $decrypt->email,
                );
                $user = $this->M_user->where('users', $whereUser);
                $dataToken = array(
                    'user_id' => $user['id'], 
                    'token' => $token, 
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                    'status' => 'order'
                );
                $id_token = $this->M_user->create('token', $dataToken);
                if (!empty($id_token)) {
                    $dataOrder = array(
                        'user_id' => $user['id'], 
                        'token_id' => $id_token, 
                        'status' => 'ordered',
                        'created_at' => date('Y-m-d h:i:s'),
                        'updated_at' => date('Y-m-d h:i:s'),
                    );  
                    $id_order = $this->M_user->create('orders', $dataOrder);
                    if (!empty($id_order)) {
                        $insertCart = array(
                            'order_id' => $id_order,
                            'product_id' => $produk_id,
                            'qty' => $qty,
                            'created_at' => date('Y-m-d h:i:s'),
                            'updated_at' => date('Y-m-d h:i:s'),
                        );
                        $insert = $this->M_user->create('order_detail', $insertCart);
                        if ($insert) {
                            $return['message'] = 'Sukses Insert Cart';
                            return $this->response($return, REST_Controller::HTTP_OK);
                        }else{
                            return false;
                        }
                    }else{
                        $updateCart = array(
                            'key' => [
                                'order_id' => $order_id['id'],
                                'product_id' => $produk_id,
                            ],
                            'data' => [
                                'qty' => $qty,
                                'updated_at' => date('Y-m-d h:i:s'),
                            ]
                        );
                        $update = $this->M_produk->updateCart('order_detail', $updateCart);
                        if ($update) {
                            $return['message'] = 'Sukses Update Cart';
                            return $this->response($return, REST_Controller::HTTP_OK);
                        }else{
                            return false;
                        }
                    }
                }
            }
        }
    }

    public function where_user($params)
    {
        $whereUser = array(
            'email' => $params,
        );
        return $user = $this->M_user->where('users', $whereUser);
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
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 10000000000);
        return $validation['JWT'];
    }

    public function token_exists($key)
    {
        var_dump($key);die;
        $cek = $this->M_user->_cekToken($key);
        if ($cek) return true;
    }

    public function cekProduk($key)
    {
        $cek = $this->M_produk->_cekProduk($key);
        if ($cek) return true;
    }

    function cekQty($num)
    {
        $produk_id = $this->post('produk_id');
        $produk = $this->M_produk->_cekQty($produk_id);
        if ($num > $produk['qty'])
        {
            $this->form_validation->set_message(
                            'your_number_field',
                            'The %s field must be less than 24'
                        );
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

}
