<?php

class JSONDB implements DB
{
  public function __construct()
  {
  }

  public function select($args)
  {
    /**
     * Explodes the selected columns into array
     *
     * @param type $args Optional. Default *
     * @return type object
     */


    return $this;
  }

  public function from($file, $load)
  {
    /**
     * Loads the JSON file
     *
     * @param type $file. Accepts file name
     * @return type object
     */

    return $this;
  }

  public function where($columns)
  {
    return $this;
  }

  public function delete()
  {
    return $this;
  }

  public function update($columns)
  {
    return $this;
  }

  /**
   * Inserts data into json file
   *
   * @param string $file json filename without extension
   * @param array $values Array of columns as keys and values
   */
  public function insert($file, $values)
  {
  }

  public function commit()
  {
  }


  /**
   * Prepares data and written to file
   *
   * @return object $this
   */
  public function flush()
  {
    return $this;
  }


  public function order_by($column, $order)
  {
    return $this;
  }


  public function get()
  {
  }
}
