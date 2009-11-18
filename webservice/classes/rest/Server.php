<?php

/**
* Invokes  Rest Service  API for KnowledgeTree.
*
*
* KnowledgeTree Community Edition
* Document Management Made Simple
* Copyright (C) 2008,2009 KnowledgeTree Inc.
* 
*
* This program is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License version 3 as published by the
* Free Software Foundation.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
* details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* You can contact KnowledgeTree Inc., PO Box 7775 #87847, San Francisco,
* California 94120-7775, or email info@knowledgetree.com.
*
* The interactive user interfaces in modified source and object code versions
* of this program must display Appropriate Legal Notices, as required under
* Section 5 of the GNU General Public License version 3.
*
* In accordance with Section 7(b) of the GNU General Public License version 3,
* these Appropriate Legal Notices must retain the display of the "Powered by
* KnowledgeTree" logo and retain the original copyright notice. If the display of the
* logo is not reasonably feasible for technical reasons, the Appropriate Legal Notices
* must display the words "Powered by KnowledgeTree" and retain the original
* copyright notice.
*
* @copyright 2008-2009, KnowledgeTree Inc.
* @license GNU General Public License version 3
* @author KnowledgeTree Team
* @package Webservice
* @version Version 0.1
*/

/**
 * Rest_Interface
 */
require_once 'classes/rest/Interface.php';

/**
 * Rest_Reflection
 */
require_once 'classes/rest/Reflection.php';

/**
 * Rest_Abstract
 */
require_once 'classes/rest/Abstract.php';

/**
 * Rest_Exception
 *
 */
require_once 'classes/rest/Exception.php';

class Rest_Server implements Rest_Interface
{
    /**
     * Class Constructor Args
     * @var array
     */
    protected $_args = array();

    /**
     * @var string Encoding
     */
    protected $_encoding = 'UTF-8';

    /**
     * @var array An array of Rest_Reflect_Method
     */
    protected $_functions = array();

    /**
     * @var array Array of headers to send
     */
    protected $_headers = array();

    /**
     * @var array PHP's Magic Methods, these are ignored
     */
    protected static $magicMethods = array(
        '__construct',
        '__destruct',
        '__get',
        '__set',
        '__call',
        '__sleep',
        '__wakeup',
        '__isset',
        '__unset',
        '__tostring',
        '__clone',
        '__set_state',
    );

    /**
     * @var string Current Method
     */
    protected $_method;

    /**
     * @var Rest_Reflection
     */
    protected $_reflection = null;

    /**
     * Whether or not {@link handle()} should send output or return the response.
     * @var boolean Defaults to false
     */
    protected $_returnResponse = false;

    /**
     * Constructor
     */
    public function __construct()
    {
		set_exception_handler(array($this, "fault"));
        $this->_reflection = new Rest_Reflection();
    }

    /**
     * Set XML encoding
     *
     * @param  string $encoding
     * @return Rest_Server
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = (string) $encoding;
        return $this;
    }

    /**
     * Get XML encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Lowercase a string
     *
     * Lowercase's a string by reference
     *
     * @param string $value
     * @param string $key
     * @return string Lower cased string
     */
    public static function lowerCase(&$value, &$key)
    {
        return $value = strtolower($value);
    }

    /**
     * Whether or not to return a response
     *
     * If called without arguments, returns the value of the flag. If called
     * with an argument, sets the flag.
     *
     * When 'return response' is true, {@link handle()} will not send output,
     * but will instead return the response from the dispatched function/method.
     *
     * @param boolean $flag
     * @return boolean|Rest_Server Returns Rest_Server when used to set the flag; returns boolean flag value otherwise.
     */
    public function returnResponse($flag = null)
    {
        if (null == $flag) {
            return $this->_returnResponse;
        }

        $this->_returnResponse = ($flag) ? true : false;
        return $this;
    }

    /**
     * Implement Rest_Interface::handle()
     *
     * @param  array $request
     * @throws Rest_Server_Exception
     * @return string|void
     */
    public function handle($request = false)
    {
        $this->_headers = array('Content-Type: text/xml');
        if (!$request) {
            $request = $_REQUEST;
        }
        if (isset($request['method'])) {
            $this->_method = $request['method'];
            if (isset($this->_functions[$this->_method])) {
                if ($this->_functions[$this->_method] instanceof Rest_Reflection_Function || $this->_functions[$this->_method] instanceof Rest_Reflection_Method && $this->_functions[$this->_method]->isPublic()) {
                    $request_keys = array_keys($request);
                    array_walk($request_keys, array(__CLASS__, "lowerCase"));
                    $request = array_combine($request_keys, $request);

                    $func_args = $this->_functions[$this->_method]->getParameters();

                    $calling_args = array();
                    foreach ($func_args as $arg) {
                        if (isset($request[strtolower($arg->getName())])) {
                            $calling_args[] = $request[strtolower($arg->getName())];
                        } elseif ($arg->isOptional()) {
                            $calling_args[] = $arg->getDefaultValue();
                        }
                    }

                    foreach ($request as $key => $value) {
                        if (substr($key, 0, 3) == 'arg') {
                            $key = str_replace('arg', '', $key);
                            $calling_args[$key] = $value;
                        }
                    }

                    // Sort arguments by key -- @see ZF-2279
                    ksort($calling_args);

                    $result = false;
                    if (count($calling_args) < count($func_args)) {
                        $result = $this->fault(new Rest_Exception('Invalid Method Call to ' . $this->_method . '. Requires ' . count($func_args) . ', ' . count($calling_args) . ' given.'), 400);
                    }

                    if (!$result && $this->_functions[$this->_method] instanceof Rest_Reflection_Method) {
                        // Get class
                        $class = $this->_functions[$this->_method]->getDeclaringClass()->getName();

                        if ($this->_functions[$this->_method]->isStatic()) {
                            // for some reason, invokeArgs() does not work the same as
                            // invoke(), and expects the first argument to be an object.
                            // So, using a callback if the method is static.
                            $result = $this->_callStaticMethod($class, $calling_args);
                        } else {
                            // Object method
                            $result = $this->_callObjectMethod($class, $calling_args);
                        }
                    } elseif (!$result) {
                        try {
                            $result = call_user_func_array($this->_functions[$this->_method]->getName(), $calling_args); //$this->_functions[$this->_method]->invokeArgs($calling_args);
                        } catch (Exception $e) {
                            $result = $this->fault($e);
                        }
                    }
                } else {

                    $result = $this->fault(
                        new Rest_Exception("Unknown Method '$this->_method'."),
                        404
                    );
                }
            } else {
                    $result = $this->fault(
                    new Rest_Exception("Unknown Method '$this->_method'."),
                    404
                );
            }
        } else {
                $result = $this->fault(
                new Rest_Exception("No Method Specified."),
                404
            );
        }

        if ($result instanceof SimpleXMLElement) {
            $response = $result->asXML();
        } elseif ($result instanceof DOMDocument) {
            $response = $result->saveXML();
        } elseif ($result instanceof DOMNode) {
            $response = $result->ownerDocument->saveXML($result);
        } elseif (is_array($result) || is_object($result)) {
            $response = $this->_handleStruct($result);
        } else {
            $response = $this->_handleScalar($result);
        }

        if (!$this->returnResponse()) {
            if (!headers_sent()) {
                foreach ($this->_headers as $header) {
                    header($header);
                }
            }

            echo $response;
            return;
        }

        return $response;
     }

    /**
     * Implement Rest_Interface::setClass()
     *
     * @param string $classname Class name
     * @param string $namespace Class namespace (unused)
     * @param array $argv An array of Constructor Arguments
     */
    public function setClass($classname, $namespace = '', $argv = array())
    {
        $this->_args = $argv;
        foreach ($this->_reflection->reflectClass($classname, $argv)->getMethods() as $method) {
            $this->_functions[$method->getName()] = $method;
        }
    }

    /**
     * Handle an array or object result
     *
     * @param array|object $struct Result Value
     * @return string XML Response
     */
    protected function _handleStruct($struct)
    {
        $function = $this->_functions[$this->_method];
        if ($function instanceof Rest_Reflection_Method) {
            $class = $function->getDeclaringClass()->getName();
        } else {
            $class = false;
        }

        $method = $function->getName();

        $dom    = new DOMDocument('1.0', $this->getEncoding());
        if ($class) {
            $root   = $dom->createElement($class);
            $method = $dom->createElement($method);
            $root->appendChild($method);
        } else {
            $root   = $dom->createElement($method);
            $method = $root;
        }
        $root->setAttribute('generator', 'Knowledgetree');
        $root->setAttribute('version', '1.0');
        $dom->appendChild($root);

        $this->_structValue($struct, $dom, $method);

        $struct = (array) $struct;
        if (!isset($struct['status'])) {
            $status = $dom->createElement('status', 'success');
            $method->appendChild($status);
        }

        return $dom->saveXML();
    }

    /**
     * Recursively iterate through a struct
     *
     * Recursively iterates through an associative array or object's properties
     * to build XML response.
     *
     * @param mixed $struct
     * @param DOMDocument $dom
     * @param DOMElement $parent
     * @return void
     */
    protected function _structValue($struct, DOMDocument $dom, DOMElement $parent)
    {
        $struct = (array) $struct;

        foreach ($struct as $key => $value) {
            if ($value === false) {
                $value = 0;
            } elseif ($value === true) {
                $value = 1;
            }

            if (ctype_digit((string) $key)) {
                $key = 'key_' . $key;
            }

            if (is_array($value) || is_object($value)) {
                $element = $dom->createElement($key);
                $this->_structValue($value, $dom, $element);
            } else {
                $element = $dom->createElement($key);
                $element->appendChild($dom->createTextNode($value));
            }

            $parent->appendChild($element);
        }
    }

    /**
     * Handle a single value
     *
     * @param string|int|boolean $value Result value
     * @return string XML Response
     */
    protected function _handleScalar($value)
    {
        $function = $this->_functions[$this->_method];
        if ($function instanceof Rest_Reflection_Method) {
            $class = $function->getDeclaringClass()->getName();
        } else {
            $class = false;
        }

        $method = $function->getName();

        $dom = new DOMDocument('1.0', $this->getEncoding());
        if ($class) {
            $xml = $dom->createElement($class);
            $methodNode = $dom->createElement($method);
            $xml->appendChild($methodNode);
        } else {
            $xml = $dom->createElement($method);
            $methodNode = $xml;
        }
        $xml->setAttribute('generator', 'KnowledgeTree');
        $xml->setAttribute('version', '1.0');
        $dom->appendChild($xml);

        if ($value === false) {
            $value = 0;
        } elseif ($value === true) {
            $value = 1;
        }

        if (isset($value)) {
            $element = $dom->createElement('response');
            $element->appendChild($dom->createTextNode($value));
            $methodNode->appendChild($element);
        } else {
            $methodNode->appendChild($dom->createElement('response'));
        }

        $methodNode->appendChild($dom->createElement('status', 'success'));

        return $dom->saveXML();
    }

    /**
     * Implement Rest_Interface::fault()
     *
     * Creates XML error response, returning DOMDocument with response.
     *
     * @param string|Exception $fault Message
     * @param int $code Error Code
     * @return DOMDocument
     */
    public function fault($exception = null, $code = null)
    {
        if (isset($this->_functions[$this->_method])) {
            $function = $this->_functions[$this->_method];
        } elseif (isset($this->_method)) {
            $function = $this->_method;
        } else {
            $function = 'rest';
        }

        if ($function instanceof Rest_Reflection_Method) {
            $class = $function->getDeclaringClass()->getName();
        } else {
            $class = false;
        }

        if ($function instanceof Rest_Reflection_Function_Abstract) {
            $method = $function->getName();
        } else {
            $method = $function;
        }

        $dom = new DOMDocument('1.0', $this->getEncoding());
        if ($class) {
            $xml       = $dom->createElement($class);
            $xmlMethod = $dom->createElement($method);
            $xml->appendChild($xmlMethod);
        } else {
            $xml       = $dom->createElement($method);
            $xmlMethod = $xml;
        }
        $xml->setAttribute('generator', 'KnowledgeTree');
        $xml->setAttribute('version', '1.0');
        $dom->appendChild($xml);

        $xmlResponse = $dom->createElement('response');
        $xmlMethod->appendChild($xmlResponse);

        if ($exception instanceof Exception) {
            $element = $dom->createElement('message');
            $element->appendChild($dom->createTextNode($exception->getMessage()));
            $xmlResponse->appendChild($element);
            $code = $exception->getCode();
        } elseif (($exception !== null) || 'rest' == $function) {
            $xmlResponse->appendChild($dom->createElement('message', 'An unknown error occured. Please try again.'));
        } else {
            $xmlResponse->appendChild($dom->createElement('message', 'Call to ' . $method . ' failed.'));
        }

        $xmlMethod->appendChild($xmlResponse);
        $xmlMethod->appendChild($dom->createElement('status', 'failed'));

        // Headers to send
        if ($code === null || (404 != $code)) {
            $this->_headers[] = 'HTTP/1.0 400 Bad Request';
        } else {
            $this->_headers[] = 'HTTP/1.0 404 File Not Found';
        }

        return $dom;
    }

    /**
     * Retrieve any HTTP extra headers set by the server
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Implement Rest_Interface::addFunction()
     *
     * @param string $function Function Name
     * @param string $namespace Function namespace (unused)
     */
    public function addFunction($function, $namespace = '')
    {
        if (!is_array($function)) {
            $function = (array) $function;
        }

        foreach ($function as $func) {
            if (is_callable($func) && !in_array($func, self::$magicMethods)) {
                $this->_functions[$func] = $this->_reflection->reflectFunction($func);
            } else {
                throw new Rest_Exception("Invalid Method Added to Service.");
            }
        }
    }

    /**
     * Implement Rest_Interface::getFunctions()
     *
     * @return array An array of Rest_Reflection_Method's
     */
    public function getFunctions()
    {
        return $this->_functions;
    }

    /**
     * Implement Rest_Interface::loadFunctions()
     *
     * @todo Implement
     * @param array $functions
     */
    public function loadFunctions($functions)
    {
    }

    /**
     * Implement Rest_Interface::setPersistence()
     *
     * @todo Implement
     * @param int $mode
     */
    public function setPersistence($mode)
    {
    }

    /**
     * Call a static class method and return the result
     *
     * @param  string $class
     * @param  array $args
     * @return mixed
     */
    protected function _callStaticMethod($class, array $args)
    {
        try {
            $result = call_user_func_array(array($class, $this->_functions[$this->_method]->getName()), $args);
        } catch (Exception $e) {
            $result = $this->fault($e);
        }
        return $result;
    }

    /**
     * Call an instance method of an object
     *
     * @param  string $class
     * @param  array $args
     * @return mixed
     * @throws Rest_Exception For invalid class name
     */
    protected function _callObjectMethod($class, array $args)
    {
        try {
            if ($this->_functions[$this->_method]->getDeclaringClass()->getConstructor()) {
                $object = $this->_functions[$this->_method]->getDeclaringClass()->newInstanceArgs($this->_args);
            } else {
                $object = $this->_functions[$this->_method]->getDeclaringClass()->newInstance();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            throw new Rest_Exception('Error instantiating class ' . $class . ' to invoke method ' . $this->_functions[$this->_method]->getName(), 500);
        }

        try {
            $result = $this->_functions[$this->_method]->invokeArgs($object, $args);
        } catch (Exception $e) {
            $result = $this->fault($e);
        }

        return $result;
    }
}
