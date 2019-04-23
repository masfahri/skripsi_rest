<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
*
*/
class Crud extends CI_Model {

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

    public function join($select, $table, $join, $where=null)
    {
        $this->db->select($select);
        $this->db->from($table[0]);
        if (!$where == null) {
            $this->db->where($where);
        }
        $this->db->join($table[1], $join);
        $qry = $this->db->get();
        return $qry->result_array();
    }

    public function join2table($select, $table, $join, $where=null)
    {
        $this->db->select($select);
        $this->db->from($table[0]);
        if (!$where == null) {
            $this->db->where($where);
        }
        $this->db->join($table[1], $join[0]);
        $this->db->join($table[2], $join[1]);
        $qry = $this->db->get();
        return $qry->result_array();
    }

}

?>