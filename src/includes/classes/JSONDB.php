<?php

class JSONDB
{
  public $file;
  public $content = [];
  public const ASC = 1;
  public const DESC = 0;
  public const AND = 'AND';
  public const OR = 'OR';

  private $fp;
  private $load;
  private $where;
  private $select;
  private $merge;
  private $update;
  private $delete = false;
  private $lastIndexes = [];
  private $orderBy = [];
  private $jsonEncodeOpt = [];
  private const LOAD_FULL = 'full';
  private const LOAD_PARTIAL = 'partial';

  protected $dir;

  public function __construct(
    $dir,
    $json_encode_opt = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
  {
    $this->dir = $dir;
    $this->jsonEncodeOpt['encode'] = $json_encode_opt;
  }


  public function select($args = '*')
  {
    /**
     * Explodes the selected columns into array
     *
     * @param type $args Optional. Default *
     * @return type object
     */

    // Explode to array
    $this->select = explode(',', $args);

    // Remove whitespaces
    $this->select = array_map('trim', $this->select);

    // Remove empty values
    $this->select = array_filter($this->select);

    return $this;
  }


  public function from($file, $load = self::LOAD_FULL)
  {
    /**
     * Loads the JSON file
     *
     * @param type $file. Accepts file path to JSON file
     * @return type object
     */

    // Adding .json extension no necessary
    $this->file = sprintf(
      '%s/%s.json',
      $this->dir,
      str_replace('.json', '', $file)
    );


    // Reset where
    $this->where([]);
    $this->content = '';
    $this->load = $load;

    // Reset order by
    $this->orderBy = [];

    $this->checkFile();
    return $this;
  }

  public function where($columns, $merge = self::OR)
  {
    $this->where = $columns;
    $this->merge = $merge;
    return $this;
  }

  public function delete()
  {
    $this->delete = true;
    return $this;
  }

  public function update($columns)
  {
    $this->update = $columns;
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
    $values['_id'] = hash('md5', microtime(true));
    $values['createdAt'] = date("Y-m-d H:i:s");

    $this->from($file, self::LOAD_PARTIAL);

    $first_row = current($this->content);
    $this->content = [];

    if (!empty($first_row)) {
      $unmatched_columns = 0;

      foreach ($first_row as $column => $value) {
        if (!isset($values[$column])) {
          $values[$column] = null;
        }
      }

      foreach ($values as $col => $val) {
        if (!array_key_exists($col, $first_row)) {
          $unmatched_columns = 1;
          break;
        }
      }

      if ($unmatched_columns) {
        throw new \Exception('Columns must match as of the first row');
      }
    }

    $this->content[] = $values;
    $this->commit();
  }

  public function commit()
  {
    if ($this->fp && is_resource($this->fp)) {
      $f = $this->fp;
    } else {
      $f = fopen($this->file, 'w+');
    }

    if ($this->load === self::LOAD_FULL) {
      // Write everything back into the file
      fwrite($f, (!$this->content ? '[]' : json_encode($this->content, $this->jsonEncodeOpt['encode'])));
    } elseif ($this->load === self::LOAD_PARTIAL) {
      // Append it
      $this->append();
    } else {
      // Unknown load type
      fclose($f);
      throw new \Exception('Write fail: Unknown load type provided', 'write_error');
    }

    fclose($f);
  }

  /**
   * Prepares data and written to file
   *
   * @return object $this
   */
  public function flush()
  {
    // Validates and fetch out the data
    // actually initialize $this->lastIndexes
    !empty($this->where) && $this->whereResult();

    if ($this->delete) {
      if (!empty($this->lastIndexes) && !empty($this->where)) {
        $this->content = array_filter($this->content, function ($index) {
          return !in_array($index, $this->lastIndexes);
        }, ARRAY_FILTER_USE_KEY);

        $this->content = array_values($this->content);
      } elseif (empty($this->where) && empty($this->lastIndexes)) {
        $this->content = [];
      }

      $this->delete = false;
    } elseif (!empty($this->update)) {
      $this->_update();
      $this->update = [];
    }

    $this->commit();
    return $this;
  }


  public function orderBy($column, $order = self::ASC)
  {
    $this->orderBy = [$column, $order];
    return $this;
  }


  public function get()
  {
    if ($this->where != null) {
      $content = $this->whereResult();
    } else {
      $content = $this->content;
    }

    if ($this->select && !in_array('*', $this->select)) {
      $r = [];
      foreach ($content as $id => $row) {
        $row = (array) $row;
        foreach ($row as $key => $val) {
          if (in_array($key, $this->select)) {
            $r[$id][$key] = $val;
          } else {
            continue;
          }
        }
      }
      $content = $r;
    }

    // Finally, lets do sorting :)
    $content = $this->_proceedOrderBy($content);

    $this->flush_indexes(true);
    return $content;
  }


  private function getCurrentPositionOfPointer()
  {
    $size = 0;
    $cur_size = 0;

    if ($this->fp) {
      $cur_size = ftell($this->fp);
      fseek($this->fp, 0, SEEK_END);
      $size = ftell($this->fp);
      fseek($this->fp, $cur_size, SEEK_SET);
    }

    return $size;
  }

  private function validateContent($content)
  {
    // Check if its arrays of JSON
    if (!is_array($content) && is_object($content)) {
      throw new \Exception('An array of JSON is required: JSON data enclosed with []');
    }
    // An invalid jSON file
    if (!is_array($content) && !is_object($content)) {
      throw new \Exception('JSON is invalid');
    }
  }

  private function checkFile()
  {
    /**
     * Checks and validates if JSON file exists
     *
     * @return bool
     */

    // Checks if DIR exists, if not create
    if (!is_dir($this->dir)) {
      mkdir($this->dir, 0700);
    }

    // Checks if JSON file exists, if not create
    if (!file_exists($this->file)) {
      touch($this->file);
    }

    $this->content = $this->loadContent();

    return true;
  }

  private function loadContent()
  {
    if ($this->load == self::LOAD_PARTIAL) {

      $this->fp = fopen($this->file, 'r+');

      if (!$this->fp) {
        throw new \Exception('Unable to open JSON file');
      }

      $size = $this->getCurrentPositionOfPointer();

      if ($size) {
        $content = getJSONChunk($this->fp);

        // We could not get the first chunk of JSON. Lets try to load everything then
        if (!$content) {
          $content = fread($this->fp, $size);
        } else {
          // We got the first chunk, we still need to put it into an array
          $content = sprintf('[%s]', $content);
        }

        $content = json_decode($content, true);
      } else {
        // Empty file. File was just created
        $content = [];
      }
    } else {
      // Read content of JSON file
      $content = file_get_contents($this->file);
      $content = json_decode($content, true);
    }

    $this->validateContent($content);

    return $content;
  }



  private function append()
  {
    $size = $this->getCurrentPositionOfPointer();
    $per_read = $size > 64 ? 64 : $size;
    $read_size = -$per_read;
    $lstblkbrkt = false;
    $lastinput = false;
    $i = $size;
    $data = json_encode($this->content, $this->jsonEncodeOpt['encode']);

    if ($size) {
      fseek($this->fp, $read_size, SEEK_END);

      while (($read = fread($this->fp, $per_read))) {
        $per_read = $i - $per_read < 0 ? $i : $per_read;
        if ($lstblkbrkt === false) {
          $lstblkbrkt = strrpos($read, ']', 0);
          if ($lstblkbrkt !== false) {
            $lstblkbrkt = ($i - $per_read) + $lstblkbrkt;
          }
        }

        if ($lstblkbrkt !== false) {
          $lastinput = strrpos($read, '}');
          if ($lastinput !== false) {
            $lastinput = ($i - $per_read) + $lastinput;
            break;
          }
        }

        $i -= $per_read;
        $read_size += -$per_read;
        if (abs($read_size) >= $size) {
          break;
        }
        fseek($this->fp, $read_size, SEEK_END);
      }
    }

    if ($lstblkbrkt !== false) {
      // We found existing json data, don't write extra [
      $data = substr($data, 1);
      if ($lastinput !== false) {
        $data = sprintf(',%s', $data);
      }
    } else {
      if ($size > 0) {
        throw new \Exception('Append error: JSON file looks malformed');
      }

      $lstblkbrkt = 0;
    }

    fseek($this->fp, $lstblkbrkt, SEEK_SET);
    fwrite($this->fp, $data);
  }

  private function _update()
  {
    if (!empty($this->lastIndexes) && !empty($this->where)) {
      foreach ($this->content as $i => $v) {
        if (in_array($i, $this->lastIndexes)) {
          $content = (array) $this->content[$i];
          if (!array_diff_key($this->update, $content)) {
            $this->content[$i] = (object) array_merge($content, $this->update);
          } else {
            throw new \Exception('Update method has an off key');
          }
        } else {
          continue;
        }
      }
    } elseif (!empty($this->where) && empty($this->lastIndexes)) {
      echo "exit here\n";
      return;
    } else {
      foreach ($this->content as $i => $v) {
        $content = (array) $this->content[$i];
        if (!array_diff_key($this->update, $content)) {
          $this->content[$i] = (object) array_merge($content, $this->update);
        } else {
          throw new \Exception('Update method has an off key ');
        }
      }
    }
  }


  /**
   * Flushes indexes they won't be reused on next action
   */
  private function flush_indexes($flush_where = false)
  {
    $this->lastIndexes = [];
    if ($flush_where) {
      $this->where = [];
    }

    if ($this->fp && is_resource($this->fp)) {
      fclose($this->fp);
    }
  }

  private function intersect_value_check($a, $b)
  {
    if ($b instanceof \stdClass) {
      if ($b->is_regex) {
        return !preg_match($b->value, (string) $a, $_, $b->options);
      }

      return -1;
    }

    if ($a instanceof \stdClass) {
      if ($a->is_regex) {
        return !preg_match($a->value, (string) $b, $_, $a->options);
      }

      return -1;
    }

    return strcasecmp((string) $a, (string) $b);
  }

  /**
   * Validates and fetch out the data for manipulation
   *
   * @return array $r Array of rows matching WHERE
   */
  private function whereResult()
  {
    $this->flush_indexes();

    if ($this->merge == 'AND') {
      return $this->whereAndResult();
    }
    // Filter array
    $r = array_filter($this->content, function ($row, $index) {
      $row = (array) $row; // Convert first stage to array if object

      // Check for rows intersecting with the where values.
      if (array_uintersect_uassoc($row, $this->where, [$this, 'intersect_value_check'], 'strcasecmp') /*array_intersect_assoc( $row, $this->where )*/) {
        $this->lastIndexes[] = $index;
        return true;
      }

      return false;
    }, ARRAY_FILTER_USE_BOTH);

    // Use helper function
    // Make sure every  object is turned to array here.
    return array_values(objectToArray($r));
  }

  /**
   * Validates and fetch out the data for manipulation for AND
   *
   * @return array $r Array of fetched WHERE statement
   */
  private function whereAndResult()
  {
    /*
            Validates the where statement values
        */
    $r = [];

    // Loop through the db rows. Ge the index and row
    foreach ($this->content as $index => $row) {

      // Make sure its array data type
      $row = (array) $row;

      //check if the row = where['col'=>'val', 'col2'=>'val2']
      if (!array_udiff_uassoc($this->where, $row, [$this, 'intersect_value_check'], 'strcasecmp')) {
        $r[] = $row;
        // Append also each row array key
        $this->lastIndexes[] = $index;
      } else {
        continue;
      }
    }
    return $r;
  }

  private function _proceedOrderBy($content)
  {
    if ($this->orderBy && $content && in_array($this->orderBy[0], array_keys((array) $content[0]))) {
      /*
        * Check if order by was specified
        * Check if there's actually a result of the query
        * Makes sure the column  actually exists in the list of columns
      */

      list($sort_column, $orderBy) = $this->orderBy;
      $sort_keys = [];
      $sorted = [];

      foreach ($content as $index => $value) {
        $value = (array) $value;
        // Save the index and value so we can use them to sort
        $sort_keys[$index] = $value[$sort_column];
      }

      // Let's sort!
      if ($orderBy == self::ASC) {
        asort($sort_keys);
      } elseif ($orderBy == self::DESC) {
        arsort($sort_keys);
      }

      // We are done with sorting, lets use the sorted array indexes to pull back the original content and return new content
      foreach ($sort_keys as $index => $value) {
        $sorted[$index] = (array) $content[$index];
      }

      $content = $sorted;
    }

    return $content;
  }


}
