<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
*
*/
class M_vendor extends CI_Model {

    public function where($table, $key)
    {
        if (!empty($key['id'])) {
            $this->db->where($key);
        }
        $query = $this->db->get($table);
        if( $query->num_rows() > 0 ){
            if( !empty($key['id']) ){
                return $query->row_array();
            }else{
                return $query->result_array();  
            }
            
        }
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

}

?>