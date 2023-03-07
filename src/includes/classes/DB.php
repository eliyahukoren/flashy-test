<?php

interface DB {
  public function get();
  public function flush();
  public function commit();
  public function insert($entity, $values);
  public function update($columns);
  public function delete();
  public function from($entity, $load);
  public function where($columns);
  public function order_by($column, $order);
  public function select($args);
}
