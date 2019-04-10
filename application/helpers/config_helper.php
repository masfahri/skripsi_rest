<?php

class Config{

    public static function validationForm()
    {
      return $config = array(
        'auth/regist_post'  => array(
          array(
            'field' => 'email',
            'label' => 'Email',
            'rules' => 'required|valid_email|is_unique[users.email]|max_length[256]',
            'errors' => [
                'required' => 'Email diperlukan',
                'valid_email' => 'Email tidak Valid',
                'is_unique' => 'Email sudah digunakan',
                'max_length' => 'Email Kelebihan karakter',
            ],
          ),
          array(
            'field' => 'nomor_hp',
            'label' => 'Nomor',
            'rules' => 'required|is_unique[users.nomor_hp]|max_length[14]|numeric',
            'errors' => [
                'required' => 'Nomor Telepon diperlukan',
                'is_unique' => 'Nomor Telepon sudah digunakan',
                'max_length' => 'Nomor Telepon Kelebihan karakter',
                'numeric' => 'Nomor Telepon hanya angka!',
            ],
          ),
        ),
        'auth/otp_post'  => array(
          array(
            'field' => 'token',
            'label' => 'Token',
            'rules' => 'required',
            'errors' => [
                'required' => 'Token diperlukan',
            ],
          ),
          array(
            'field' => 'token',
            'label' => 'Token',
            'rules' => 'required',
            'errors' => [
                'required' => 'Token diperlukan',
            ],
          ),
        ),
        
      );
      
      
      
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
    }

}



?>