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
        $this->load->model('M_cart');

        date_default_timezone_set('Asia/Jakarta');
    }

    public function cart_post()
    {
        $CI =& get_instance();
        $tokenUser = $this->post('token');
        $produkId = $this->post('produk_id');
        $qty = $this->post('qty');
        
        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s diperlukan',
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
                'field' => 'qty',
                'label' => 'Jumlah Barang',
                'rules' => 'required|callback_cekQty|numeric',
                'errors' => [
                    'required' => '%s diperlukan',
                    'cekQty' => '%s Melebihi Stok Barang',
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
            $decryptData = array('token' => $tokenUser);
            $decrypt     = $this->_decrypt($decryptData, 10000);
            $users = $this->where_user($decrypt->email);
            if ($decrypt) {
                if ($decrypt->status = "aktif") {
                    $encrypt['produkId'] = $produkId;
                    $encrypt['qty'] = $qty;
                    $encrypt['id_user'] = $users['id'];
                    $encrypt['timeout'] = now();
                    $token = Crypt::encrypt_($encrypt);
                    $cekTokenUser = $this->where_user_token($users['id']);

                    if (empty($cekTokenUser)) {
                        $insertToken = array(
                            'user_id'     => $users['id'],
                            'token'       => $token,
                            'status'      => 'order',
                            'created_at' => date('Y-m-d h:i:s'),
                            'updated_at' => date('Y-m-d h:i:s'),
                          );
                        $tokenId = $this->M_user->create('token', $insertToken);    
                        if (!empty($tokenId)) {
                            $insertOrder = array(
                                'token_id' => $tokenId, 
                                'user_id' => $users['id'],
                                'status' => 'ordered',
                                'created_at' => date('Y-m-d h:i:s'),
                                'updated_at' => date('Y-m-d h:i:s'),
                            );
                            $orderId = $this->M_user->create('orders', $insertOrder);    
                        }
                    }else{
                        $whereTokenCart = array(
                            'token_id' => $cekTokenUser['id'],
                        );
                        $idTokenUser = $this->M_cart->where('orders', $whereTokenCart);
                        $orderId = $idTokenUser['id'];
                        
                    }
                    $cekCart = $this->cekCart($orderId, $produkId);
                    if (empty($cekCart)) {
                        $insertCart = array(
                            'order_id' => $orderId, 
                            'produk_id' => $produkId,
                            'qty' => $qty,
                            'created_at' => date('Y-m-d h:i:s'),
                            'updated_at' => date('Y-m-d h:i:s'),
                        );
                        $cartId = $this->M_user->create('order_detail', $insertCart);    
                        if ($cartId) {
                            $return['message'] = 'Sukses Insert Cart';
                            return $this->response($return, REST_Controller::HTTP_OK);
                        }else{
                            return false;
                        }
                    }else{
                        $updateCart = array(
                            'key' => [
                                'order_id' => $idTokenUser['id'],
                                'produk_id' => $produkId,
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
            }else{
                return false;
            }
        }
        // return false; 
    }

    public function cekCart($orderId, $produkId)
    {
        $whereUser = array(
            'order_id' => $orderId,
            'produk_id' => $produkId,
        );
        return $cek = $this->M_user->where('order_detail', $whereUser);
    }

    public function where_user($params)
    {
        $whereUser = array(
            'email' => $params,
        );
        return $user = $this->M_user->where('users', $whereUser);
    }
    
    public function where_user_token($userId)
    {
        $whereUser = array(
            'user_id' => $userId,
        );
        return $user = $this->M_user->where('token', $whereUser);
    }

    public function _decrypt($token, $expired)
    {
        $CI =& get_instance();
        $decrypt = array(
            'n'     => $CI->config->item('key_rsa'), 
            'd'     => $CI->config->item('key_d'), 
            'token' => $token
        );
        $validation = array();
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($token), $expired);
        return $validation['JWT'];
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
