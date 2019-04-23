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
class Produk extends REST_Controller {

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
            $return = $this->form_validation->error_array();
            $this->set_response($return, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array('id' => $id);
                if (empty($id)) {
                    $select = array('produk.id', 'nama_vendor', 'nama_produk', 'img_cover', 'slug', 'vendor_id');
                    $table = array('vendor', 'produk');
                    $join = 'produk.vendor_id = vendor.id';
                    $return['produk'] = $this->Crud->join($select, $table, $join);
                    // var_dump($return['produk']);die;
                }else{
                    $select = array('produk.id', 'nama_vendor', 'nama_produk', 'img_cover', 'slug', 'vendor_id', 'deskripsi_produk');
                    $table = array('produk', 'vendor', 'detail_produk');
                    $join = array('produk.vendor_id = vendor.id', 'produk.id = detail_produk.produk_id');
                    $return['produk'] = $this->Crud->join2table($select, $table, $join, $data);
                }
            }
        }
        return $this->set_response($return, REST_Controller::HTTP_OK); 
    }

    public function index_post()
    {
        $token = $this->post('token');
        $vendor_id = $this->post('vendor_id');
        $nama_produk = $this->post('nama_produk');
        $kategori_produk = $this->post('kategori_produk');
        $slug_ = $this->post('slug');
        $slug = str_replace(" ", "-", $nama_produk);
        if (!empty($_FILES['img_cover'])) {
            $image_path = $_FILES['img_cover']['tmp_name'];
            $this->validate_image();

            $imageCover = $this->base64($image_path);
        
            $config = [
                [
                    'field' => 'nama_produk',
                    'label' => 'Nama Produk',
                    'rules' => 'trim|required|max_length[256]|is_unique[produk.nama_produk]',
                    'errors' => [
                        'required' => '%s Diperlukan',
                        'is_unique' => '%s Sudah Ada',
                        'max_length' => '%s Kelebihan karakter',
                    ],
                ],
                [
                    'field' => 'vendor_id',
                    'label' => 'Vendor',
                    'rules' => 'required|max_length[256]|callback_cek_vendor',
                    'errors' => [
                        'required' => '%s Diperlukan',
                        'cek_vendor' => '%s Tidak Ada',
                        'max_length' => '%s Kelebihan karakter',
                    ],
                ],
                [
                    'field' => 'kategori_produk',
                    'label' => 'Kategori',
                    'rules' => 'required|max_length[256]|callback_cek_kategori',
                    'errors' => [
                        'required' => '%s Diperlukan',
                        'cek_kategori' => '%s Tidak Ada',
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
        } else {
            $return['message'] = 'Harap Masukan Gambar Cover!';
            return $this->set_response($return, REST_Controller::HTTP_BAD_REQUEST); 
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
                $update = $this->M_vendor->delete('produk', $key);
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

    public function detail_post()
    {
        $token = $this->post('token');
        $produk_id = $this->post('produk_id');
        $deskripsi_produk = $this->post('deskripsi_produk');
        $qty = $this->post('qty');
        $harga = $this->post('harga');
        $config = [
            [
                'field' => 'deskripsi_produk',
                'label' => 'Deskripsi Produk',
                'rules' => 'trim|required|max_length[256]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'max_length' => '%s Kelebihan karakter',
                ],
            ],
            [
                'field' => 'produk_id',
                'label' => 'Produk',
                'rules' => 'required|max_length[256]|callback_cek_produk|is_unique[detail_produk.produk_id]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'is_unique' => '%s Sudah ada Detail',
                    'cek_produk' => '%s Tidak Ada',
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
                'field' => 'qty',
                'label' => 'Quantity',
                'rules' => 'required|callback_maximumCheck|numeric',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'maximumCheck' => '%s Melebihi Kapasitas',
                    'numeric' => '%s Hanya Angka',
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
            $prodId = array('id' => $produk_id);
            $namaProduk = $this->M_user->where('produk', $prodId);
            
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $arrayName[] = array();
                for ($i=0; $i < 10; $i++) { 
                    if (!isset($_FILES['img_detail'.$i])) {
                        $_FILES['img_detail'.$i] = null;
                    }
                    if (isset($_FILES['img_detail'.$i])) {
                        $image_path[] = $_FILES['img_detail'.$i];
                        $arrayName = $_FILES['img_detail'.$i];
                    }
                }
                $upload = $this->_doUpload($image_path, $namaProduk['nama_produk']);

                $data = array(
                    'produk_id' => $produk_id,
                    'deskripsi_produk' => $deskripsi_produk,
                    'qty' => $qty,
                    'harga' => $harga,
                    'img_detail' => $upload,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                );
                if($this->M_user->create('detail_produk', $data)) {
                    $validation['message'] = 'Sukses Insert Detail_Produk';
                }else{
                    $validation['message'] = 'Gagal Insert Detail_Produk';
                }
            }else{
                return false;
            }
            return $this->set_response($validation, REST_Controller::HTTP_OK); 
        }
        
        // foreach ($jeson as $json) {
        //     var_dump(json_decode($json));    
        // }
        // die;
    }

    public function detail_get()
    {
        $token = $this->get('token');
        $vendor_id = $this->get('vendor_id');

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
                $data = array('id' => $vendor_id);
                if (empty($vendor_id)) {
                    $return['vendor'] = $this->M_vendor->where('vendor', $data);
                }else{
                    $return['vendor'] = array($this->M_vendor->where('vendor', $data));

                }
            }
        }
        return $this->set_response($return, REST_Controller::HTTP_OK); 
    }

    

    public function _doUpload($params, $namaProduk)
    {
        $tambah = count($params) + 1;
        for ($i=0; $i < count($params); $i++) { 
            if (isset($params[$i])) {
                if ($params[$i]['size'] > 0) {
                    $name = str_replace(" ","_", $namaProduk)."_".$i;
                    $arrayName[] = array('img_detail'.$i => $name);
                    $config['upload_path'] = '___/upload/produk';
                    $config['allowed_types'] = 'gif|jpg|png';
                    $config['max_size'] = 200000;
                    $config['max_height'] = 10000;
                    $config['max_width'] = 10000;
                    $config['overide'] = TRUE;
                    $config['file_name'] = str_replace(" ","_", $namaProduk)."_".$i;
                    // $config['max_filename'] = 25;
                    $this->load->library('upload');
                    $this->upload->initialize($config);
                    
                    if (!$this->upload->do_upload('img_detail'.$i)) {
                        $error = $this->upload->display_errors();
                        $this->set_response([
                            'status' => FALSE,
                            'message' => $error
                        ], 404);
                    }
                }
            }
        }
        return json_encode($arrayName);
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

    public function cek_vendor($key)
    {
        $data = array('id' => $key);
        $cek = $this->M_user->_isNotExists($data, 'vendor');
        if($cek){
            return false;
        } else{
            return true;
        }
    }

    public function cek_kategori($key)
    {
        $data = array('id' => $key);
        $cek = $this->M_user->_isNotExists($data, 'kategori_produk');
        if($cek){
            return false;
        } else{
            return true;
        }
    }

    public function cek_produk($key)
    {
        $data = array('id' => $key);
        $cek = $this->M_user->_isNotExists($data, 'produk');
        if($cek){
            return false;
        } else{
            return true;
        }
    }

    public function validate_image() 
    {
        $check = TRUE;
        if ((!isset($_FILES['img_cover'])) || $_FILES['img_cover']['size'] == 0) {
            $this->form_validation->set_message('validate_image', 'The {field} field is required');
            $check = FALSE;
        }
        else if (isset($_FILES['img_cover']) && $_FILES['img_cover']['size'] != 0) {
            $allowedExts = array("jpeg", "jpg", "png", "JPG", "JPEG", "PNG");
            $allowedTypes = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
            $extension = pathinfo($_FILES["img_cover"]["name"], PATHINFO_EXTENSION);
            $detectedType = exif_imagetype($_FILES['img_cover']['tmp_name']);
            $type = $_FILES['img_cover']['type'];
            if (!in_array($detectedType, $allowedTypes)) {
                $this->form_validation->set_message('validate_image', 'Invalid Image Content!');
                $check = FALSE;
            }
            if(filesize($_FILES['img_cover']['tmp_name']) > 200000) {
                $this->form_validation->set_message('validate_image', 'The Image file size shoud not exceed 2MB!');
                $check = FALSE;
            }
            if(!in_array($extension, $allowedExts)) {
                $this->form_validation->set_message('validate_image', "Invalid file extension {$extension}");
                $check = FALSE;
            }
        }
        return $check;
    }

    function maximumCheck($num)
    {
        if ($num > 100)
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