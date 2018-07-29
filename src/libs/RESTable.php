<?php

namespace libs;

/**
 * Abstract class to add REST functionalities to models.
 *
 * This abstract class is used as parent for other classes that want to offer a REST interface to models. This way,
 * we can create model classes (like the Document class) and then, extending from this abstract class, simply add
 * REST access to that model, via GET/POST/DELETE/PUT
 *
 * @author Silvio Porcellana <silvio.porcellana@gmail.com>
 * @version 1.0.0
 */

abstract class RESTable
{
    /**
     * @property striong $version
     * Used for versioning, calls to API need to be in the form /v<version>/<endpoint>
     */
    protected $version = '';

    /**
     * @property string $method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * @property string $endpoint
     * The Model requested in the URI. eg: /v1/tasks
     */
    protected $endpoint = '';

    /**
     * @property string $verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /v1/tasks/process
     */
    protected $verb = '';

    /**
     * @property array $args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /v1/<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = [];

    /**
     * @property array $query
     * All query string params, used for example for sorting, ordering or for passing other modifiers to the query.
     * For example /v1/tasks?orderby=name&limit=10&search=email
     */
    protected $query = [];


    /**
     * @property string file
     * Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * @property string token
     * Stores the token
     */
    protected $token = Null;


    /**
     * RESTable constructor.
     * @param $request      Either passed manually or taken from the $_REQUEST['request'] superglobal
     */
    public function __construct($request = '')
    {
        if (empty($request))
        {
            $request = isset($_REQUEST['request']) ? $_REQUEST['request'] : false;
        }
        if (empty($request))
        {
            $request = $_SERVER['REDIRECT_URL'];
        }

        if (! $request)
        {
            return false;
        }

        // remove (optional) initial slash from request (we get this in the REDIRECT_URL)
        $request = preg_replace('/^\//', '', $request);

        $this->_preFlightCheck();

        /**
         * get args from url
         * format: /v<version>/<endpoint>/<verb|
         */
        $this->args = explode('/', rtrim($request, '/'));
        $this->version = array_shift($this->args);
        $this->endpoint = array_shift($this->args);
        /**
         * add other query string params to the $query property
         */
        if (is_array($_GET) && count($_GET) > 0)
        {
            foreach ($_GET as $_get_key => $_get_value)
            {
                if ($_get_key == "request") continue;
                $this->query[$_get_key] = $_get_value;
            }
        }

        /**
         * Get the method to use for this API call
         */
        $this->method = $_SERVER['REQUEST_METHOD'];

        /**
         * Check if the method is called in the HTTP_X_HTTP_METHOD $_SERVER superglobal
         */
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
        {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
            {
                $this->method = 'DELETE';
            }
            elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
            {
                $this->method = 'PUT';
            }
            else
            {
                throw new Exception("Unexpected Header");
            }
        }

        /**
         * Prepare and store the $request data depending on the method chosen
         */
        switch($this->method)
        {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }



    /**
     * TODO
     * @return bool|null|string
     */
    public function getToken()
    {
        /* Variables that will hold the credentials passed by user. */
        $token = null;

        // mod_php
        if (isset($_SERVER['PHP_AUTH_USER']))
        {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            $token = base64_encode("$username:$password");
            // most other servers
        }
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0)
            {
                $token = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
            }
        }

        if (is_null($token))
        {
            $this->_header(401);
            return false;
        }
        return $token;
    }



    /**
     * This is the method that actually calls the requested endpoint of the children class,
     * passing it the args and query string received
     *
     * @return string
     */
    public function processAPI()
    {
        if (method_exists($this, $this->endpoint))
        {
            return $this->_response($this->{$this->endpoint}($this->args, $this->query));
        }
        return $this->_response("No Endpoint: $this->endpoint", 404);
    }


    /**
     * A CORS preflight request is a CORS request that checks to see if the CORS protocol is understood.
     *
     * @see https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request
     */
    private function  _preFlightCheck()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
        {
            /**
             * only allow CORS if we're doing a GET
             */
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET')
            {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Methods: GET');
                header('Access-Control-Allow-Headers: X-Requested-With, Authorization, Origin, Accept, Content-Type');
            }
            exit;
        }

    }


    private function _cleanInputs($data)
    {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }


    private function _response($data, $status = 200)
    {
        $this->_header($status);
        header('Content-Type: application/json');
        $json = json_encode($data);
        return $json;
    }


    protected function _requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }


    protected function _header($code)
    {
        header('HTTP/1.1 ' . $code . ' ' . $this->_requestStatus($code));
    }
}

?>