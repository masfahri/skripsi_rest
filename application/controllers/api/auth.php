<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

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
                $output['error'] = $this->form_validation->error_array();
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
                        $token = $this->M_user->create('token', $dataToken);
                        $return['otp'] = $auth['otp'];

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

            $decrypt = array(
                'n'     => $CI->config->item('key_rsa'), 
                'd'     => $CI->config->item('key_d'), 
                'token' => $token
            );

            $validation = JWT::validateTimestamp(Crypt::decrypt_($decrypt));
            if (!empty($token) && $otp == $validation->otp) {
                if (!empty($otp) && $validation) {
                    $data = array('status' => 'pending');
                    $user = $this->M_user->where('users', $validation->email, 'email');
                    if ($user != false) {
                        $key = array('user_id' => $user['id']);
                        $del = $this->M_user->delete('token', $key);
                    }
                }
                return $this->response($validation, REST_Controller::HTTP_OK);
            }
            
            return false; 

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
            if ((now() - $return[2] < ($CI->config->item('token_otp_time_out') * 100))) {
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

        public function index_get(){
        //    $r = $this->user_model->read();
           $r['data'] = $this->user_model->read();
           $r['code'] = REST_Controller::HTTP_OK;
           $r['message'] = 'sukses';
           $this->response($r); 
        }
        
        public function index_put(){
           $id = $this->uri->segment(3);

           $data = array('name' => $this->input->get('name'),
           'pass' => $this->input->get('pass'),
           'type' => $this->input->get('type')
           );

            $r = $this->user_model->update($id,$data);
               $this->response($r); 
        }

       public function index_post(){
           $user = $this->post('user');
           $arr = array(
               'user' => $user,
               'pass' => md5($this->post('pass')));
            if ($this->post('action') == 'login') {
                $r = $this->user_model->login($user, md5($this->post('pass')));  
                if ($r['count'] == 1) {
                    $level = $r['data']->level;
                    $r['code'] = REST_Controller::HTTP_OK;
                    switch ($level) {
                        case 'admin':
                        $r['message'] = 'Anda '.$level;
                        break;
                        
                        case 'pegawai':
                        $r['message'] = 'Anda '.$level;
                        break;
                        
                        case 'pimpinan':
                        $r['message'] = 'Anda '.$level;
                        break;
                        
                        default:
                        $r['message'] = 'Tidak berhak mengakses';
                            break;
                    }
                }else{
                    $r['data'] = 'Not User';
                    $r['code'] = REST_Controller::HTTP_NOT_FOUND;
                }
                
                $this->response($r);
            }
       }

       public function index_delete(){
           $id = $this->uri->segment(3);
           $r = $this->user_model->delete($id);
           $this->response($r); 
       }
    }