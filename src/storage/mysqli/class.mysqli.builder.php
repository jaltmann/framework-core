<?php
namespace storage\MySQLi;

//TODO MySQLiObjectBuilder
class MysqliBuilder extends \storage\GenericObject
{
  private $debug = false;

  public function __construct($relationName, $storage_def)
  {
    parent::__construct($relationName, $storage_def);
  }

  private function translateEntityType(&$entity)
  {
    if (isset($entity['type']))
    {
      switch($entity['type'])
      {
      	case 'flag':
      	  $entity['type'] = 'bigint';
          $entity['unsigned'] = true;
          $entity['nullable'] = false;
          $entity['default'] = 0;
          break;

        case 'json':
          $entity['type'] = 'text';
          $entity['nullable'] = true;
          break;

        case 'mediumjson':
          $entity['type'] = 'mediumtext';
          $entity['nullable'] = true;
          break;

      	default:
      	  break;
      }
    }


  }

  public function setDebug($debug)
  {
    $this->debug = $debug;
    return true;
  }

  protected function buildCreate()
  {
    //TODO create storage independent
    $query = array();
    $query[] = 'CREATE TABLE `' . $this->getRelationName() . '` (';

    // Generate columns
    foreach ($this->relation['entities'] as $name => $entity) {
      $query[] = '`' . $name . '`';

      //TODO more flexible
      $this->translateEntityType($entity);

      if (isset($entity['length'])) {
        $query[] = $entity['type'] . '(' . $entity['length'] . ')';
      } else {
        $query[] = $entity['type'];
      }

      if (isset($entity['unsigned']) && $entity['unsigned'] === true) {
        $query[] = 'UNSIGNED';
      }

      if (isset($entity['default'])) {
        $type = gettype($entity['default']);
        switch ($type) {
          case 'array':
            if (isset($entity['default']['value']))
              $query[] = 'DEFAULT ' . $entity['default']['value'];
            break;
          case 'string':
          default:
            $query[] = 'DEFAULT ' . $entity['default'];
            break;
        }
      }

      if (isset($entity['nullable'])) {
        if ($entity['nullable'] === false) {
          $query[] = 'NOT';
        }
        $query[] = 'NULL';
      }

      if (isset($entity['options'])) {
        foreach ($entity['options'] as $option) {
          $query[] = strtoupper($option);
        }
      }

      $query[] = ",\n";
    }

    // Generate keys
        if (!empty($this->relation['keys'])) {
      foreach ($this->relation['keys'] as $type => $keys) {
        foreach ($keys as $keyName => $keyDetails) {
          $keyFullName = strtoupper(sprintf('%s_%s', $this->name, $keyName));
          switch ($type) {
            case 'primary':
              $query[] = sprintf('PRIMARY KEY (%s)', implode(',', $keys));
              $query[] = ",\n";
              break;
            case 'foreign':
              // foreign keys have to be created after all referenced tables with unique keys are ready
              break;
            case 'index':
              $query[] = sprintf('INDEX IX_%s (%s)', $keyFullName, implode(',', $keyDetails));
              $query[] = ",\n";
              break;
            case 'unique':
              $query[] = sprintf('UNIQUE UX_%s (%s)', $keyFullName, implode(',', $keyDetails));
              $query[] = ",\n";
              break;
          }
        }
      }
    }

    // Remove last coma
    array_pop($query);

    return array(implode(' ', $query) . ');');
  }

  /**
   * Prepare alter table query based on relation definition and current columns
   *
   * @param array $tableColumns
   * @param bool $deleteOldColumns whether or not delete old columns
   * @param bool $recreateKeys whether or not recreate existing keys
   * @return string
   */
  protected function buildUpdate(array $tableColumns, $deleteOldColumns, $recreateKeys)
  {
    $query = array('ALTER TABLE `' . $this->getRelationName() . '` ');

    // Generate columns
    foreach ($this->relation['entities'] as $name => $entity) {
      $query[] = isset($tableColumns[$name]) ? 'CHANGE COLUMN `' . $name . '`' : 'ADD COLUMN ';
      $query[] = '`' . $name . '`';

      $this->translateEntityType($entity);

      if (isset($entity['length'])) {
        $query[] = $entity['type'] . '(' . $entity['length'] . ')';
      } else {
        $query[] = $entity['type'];
      }

      if (isset($entity['unsigned']) && $entity['unsigned'] === true) {
        $query[] = 'UNSIGNED';
      }

      if (isset($entity['default'])) {
        $type = gettype($entity['default']);
        switch ($type) {
          case 'array':
            if (isset($entity['default']['value']))
              $query[] = 'DEFAULT ' . $entity['default']['value'];
            break;
          case 'string':
          default:
            $query[] = 'DEFAULT ' . $entity['default'];
            break;
        }
      }

      if (isset($entity['nullable'])) {
        if ($entity['nullable'] === false) {
          $query[] = 'NOT';
        }
        $query[] = 'NULL';
      }

      if (isset($entity['options'])) {
        foreach ($entity['options'] as $option) {
          $query[] = strtoupper($option);
        }
      }

      $query[] = ",\n";
    }

    // Delete old columns
    if ($deleteOldColumns) {
      $entities = array_keys($this->relation['entities']);
      foreach (array_keys($tableColumns) as $tableColumn) {
        if (!in_array($tableColumn, $entities)) {
          $query[] = 'DROP COLUMN `' . $tableColumn . '`';
          $query[] = ",\n";
        }
      }
    }

    // Generate missing keys or keys to recreate
    $relationKeys = array();
    $tableKeys = $this->storage->getTableKeys($this->getRelationName());
    if (!empty($this->relation['keys'])) {
      foreach ($this->relation['keys'] as $type => $keys) {
        foreach ($keys as $keyName => $keyDetails) {
          $relationKeys[] = $keyName;
          $keyFullName = sprintf('%s_%s', $this->name, $keyName);
          if (($type == 'primary' && empty($tableKeys['PRIMARY'])) || ($type != 'primary' && (empty($tableKeys[$keyFullName]) ||
            $recreateKeys))) {
            switch ($type) {
              case 'primary':
                $query[] = sprintf('ADD PRIMARY KEY (%s)', implode(',', $keyDetails));
                $query[] = ",\n";
                break;
              case 'foreign':
                // foreign keys have to be created after all referenced tables with unique keys are ready
                break;
              case 'index':
                $query[] = sprintf('ADD INDEX %s (%s)', $keyFullName, implode(',', $keyDetails));
                $query[] = ",\n";
                break;
              case 'unique':
                $query[] = sprintf('ADD UNIQUE %s (%s)', $keyFullName, implode(',', $keyDetails));
                $query[] = ",\n";
                break;
            }
          }
        }
      }
    }

    // Keys to drop
    $dropKeys = array();
    if ($recreateKeys) {
      // Delete all existing keys on recreate
      foreach ($tableKeys as $keyFullName => $key) {
        if ($keyFullName == 'PRIMARY') {
          continue;
        }
        $dropKeys[$keyFullName] = sprintf("ALTER TABLE `%s` DROP INDEX `%s`;\n", $this->getRelationName(), $keyFullName);
      }
    } else {
      // Delete old keys
      foreach (array_keys($tableKeys) as $tableKey) {
        $key = preg_replace('/^' . $this->name . '\_/', '', $tableKey);
        if ($tableKey != 'PRIMARY' && !in_array($key, $relationKeys)) {
          $dropKeys[$tableKey] = sprintf("ALTER TABLE `%s` DROP INDEX `%s`;\n", $this->getRelationName(), $tableKey);
        }
      }
    }

    // Remove last coma
    array_pop($query);

    return array_merge($dropKeys, array(implode(' ', $query) . ';'));
  }

  public function deleteTable()
  {
    $query = sprintf('DROP TABLE IF EXISTS `%s`', $this->getRelationName());

    // Execute query or just display query for debug purposes
    if ($this->debug) {
      printf("%s\n", $query);
    } else {
      $this->storage->query($query);
      printf("Deleted table %s\n", $this->getRelationName());
    }
  }

  public function prepareTable($deleteOldColumns, $recreateKeys)
  {
    // Search for table columns
    $tableColumns = $this->storage->getTableColumns($this->getRelationName());

    // Build update or create table query
    $query = '';
    if ($tableColumns) {
      $query = $this->buildUpdate($tableColumns, $deleteOldColumns, $recreateKeys);
    } else {
      $query = $this->buildCreate();
    }

    // Execute query or just display query for debug purposes
    if ($this->debug) {
      printf("%s\n", implode('', $query));
    } else {
      foreach ($query as $q) {
        $this->storage->query($q);
      }
      printf("%s table %s\n", $tableColumns ? 'Updated' : 'Created', $this->getRelationName());
    }
  }

  public function deleteTableForeignKeys($recreateKeys)
  {
    // Get table foreign keys
    $tableForeignKeys = array_keys($this->storage->getTableForeignKeys($this->getRelationName()));
    $query = array();

    if ($recreateKeys) {
      // Delete all if recreate
      foreach ($tableForeignKeys as $tableForeignKey) {
        $query[] = sprintf("ALTER TABLE `%s` DROP FOREIGN KEY `%s`;\n", $this->getRelationName(), $tableForeignKey);
      }
    } elseif (!empty($this->relation['keys']['foreign'])) {
      // Delete old foreign keys
      $relationKeys = !empty($this->relation['keys']['foreign']) ? array_keys($this->relation['keys']['foreign']) : array();
      foreach ($tableForeignKeys as $tableForeignKey) {
        $key = preg_replace('/^' . $this->name . '\_/', '', $tableForeignKey);
        if ($tableForeignKey != 'PRIMARY' && !in_array($key, $relationKeys)) {
          $query[] = sprintf("ALTER TABLE `%s` DROP FOREIGN KEY `%s`;\n", $this->getRelationName(), $tableForeignKey);
        }
      }
    }
    if (!$query) {
      return;
    }

    // Execute query or just display query for debug purposes
    if ($this->debug) {
      printf("%s\n", implode('', $query));
    } else {
      foreach ($query as $q) {
        $this->storage->query($q);
      }
      printf("Deleted foreign keys for table %s\n", $this->getRelationName());
    }
  }

  public function prepareTableForeignKeys($recreateKeys)
  {
    // Get table foreign keys
    $tableForeignKeys = $this->storage->getTableForeignKeys($this->getRelationName());

    // Build query
    $query = array();
    if (!empty($this->relation['keys']['foreign'])) {
      foreach ($this->relation['keys']['foreign'] as $keyName => $keyDetails) {
        $keyFullName = strtoupper(sprintf('%s_%s', $this->name, $keyName));
        if (empty($tableForeignKeys[$keyFullName]) || $recreateKeys) {
          $query[] = sprintf(
            "ALTER TABLE `%s` ADD CONSTRAINT FK_%s FOREIGN KEY (%s) REFERENCES %s_%s (%s);\n",
            $this->getRelationName(),
            $keyFullName,
            $keyDetails['column'],
            $this->storage->getPrefix(),
            $keyDetails['reference_table'],
            $keyDetails['reference_column']
          );
        }
      }
    }
    if (!$query) {
      return;
    }

    // Execute query or just display query for debug purposes
    if ($this->debug) {
      printf("%s\n", implode('', $query));
    } else {
      foreach ($query as $q) {
        $this->storage->query($q);
      }
      printf("Created foreign keys for table %s\n", $this->getRelationName());
    }
  }
}
