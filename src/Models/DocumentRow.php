<?php

namespace Models;
use libs\DocumentPDO;

/**
 * This is the main class for the Document model.
 *
 * @property integer $document_id
 * @property string $key
 * @property string $value
 * @property integer $created_at
 * @property integer $updated_at
 */


class DocumentRow
{
    private $sql_fields = ['document_id', 'key', 'value'];

    public $document_id;
    public $key;
    public $value;
    public $created_at;
    public $updated_at;

    /**
     * Document constructor.
     *
     * @param $key
     * @param $value
     */
    public function __construct($document_id, array $params = [])
    {
        $this->document_id = $document_id;

        if (isset($params['key']) && $params['key'])
        {
            $this->key = $params['key'];
        }
        if (isset($params['value']) && $params['value'])
        {
            $this->value = $params['value'];
        }

        return $this;
    }

    /**
     * @param $key
     */
    public static function find($document_id, $key)
    {
        $pdo = new DocumentPDO();
        $query = 'SELECT * FROM ' . $pdo->table_rows . ' WHERE `document_id` LIKE :document_id AND `key` LIKE :key';
        $statement = $pdo->pdo->prepare($query);
        $statement->execute(['document_id' => $document_id, 'key' => $key]);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $row = array_shift($result);
        if ($row['key'])
        {
            return self::convertRowToModel($row['document_id'], $row);
        }

        return false;
    }



    /**
     * @param string $keyLike
     */
    public static function findAll($document_id = '', $keyLike = '')
    {
        $pdo = new DocumentPDO();
        $searchArray = [];

        $query = 'SELECT * FROM ' . $pdo->table_rows . ' WHERE 1';
        if ($document_id)
        {
            $query .= ' AND `document_id` LIKE :document_id ';
            $searchArray = ['document_id' => $document_id];
        }
        if ($keyLike)
        {
            $query .= ' AND `key` LIKE :key ';
            $searchArray = ['key' => ('%' . $keyLike . '%')];
        }

        $statement = $pdo->pdo->prepare($query);
        $statement->execute($searchArray);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $return = [];
        foreach ($result as $row)
        {
            $return[] = self::convertRowToModel($row['document_id'], $row);
        }

        return $return;
    }



    public function getDocument()
    {
        return Document::find($this->document_id);
    }



    /**
     * @param $row
     * @return Document
     */
    public static function convertRowToModel($document_id, $row)
    {
        $document_row = new self($document_id);
        foreach ($row as $k => $v)
        {
            if (property_exists($document_row, $k))
            {
                $document_row->$k = $v;
            }
        }

        return $document_row;
    }



    /**
     * Saves this model to the database
     */
    public function save()
    {
        $pdo = new DocumentPDO();
        foreach ($this->sql_fields as $sql_field)
        {
            $query_insert_fieldnames[] = '`' . $sql_field . '`';
            $query_insert_placeholders[] = ':' . $sql_field;
            $query_values[$sql_field] = $this->$sql_field;
        }
        $this->created_at = time();
        $query_insert_fieldnames[] = '`created_at`';
        $query_insert_placeholders[] = ':created_at';
        $query_values['created_at'] = $this->created_at;

        $query = 'INSERT INTO `' . $pdo->table_rows . '` ( ' . implode(', ', $query_insert_fieldnames) . ' ) VALUES ( ' . implode(', ', $query_insert_placeholders) . ' )';
        $statement = $pdo->pdo->prepare($query);
        if (! $statement->execute($query_values))
        {
            /*
             * TODO - debugging
             */
            print_r($statement->errorInfo());
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            return $pdo->pdo->lastInsertId();
        }
    }



    /**
     * @param $key
     * @param $value
     */
    public function update(array $values = [])
    {
        $pdo = new DocumentPDO();
        $query_update = [];
        $query_values = [];

        foreach ($this->sql_fields as $sql_field)
        {
            $query_update[] = '`' . $sql_field . '` = :' . $sql_field;
            $query_values[$sql_field] = isset($values[$sql_field]) ? $values[$sql_field] : $this->$sql_field;
        }

        $this->updated_at = time();
        $query_update[] = '`updated_at` = :updated_at';
        $query_values['updated_at'] = $this->updated_at;

        $query = 'UPDATE `' . $pdo->table_rows . '` SET ' . implode(', ', $query_update) . ' WHERE `key` = "' . $this->key . '" AND document_id = "' . $this->document_id . '"';
        $statement = $pdo->pdo->prepare($query);
        if (! $statement->execute($query_values))
        {
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            return $this;
        }

        $this->document->update();
    }





    public function delete()
    {
        $pdo = new DocumentPDO();

        $query = 'DELETE FROM `' . $pdo->table_rows . '` WHERE `key` = "' . $this->key . '"';
        $count = $pdo->pdo->exec($query);
        $this->document->update();
        return $count;
    }



    public static function deleteAll($document_id)
    {
        $pdo = new DocumentPDO();

        $query = 'DELETE FROM `' . $pdo->table_rows . '` WHERE `document_id` = ' . intval($document_id);
        $count = $pdo->pdo->exec($query);

        $document = Document::find($document_id);
        if ($document)
        {
            $document->update(['updated_at' => time()]);
        }
        return $count;
    }



}