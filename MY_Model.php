<?php
class MY_Model extends CI_Model {

  /**
   * @var String the table name
   */
  protected $_table_name   = '';
  /**
   * @var String the fields of table
   */
  protected $_table_fields = '';
  /**
   * @var Array join other tables table
   */
  protected $_join_tables  = [];
  /**
   * @var Array join conditions
   */
  protected $_join_fields  = [];
  /**
   * @var Array join type
   */
  protected $_join_types   = [];
  /**
   * @var String sorting by this field
   */
  public $_order_by        = '';
  /**
   * @var Array validator rules
   */
  public $rules            = array();

  /**
  * Constructor.
  */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * SetFieldValue() This is can help to update any records/fields.
   *
   * @param Int   $id    id of table
   * @param Array $data  the fields and values pairs what we want to update
   */
  public function setFieldValue($id, $data = array()){
    $this->db->where('id', $id)
      ->set($data)
      ->update($this->_table_name);
    return $this->db->affected_rows();
  }

  /**
  * Get records form database.
  *
  * @param Array $filter
  * @return Array
  */
  public function get($filter = array())
  {
    if(!empty($this->_table_fields)){
      $this->db->select($this->_table_fields);
    }

    if(count($this->_join_tables) > 0){
      foreach($this->_join_tables as $k => $table){
        $this->db->join($table, $this->_join_fields[$k], $this->_join_types[$k]);
      }
    }
    if(!empty($this->_order_by)){
      $this->db->order_by($this->_order_by);
    }

    if(count($filter) > 0){
      foreach($filter as $k => $v){
        $this->db->where($k, $v);
      }
    }
    $q = $this->db->get($this->_table_name);

    return $q->result_array();
  }

/**
 * Save/update data to database.
 *
 * @param Array  $data
 * @param Int $id
 * @return Int $id
 */
  public function save($data = array(), $id = NULL)
  {
    if($id === NULL){
      $this->db->insert($this->_table_name, $data);
      $id = $this->db->insert_id();
    }else{
      $this->db->set($data);
      $this->db->where('id', $id);
      $this->db->update($this->_table_name);
    }
    return $id;
  }

  /**
   * Delete record by id.
   * @param  Int $id
   * @return Int The number of affected rows
   */
  public function delete($id)
  {
    $this->db->where('id', $id);
    $this->db->delete($this->_table_name);
    return $this->db->affected_rows();
  }

  /**
   * Get records in JSON format.
   * Used in datatables.
   * https://datatables.net
   *
   * @param  Array  $fields The fields and values pairs what we want to get in JSON.
   * @return JSON
   */
  public function getJSON($fields = array())
  {
    //Only AJAX request allowed
    if (!IS_AJAX){
      return json_encode(array('error' => 'noway'));
    }

    $total = $this->db->count_all_results($this->_table_name);

    $search  = $this->input->get('search', TRUE);
    if(is_array($search) && count($search) > 0 ){
      //$fields = explode(",", $this->_table_fields);
      foreach($fields as $f){
        if(!empty($search['value'])){
          $this->db->or_like($f, $search['value']);
        }
      }
    }

    $this->db->limit($this->input->get('length', TRUE), $this->input->get('start'));
    $order = $this->input->get('order', TRUE);
    if(is_array($order)){
      foreach($order as $o){
        $order_field 	= $o['column'];
        $order_field 	= $fields[$order_field];
        $order_dir 		= $o['dir'];
        $this->db->order_by($order_field, $order_dir);
      }
    }

    $this->db->select($fields);
    if(count($this->_join_tables) > 0){
      foreach($this->_join_tables as $k => $table){
        $this->db->join($table, $this->_join_fields[$k], $this->_join_types[$k]);
      }
    }

    $query = $this->db->get($this->_table_name);
    $data = $query->result_array();

    $us = array();
    foreach ($data as $u) {
        $us[] = array_values($u);
    }
    $obj = array(
        "draw" => $this->input->get('draw'),
        "recordsTotal" => $total,
        "recordsFiltered" => count($data),
        "data" => $us
    );
    return json_encode($obj);
  }
}
