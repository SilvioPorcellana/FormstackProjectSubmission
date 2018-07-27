<?php

namespace Models;

/**
 * This is the main class for the Document model.
 *
 * @property \PDO $pdo;
 * @property string $db_table
 *
 * @property string $key
 * @property string $value
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $exported_at
 */


class Document
{
    private $key;
    private $value;
    private $created_at;
    private $updated_at;
    private $exported_at;

    /**
     * Document constructor.
     *
     * @param $key
     * @param $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;

        return $this;
    }


    /**
     * These are two static function used in this class to read the PDO and table details from the config files
     *
     * @return \PDO
     */
    private static function _pdo()
    {
        $_config_file_path = $_SERVER['DOCUMENT_ROOT'] . '/../src/_config.ini';
        $config = parse_ini_file($_config_file_path);
        $_pdo = new \PDO($config['db_dsn'], $config['db_user'], $config['db_password']);
        return $_pdo;
    }

    /**
     * @return mixed
     */
    private static function _tablename()
    {
        $_config_file_path = $_SERVER['DOCUMENT_ROOT'] . '/../src/_config.ini';
        $config = parse_ini_file($_config_file_path);
        $_tablename = $config['db_table'];

        return $_tablename;
    }


    /**
     * @param $key
     */
    public static function find($key)
    {
        $_pdo = self::_pdo();
        $_tablename = self::_tablename();
        $query = 'SELECT * FROM ' . $_tablename . ' WHERE `key` LIKE :key';
        $statement = $_pdo->prepare($query);
        $statement->execute(['key' => $key]);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $row = array_shift($result);
        if ($row['key'])
        {
            return self::_convertRowToModel($row);
        }

        return false;
    }



    /**
     * @param string $keyLike
     */
    public static function findAll($keyLike = '')
    {
        $_pdo = self::_pdo();
        $_tablename = self::_tablename();
        $searchArray = [];

        $query = 'SELECT * FROM ' . $_tablename . ' WHERE 1';
        if ($keyLike)
        {
            $query .= ' AND `key` LIKE :key ';
            $searchArray = ['key' => ('%' . $keyLike . '%')];
        }
        $statement = $_pdo->prepare($query);
        $statement->execute($searchArray);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $return = [];
        foreach ($result as $row)
        {
            $return[] = self::_createTaskFromArray($row);
        }

        return $return;
    }



    /**
     * @param $row
     * @return Document
     */
    private static function _convertRowToModel($row)
    {
        $document = new Document();
        foreach ($row as $k => $v)
        {
            if (property_exists($document, $k))
            {
                $document->$k = $v;
            }
        }

        return $document;
    }



    /**
     * Saves this model to the database
     */
    public function save()
    {
        $this->created_at = time();

        $_pdo = self::_pdo();
        $_tablename = self::_tablename();

        $query = 'INSERT INTO `' . $_tablename . '` ( `key`, `value`, `created_at` ) VALUES ( :key, :value, :created_at )';
        $statement = $_pdo->prepare($query);
        if (! $statement->execute(['key' => $this->key, 'value' => $this->value, 'created_at' => $this->created_at]))
        {
            /*
             * TODO - debugging
             */
            print_r($statement->errorInfo());
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            return $_pdo->lastInsertId();
        }
    }


    /**
     * @param $key
     * @param $value
     */
    public function update($value)
    {
        $this->value = $value;
        $this->updated_at = time();

        $_pdo = self::_pdo();
        $_tablename = self::_tablename();

        $query = 'UPDATE `' . $_tablename . '` SET value = :value, updated_at = :updated_at WHERE key = "' . $this->key . '"';
        $statement = $_pdo->prepare($query);
        if (! $statement->execute(['value' => $value, 'updated_at' => $this->updated_at]))
        {
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            return $this->key;
        }
    }


    /**
     * @param array $params
     * @param string $keyLike
     */
    public static function updateAll(array $params, $keyLike = '')
    {
        $_pdo = self::_pdo();
        $_tablename = self::_tablename();

        foreach ($params as $k => $v)
        {
            $params_query[] = $k . ' = :' . $k;
            $values_query[$k] = $v;
        }

        $query = 'UPDATE `' . $_tablename . '` SET ' . implode(', ', $params_query) . ' WHERE key LIKE "%' . $keyLike . '%"';
        $statement = $_pdo->prepare($query);
        if (! $statement->execute($values_query))
        {
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            return 1;
        }
    }


    public function delete()
    {
        $_pdo = self::_pdo();
        $_tablename = self::_tablename();

        $query = 'DELETE FROM `' . $_tablename . '` WHERE key = "' . $this->key . '"';
        $count = $_pdo->exec($query);
        return $count;
    }
}