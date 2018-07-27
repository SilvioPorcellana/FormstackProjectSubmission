<?php

/**
 * This is the RESTable class that implements the processAPI method which will then call the appropriate method,
 * depending on the actual request
 *
 * @see the task-manager-api.raml file for the full specification on the API
 */

namespace Models;

use libs\RESTable;
use Models\Document;


class DocumentAPI extends RESTable
{

    /*
     * Constructor - creates a new TaskRegistry instance and passes the request to parent
     */
    public function __construct($request = '')
    {
        return parent::__construct($request);
    }


    /**
     * TODO - auth
     * @param $token
     */
    public function authorizeToken($token)
    {
        if ($token != '123')
        {
            $this->_header(401);
            die;
        }
    }


    /**
     * /documents method - only accepts GET, can be called with an argument and query or without
     *
     * @param array $args
     * @param array $query
     * @return array|Document
     */
    public function documents($args = [], $query = [])
    {
        if ($this->method == "GET")
        {
            if ($args[0])
            {
                $document = Document::find($args[0]);
                return $document;
            }
            else
            {
                $documents = Document::findAll($query['s']);
                return $documents;
            }
        }
        else
        {
            throw new \BadMethodCallException();
        }
    }


    /**
     * CRUD operations on single document
     *
     * @param array $args
     * @return int|mixed
     */
    public function document($args = [])
    {
        if ($this->method == "POST")
        {
            if (isset($args[0]) && $args[0])
            {
                // it's an update
                $document = Document::find($args[0]);
                return $document->update($this->request);
            }
            else
            {
                // new task
                $document = new Document($this->request);
                return $document->save();
            }
        }
        elseif ($this->method == "DELETE")
        {
            if (isset($args[0]) && $args[0])
            {
                // it's an update
                $document = Document::find($args[0]);
                return $document->delete();
            }
        }
        else
        {
            throw new \BadMethodCallException();
        }
    }
}