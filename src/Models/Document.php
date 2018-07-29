<?php

namespace Models;
use libs\DocumentPDO;

/**
 * This is the main class for the Document model. A Document has a one-to-many relationship with DocumentRows and
 * some metadata such as created_at, updated_at etc.
 *
 * @property string $name
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $exported_at
 * @property array $rows
 */


class Document
{
    public $id;

    public $name;
    public $created_at;
    public $updated_at;
    public $exported_at;
    public $rows = [];

    private $sql_fields = ['name', 'created_at', 'exported_at'];

    /**
     * Document constructor.
     *
     * @param $key
     * @param $value
     */
    public function __construct(array $params = [])
    {
        if (isset($params['name']) && $params['name'])
        {
            $this->name = $params['name'];
        }
        if (isset($params['rows']) && is_array($params['rows']) && count($params['rows']) > 0)
        {
            $this->addRows($params['rows']);
        }

        return $this;
    }



    public function addRows(array $rows)
    {
        foreach ($rows as $row)
        {
            if (is_array($row))
            {
                $this->addRow($row);
            }
        }

        return $this;
    }



    public function addRow(array $row)
    {
        $this->rows[] = DocumentRow::convertRowToModel($this->id, $row);
        return $this;
    }


    public function deleteRow($key)
    {
        $_ok_rows = [];

        foreach ($this->rows as $row)
        {
            if ($row->key == $key)
            {
                $row->delete();
            }
            else
            {
                $_ok_rows[] = $row;
            }
        }

        $this->update();

        $this->rows = $_ok_rows;
        return $this;
    }



    /**
     * @param $key
     */
    public static function find($id)
    {
        $pdo = new DocumentPDO();

        /**
         * get document
         */
        $query = 'SELECT * FROM ' . $pdo->table_documents . ' WHERE `id` LIKE :id';
        $statement = $pdo->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $row = array_shift($result);
        if ($row['id'])
        {
            /**
             * this takes care of getting also the rows
             */
            $model = self::_convertRowToModel($row);
            return $model;
        }

        return false;
    }



    /**
     * @param string $nameLike
     */
    public static function findAll($nameLike = '')
    {
        $pdo = new DocumentPDO();
        $searchArray = [];

        $query = 'SELECT * FROM ' . $pdo->table_documents . ' WHERE 1';
        if ($nameLike)
        {
            $query .= ' AND `name` LIKE :name ';
            $searchArray = ['name' => ('%' . $nameLike . '%')];
        }

        $statement = $pdo->pdo->prepare($query);
        $statement->execute($searchArray);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $return = [];
        foreach ($result as $row)
        {
            $return[] = self::_convertRowToModel($row);
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

        /**
         * now get also rows
         */
        $document->rows = DocumentRow::findAll($document->id);

        return $document;
    }



    /**
     * Saves this model to the database
     */
    public function save()
    {
        $this->created_at = time();

        $pdo = new DocumentPDO();

        foreach ($this->sql_fields as $sql_field)
        {
            if (! isset($this->$sql_field))
            {
                continue;
            }
            $query_insert_fieldnames[] = '`' . $sql_field . '`';
            $query_insert_placeholders[] = ':' . $sql_field;
            $query_values[$sql_field] = $this->$sql_field;
        }

        $query = 'INSERT INTO `' . $pdo->table_documents . '` ( ' . implode(', ', $query_insert_fieldnames) . ' ) VALUES ( ' . implode(', ', $query_insert_placeholders) . ' )';

        #print_r(debug_backtrace()) . "\n\n";

        $statement = $pdo->pdo->prepare($query);
        if (! $statement->execute($query_values))
        {
            /*
             * TODO - debugging
             */
            print_r($statement->errorInfo());
            throw new \BadMethodCallException('Cannot execute query (' . $query . ') [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            /**
             * save rows
             */
            if (is_array($this->rows))
            {
                foreach ($this->rows as $row)
                {
                    $row->document_id = $pdo->pdo->lastInsertId();
                    $row->save();
                }
            }

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
            if (! isset($values[$sql_field]))
            {
                continue;
            }
            $query_update[] = '`' . $sql_field . '` = :' . $sql_field;
            $query_values[$sql_field] = isset($values[$sql_field]) ? $values[$sql_field] : $this->$sql_field;
        }

        $this->updated_at = time();
        $query_update[] = '`updated_at` = :updated_at';
        $query_values['updated_at'] = $this->updated_at;

        $query = 'UPDATE `' . $pdo->table_documents . '` SET ' . implode(', ', $query_update) . ' WHERE `id` = "' . $this->id . '"';
        $statement = $pdo->pdo->prepare($query);
        if (! $statement->execute($query_values))
        {
            throw new \BadMethodCallException('Cannot execute query [' . $statement->errorInfo()[2] . ']');
        }
        else
        {
            if (is_array($values['rows'])) {
                $_updated_rows = [];
                foreach ($values['rows'] as $row_array)
                {
                    $row = DocumentRow::find($this->id, $row_array['key']);
                    if ($row)
                    {
                        $row->update($row_array);
                    }
                    else
                    {
                        $row = new DocumentRow($this->id, $row_array);
                        $row->save();
                    }
                    $_updated_rows[] = $row;
                }
                $this->rows = $_updated_rows;
            }
            return $this;
        }
    }



    public function updateRow($key, array $values = [])
    {
        $row = DocumentRow::find($this->id, $key);
        $row->update($values);

        $this->rows = DocumentRow::findAll($this->id);
        return $this;
    }



    public function delete()
    {
        $pdo = new DocumentPDO();

        $query = 'DELETE FROM `' . $pdo->table_documents . '` WHERE `id` = "' . $this->id . '"';
        $count = $pdo->pdo->exec($query);

        DocumentRow::deleteAll($this->id);

        return $count;
    }



    public function export($format = 'csv')
    {
        return DocumentExport::export($this->id);
    }



    public static function formatDate($datetime, $format = '')
    {
        if (! $format)
        {
            $format = "F j, Y, g:i a";
        }

        if (is_numeric($datetime))
        {
            $datetime = date('Y-m-d H:i:s', $datetime);
        }
        $dt = new \DateTime($datetime);
        return $dt->format($format);
    }


}