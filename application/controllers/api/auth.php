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
class Auth extends REST_Controller {

        public function __construct($config = 'rest') {
            parent::__construct($config);
            $this->load->library('session');
            $this->load->model('M_user');
            $this->load->helper('crypt');
            $this->load->helper('jwt');
            date_default_timezone_set('Asia/Jakarta');
        }    

        public function regis_post()
        {
            $CI =& get_instance();
            $tokenData['email'] = $this->post('email');
            $tokenData['nomor_hp'] = $this->post('nomor_hp');
            
            $config = [
                [
                    'field' => 'email',
                    'label' => 'Email',
                    'rules' => 'required|valid_email|is_unique[users.email]|max_length[256]',
                    'errors' => [
                        'required' => 'Email diperlukan',
                        'valid_email' => 'Email tidak Valid',
                        'is_unique' => 'Email sudah digunakan',
                        'max_length' => 'Email Kelebihan karakter',
                    ],
                ],
                [
                    'field' => 'nomor_hp',
                    'label' => 'Nomor',
                    'rules' => 'required|is_unique[users.nomor_hp]|max_length[14]|numeric',
                    'errors' => [
                        'required' => 'Nomor Telepon diperlukan',
                        'is_unique' => 'Nomor Telepon sudah digunakan',
                        'max_length' => 'Nomor Telepon Kelebihan karakter',
                        'numeric' => 'Nomor Telepon hanya angka!',
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
                $auth = array();
                // $auth = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
                $auth['timestamp'] = now();
                $auth['email'] = $tokenData['email'];
                $str_shuffle = JWT::otp($auth['timestamp'], $this->input->server('HTTP_AUTHORIZATION'));
                $auth['otp'] = substr($str_shuffle, 0, 4);  

                 $return['token'] = Crypt::encrypt_($auth);
                 
                 if (!empty($return['token']) && empty($return['otp'])) {
                     if (empty($return['otp'])) {
                        $dataUser = array(
                            'email' => $tokenData['email'], 
                            'nomor_hp' => $tokenData['nomor_hp'], 
                            'created_at' => date('Y-m-d h:i:s'),
                            'status' => 'not_aktif'
                        );
                        $id_user = $this->M_user->create('users', $dataUser);
                        $dataToken = array(
                            'user_id' => $id_user,
                            'status' => 'aktivasi',
                            'token' => $return['token'],
                            'ip_addresses' =>  $this->input->ip_address()    
                        );
                        $return['otp'] = $auth['otp'];
                        if ($this->sms($auth['otp'], $tokenData['nomor_hp'])) {
                            $token = $this->M_user->create('token', $dataToken);
                        }else{
                            return false;
                        }
                            /** PROSES SEND MAIL */
                            // $this->sendMail($output['token']);
                            /** END PROSES SEND MAIL */
                    }else{
                        return false;
                    }
                    return $this->set_response($return, REST_Controller::HTTP_OK); 
                 }else{
                     return false;
                 }
            }
        }

        public function otp_post()
        {
            $CI =& get_instance();
            $otp = $this->post('otp');
            $token = $this->post('token');
            $action = $this->post('action');

            $decrypt = array(
                'n'     => $CI->config->item('key_rsa'), 
                'd'     => $CI->config->item('key_d'), 
                'token' => $token
            );
            
            $config = [
                [
                    'field' => 'token',
                    'label' => 'Token',
                    'rules' => 'required|callback_token_exists',
                    'errors' => [
                        'required' => '%s diperlukan',
                        'token_exists' => '%s Expired',
                    ],
                ],
                [
                    'field' => 'otp',
                    'label' => 'OTP',
                    'rules' => 'required|max_length[4]|min_length[4]',
                    'errors' => [
                        'required' => 'OTP diperlukan',
                        'max_length' => 'OTP Kelebihan karakter',
                        'min_length' => 'OTP Kekurangan karakter',
                    ],
                ],
            ];

            $data = $this->post();
            $this->form_validation->set_data($data);
            $this->form_validation->set_rules($config);

            if ($action == null) {
                if($this->form_validation->run()==FALSE){
                    $output['error'] = $this->form_validation->error_array();
                    return $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
                }else{
                    $validation = array();
                    $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 60);
                    // var_dump($validation['JWT']->timestamp);die;
                
                    if (empty($validation['JWT']->otp)) {
                        $key = array('token' => $token);
                        $dataUser = $this->M_user->where('token', $key);
                        
                         $delToken = $this->M_user->delete('token', array('user_id' => $dataUser['user_id']));
                         $delUsers = $this->M_user->delete('users', array('id' => $dataUser['user_id']));
                    }else{
                        if (!empty($token) && $otp == $validation['JWT']->otp) {
                            if (!empty($otp) && $validation['JWT']) {
                                $statusUser = array('status' => 'pending');
                                $whereUser = array('email' => $validation['JWT']->email);
                                $user = $this->M_user->where('users', $whereUser);
                                if ($user != false) {
                                    $key = array('user_id' => $user['id']);
                                    $token = $this->M_user->where('token', $key);
                                    // var_dump($token);die;
                                    switch ($token['status']) {
                                        case 'aktivasi':
                                            // $this->email($token->token);
                                            $user = $this->M_user->update('users', $whereUser, $statusUser);
                                            $validation['status'] = 'Berhasil Aktivasi Akun';
                                            // $del = $this->M_user->delete('token', $key);
                                            break;
        
                                        case 'pembayaran':
                                            // $del = $this->M_user->delete('token', $key);
                                            $validation['status'] = 'Berhasil Melakukan Pembayaran';
                                            break;    
        
                                        case 'lupa_password':
                                            $validation['status'] = 'Berhasil Ubah Password';
                                            // $del = $this->M_user->delete('token', $key);
                                            break;    
                                        
                                        default:
                                            # code...
                                            break;
                                    }
                                    
                                }
                            }
                            return $this->response($validation, REST_Controller::HTTP_OK);
                        }
                    }
                }
            }else{
                // VALIDASI KETIKA LOGIN
                $decryptData = array('token' => $token);
                $decrypt = Crypt::decrypt_($decryptData);
                $validate = JWT::validateTimestamp(Crypt::decrypt_($decryptData), $decrypt->timestamp);
                if ($validate) {
                    return $this->response($decrypt, REST_Controller::HTTP_OK);
                }
                return false; 
            }
            return false; 
        }

        public function login_post()
        {
            $CI =& get_instance();
            $config = [
                [
                    'field' => 'nomor_hp',
                    'label' => 'Nomor HP',
                    'rules' => 'required|callback_isUser|max_length[13]|min_length[10]',
                    'errors' => [
                        'required' => '%s diperlukan',
                        'isUser' => '%s Tidak Terdaftar',
                        'max_length' => '%s Kelebihan Karakter',
                        'min_length' => '%s Kekurangan Karakter',
                    ],
                ],
            ];

            $data = $this->post();
            $this->form_validation->set_data($data);
            $this->form_validation->set_rules($config);

            if($this->form_validation->run()==FALSE){
                $output = $this->form_validation->error_array();
                $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
            }

            $userData = $this->M_user->_login('users', $this->post('nomor_hp'));
            if (!empty($userData)) {
                $tokenData['nomor'] = $userData['nomor_hp'];
                $tokenData['email'] = $userData['email'];
                $tokenData['status'] = $userData['status'];
                $tokenData['timestamp'] = now();

                $return = $this->cekRole($tokenData);
                $return['action'] = 'login';
            }else{
                $return = 'User tidak ditemukan';
            }
            $data = array(
                'n' => $CI->config->item('key_rs'),
                'e' => $CI->config->item('key_e'),
             );
             return $this->set_response($return, REST_Controller::HTTP_OK);
        }

        public function logins_get()
        {
            $token = $this->get('token');

            // var_dump(base64_encode(hash_hmac('SHA256', $token, 3, TRUE)));die;

            $data = array(
                'n'     => $CI->config->item('key_rsa'), 
                'd'     => $CI->config->item('key_d'),
                'token' => $token,
            );
            $return = Crypt::decrypt_($data);
            // list($return->username, $return->password) = explode(':', base64_decode(substr($return->headers, 6)));
            if ((now() - $return[2] < ($CI->config->item('token_otp_time_out') * 259200))) {
                $res = array(
                    'n' => $return[0], // Nilai N,
                    'e' => $return[1], // Nilai E,
                    'date' => $return[2], // Tanggal Expired,
                    'email' => $return[3]
                );
                return $this->response($res); 
            }else{
                return false;
            }
        }

        public function isUser($key)
        {
            $cek = $this->M_user->_login('users', $key);
            if($cek) return true;

        }

        public function token_exists($key)
        {
           $cek = $this->M_user->_cekToken($key);
           if ($cek) return true;
        }

        public function cekRole($token)
        {
            // $validate = Crypt::decrypt_($token);
            switch ($token['status']) {
                case 'admin':
                    $encrypt = Crypt::encrypt_($token);
                    $return['token'] = $encrypt;
                    $return['timeout'] = 172800;
                    $str_shuffle = JWT::otp($return['timeout'], $this->input->server('HTTP_AUTHORIZATION'));
                    $return['otp'] = substr($str_shuffle, 0, 4);  
                    break;
                case 'aktif':
                    $encrypt = Crypt::encrypt_($token);
                    $return['token'] = $encrypt;
                    $return['timeout'] = 2592000;
                    $str_shuffle = JWT::otp($return['timeout'], $this->input->server('HTTP_AUTHORIZATION'));
                    $return['otp'] = substr($str_shuffle, 0, 4);  
                    break;
                
                default:
                    # code...
                    break;
            }
            return $return;
        }

        public function sms($otp, $nomor_hp)
        {
            $verif = substr(rand(),0,4);
            $msg = "Kode Verifikasi Anda : " .$otp.  ". Kode Ini Hanya Berlaku Selama 2 Menit";
            // Configure client
            $config = Configuration::getDefaultConfiguration();
            $config->setApiKey('Authorization', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhZG1pbiIsImlhdCI6MTU1NDkyMjc1MCwiZXhwIjo0MTAyNDQ0ODAwLCJ1aWQiOjE2NTM3LCJyb2xlcyI6WyJST0xFX1VTRVIiXX0.UAO-fcyaGQoQYDZUW51pB2EYGoFzKYWQiD32CGuElsE');
            $apiClient = new ApiClient($config);
            $messageClient = new MessageApi($apiClient);
            // Sending a SMS Message
            $sendMessageRequest2 = new SendMessageRequest([
                'phoneNumber' => $nomor_hp,
                'message' => $msg,
                'deviceId' => 110817
                ]);
                $sendMessages = $messageClient->sendMessages([
                    $sendMessageRequest2
                ]);
            return true;
        }

       
    }