<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
*
*/
class M_user extends CI_Model {


    public function create($table, $data)
    {
        $qry = $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    public function update($table, $data, $key, $field)
    {
        $this->db->where($field, $key);
        return $this->db->update($table, $data);
    }

    public function delete($table, $key)
    {
        $this->db->where($key);
        return $this->db->delete($table);
    }

























    
}