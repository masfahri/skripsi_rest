<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
*
*/
class M_produk extends CI_Model {


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

    public function count($table, $key)
    {
        $this->db->where($key);
        $qry = $this->db->get($table);
        if ($qry->num_rows() > 0) {
            return true;
        }else{
            return false;
        }
    }

    public function updateCart($table, $params)
    {
        $this->db->where($params['key']);
        return $this->db->update($table, $params['data']);
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

    public function join($table, $join, $id)
    {
        $this->db->from($table[0]);
        $this->db->join($table[1], $join);
        return $this->db->get();
    }

    function _cekProduk($key)
    {
        $this->db->where('id',$key);
        $query = $this->db->get('produk');
        if ($query->num_rows() > 0){
            return true;
        }
        else{
            return false;
        }
    }
    
    function _cekQty($key)
    {
        $this->db->where('produk_id', $key);
        $query = $this->db->get('detail_produk');
        if( $query->num_rows() > 0 ){
            if( $key !== "" ){
                return $query->row_array();
            }else{
                return $query->result_array();  
            }
            
        }else return null;
    }
}
