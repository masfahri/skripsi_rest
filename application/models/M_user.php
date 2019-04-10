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

    public function update($table, $key, $data)
    {
        $this->db->where($key);
        return $this->db->update($table, $data);
    }

    public function delete($table, $key)
    {
        $this->db->where($key);
        return $this->db->delete($table);
    }

    public function where($table, $key)
    {
        $this->db->where($key);
        $query = $this->db->get($table);
        if( $query->num_rows() > 0 ){
            if( $key !== "" ){
                return $query->row_array();
            }else{
                return $query->result_array();  
            }
            
        }else return null;
    }

    function _cekToken($key)
    {
        $this->db->where('token',$key);
        $query = $this->db->get('token');
        if ($query->num_rows() > 0){
            return true;
        }
        else{
            return false;
        }
    }

























    
}