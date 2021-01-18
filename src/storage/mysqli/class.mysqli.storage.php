<?php
namespace storage\MySQLi;

//use \framework\storage\GenericStorage;

class MysqliStorage extends \storage\GenericStorage
{
  private $mysqli = NULL;
  private $host = NULL;
  private $username = NULL;
  private $password = NULL;
  private $db = NULL;
  private $prefix = false;
  private $port = 3306;

  protected function connect()
  {
    // TODO check arguments //host, username, password, db
    $host = $this->config['arguments']['host'];
    $username = $this->config['arguments']['username'];
    $password = $this->config['arguments']['password'];
    $this->db = $this->config['arguments']['db'];
    $this->port = isset($this->config['arguments']['port']) ? $this->config['arguments']['port'] : 3306;
    $this->prefix = isset($this->config['prefix']) ? $this->config['prefix'] : false;

    $this->mysqli = mysqli_init();
    $isConnected = $this->mysqli->real_connect($host, $username, $password, $this->db, $this->port);
    if (!$isConnected)
    {
      throw new \Exception('Error establishing storage connection: '.$this->mysqli->error . "\n");
      return false;
    }

    return true;
  }

  public function getConnection()
  {
    return $this->mysqli;
  }

  public function __destruct()
  {
    // TODO sicherstellen, dass sichdie DB als letztes beendet!
    // if ($this->mysqli)
    // $this->mysqli->close();
  }

  private function checkConnection()
  {
    if ($this->mysqli->ping())
      return true;

    $this->mysqli->close();
    $this->mysqli = mysqli_init();

    $this->mysqli->real_connect($this->host, $this->username, $this->password, $this->db) or die('Error reconnecting DB' . "\n");

    return true;
  }

  public function getPrefix()
  {
    return $this->prefix;
  }

  public function query($stmt)
  {
    $this->checkConnection();

    $result = $this->mysqli->query($stmt);

    /*
     * TODO LOG Errors in DEVMODE
    //var_dump($result);
    var_dump(DEVMODE);
    //defined('DEVMODE') && DEVMODE &&
    if ($result === false)
    {
      echo $this->mysqli->error."\n";
    }
     */

    return $result;
  }

  public function affected_rows()
  {
    return $this->mysqli->affected_rows;
  }

  public function insert_id()
  {
    return $this->mysqli->insert_id;
  }

  private function getFieldTypes($result)
  {
    $fields = mysqli_fetch_fields($result);
    //print_r($fields);
    $types = array();
    foreach($fields as $field)
    {
      switch($field->type)
      {
        case 8: //bigint
        case 3:
          $types[$field->name] = 'int';
          break;

        case 4:
          $types[$field->name] = 'float';
          break;

        case 253:
          $types[$field->name] = 'string';
          break;

        //TODO log unhandled
        default:
          $types[$field->name] = 'string';
          break;
       }
    }

    return $types;
  }

  /**
   * Returns database query result as associative array limitied to $buffer rows.
   *
   * @param $result
   * @param int $buffer
   * @return array|bool
   */
  public function fetchRows(&$result, $buffer = -1)
  {
    if (!isset($result) || is_bool($result) || !get_class($result) == 'mysqli_result')
      return false;

    $fieldTypes = $this->getFieldTypes($result);

    $ret = array();

    $cnt = 0;
    while ( $row = $result->fetch_assoc() )
    {
      foreach ($row as $entityName => &$value)
      {
        if (isset($fieldTypes[$entityName])) //TODO handle else
        {
          settype($value, $fieldTypes[$entityName]);
        }
      }

      $cnt++;
      $ret[] = $row;

      if ($buffer > -1 && $cnt == $buffer)
        return $ret;
    }

    $result->close();
    unset($result);

    return $ret;
  }

  /**
   * Returns array with table columns description or false if table doesn't exist
   *
   * @param string $tableName
   * @return array|bool
   */
  public function getTableColumns($tableName)
  {
    // Search for table
    $query = $this->query(sprintf('SHOW TABLES FROM %s LIKE \'%s\'', $this->db, $tableName));
    if (!$this->fetchRows($query)) {
      return false;
    }

    // Get columns
    $query = $this->query('SHOW COLUMNS FROM ' .  $tableName);
    $result = $this->fetchRows($query);

    //Prepare response
    $table = array();
    if (is_array($result)) {
      foreach ($result as $item) {
        $table[$item['Field']] = $item;
      }
    }

    return $table;
  }

  /**
   * Returns array with table keys description or false if table doesn't exist
   *
   * @param string $tableName
   * @return array|bool
   */
  public function getTableKeys($tableName)
  {
    // Search for table
    $query = $this->query(sprintf('SHOW TABLES FROM %s LIKE \'%s\'', $this->db, $tableName));
    if (!$this->fetchRows($query)) {
      return false;
    }

    // Get keys
    $query = $this->query('SHOW INDEX FROM ' .  $tableName);
    $result = $this->fetchRows($query);

    //Prepare response
    $table = array();
    if (is_array($result)) {
      foreach ($result as $item) {
        $table[$item['Key_name']] = $item;
      }
    }

    return $table;
  }

  /**
   * Returns array with table foreign keys description or false if table doesn't exist
   *
   * @param string $tableName
   * @return array|bool
   */
  public function getTableForeignKeys($tableName)
  {
    // Get foreign keys
    $query = $this->query(
      sprintf(
        'SELECT *  FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = \'%s\' AND TABLE_NAME = \'%s\'',
        $this->db,
        $tableName
      )
    );
    $result = $this->fetchRows($query);

    //Prepare response
    $table = array();
    if (is_array($result)) {
      foreach ($result as $item) {
        $table[$item['CONSTRAINT_NAME']] = $item;
      }
    }

    return $table;
  }

}
