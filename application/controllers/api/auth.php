<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/helpers/jwt_helper.php';

use Restserver\Libraries\REST_Controller;

class Auth extends REST_Controller {

        public function __construct($config = 'rest') {
            parent::__construct($config);
            $this->load->model('user_model');
            $this->load->library('session');
            $this->load->helper('crypt');
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
                        'max_length[256]' => 'Email Kelebihan karakter',
                    ],
                ],
                [
                    'field' => 'nomor_hp',
                    'label' => 'Nomor',
                    'rules' => 'required|is_unique[users.nomor_telf]|max_length[14]|numeric',
                    'errors' => [
                        'required' => 'Nomor Telepon diperlukan',
                        'is_unique' => 'Nomor Telepon sudah digunakan',
                        'max_length[14]' => 'Nomor Telepon Kelebihan karakter',
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
                $tokenData = array();
                $auth = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
                $tokenData[2] = now();
                $tokenData[3] = $this->post('email');
                $tokenData[4] = $this->post('nomor_hp');
                
                    // var_dump($this->input->server('HTTP_AUTHORIZATION'));die;
                    // var_dump(base64_encode(serialize($auth)));
                    // var_dump(($auth));die;
                    // var_dump($serialize_b64);
                    // var_dump(Urlsafe::urlsafeB64Encode(base64_encode(serialize($header))));
                    // var_dump($unserialize);
                    // var_dump(Urlsafe::urlsafeB64Encode($serialize_b64));die;
                 $data = array(
                     'headers' => $this->input->server('HTTP_AUTHORIZATION'),
                     'date' => now()
                 );
                //  $return['token'] = Crypt::encrypt_($auth);
                 $return['token'] = JWT::encode($auth, $tokenData);
                 $return['time'] = $tokenData[2];
                 
                 $this->set_response($return, REST_Controller::HTTP_OK); 
            }
        }

        public function logins_get()
        {
            $CI =& get_instance();
            $token = $this->get('token');

            // var_dump(base64_encode(hash_hmac('SHA256', $token, 3, TRUE)));die;

            $data = array(
                'n'     => $CI->config->item('key_rsa'), 
                'd'     => $CI->config->item('key_d'),
                'token' => $token,
            );
            // $return = Crypt::decrypt_($data);
            $return['token'] = JWT::decode($token);
            // var_dump($return['token']->{2});die;
            // list($return->username, $return->password) = explode(':', base64_decode(substr($return->headers, 6)));
            // ITUNGAN 1 MENIT
            if ((now() - $return['token']->{2} < ($CI->config->item('token_otp_time_out') * 600))) {
                $res = array(
                    'email' => $return['token']->{3}, // Nilai N,
                    'nomor_hp' => $return['token']->{4}, // Nilai E,
                    'date' => $return['token']->{2}, // Tanggal Expired,
                );
                return $this->response($res); 
            }else{
                return false;
            }
            return $this->response($return); 
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