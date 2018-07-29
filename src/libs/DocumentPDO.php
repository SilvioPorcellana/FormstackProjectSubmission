<?php

namespace libs;

/**
 * Class DocumentPDO
 * @package libs
 *
 * @property \PDO $pdo
 * @property string $table_documents
 * @property string $table_rows
 */

class DocumentPDO
{

    public $pdo;
    public $table_documents;
    public $table_rows;

    public function __construct()
    {
        $this->pdo = self::_pdo();
        $this->table_documents = self::_tablename();
        $this->table_rows = self::_tablename(true);
    }

    /**
     * These are two static function used in this class to read the PDO and table details from the config files
     *
     * @return \PDO
     */
    private static function _pdo()
    {
        $_config_file_path = $_SERVER['DOCUMENT_ROOT'] . '/../_config.ini';
        $config = parse_ini_file($_config_file_path);
        $_pdo = new \PDO($config['db_dsn'], $config['db_user'], $config['db_password']);
        return $_pdo;
    }

    /**
     * @return mixed
     */
    private static function _tablename($rows = false)
    {
        $_config_file_path = $_SERVER['DOCUMENT_ROOT'] . '/../_config.ini';
        $config = parse_ini_file($_config_file_path);
        if ($rows)
        {
            $_tablename = $config['db_table_rows'];
        }
        else
        {
            $_tablename = $config['db_table_documents'];
        }

        return $_tablename;
    }


}