<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 * 
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Protocol
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * see http://tools.ietf.org/html/draft-ietf-sieve-managesieve-09.txt
 * 
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Protocol
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Mail_Protocol_Sieve
{
    /**
     * Default timeout in seconds for initiating session
     */
    const TIMEOUT_CONNECTION = 30;
    
    /**
     * socket to Sieve
     * @var null|resource
     */
    protected $_socket;

    /**
     * Public constructor
     *
     * @param  string      $host  hostname of IP address of Sieve server, if given connect() is called
     * @param  int|null    $port  port of Sieve server, null for default (2000)
     * @param  bool|string $ssl   use 'TLS' or false
     * @throws Zend_Mail_Protocol_Exception
     */
    public function __construct($host = '', $port = null, $ssl = false)
    {
        if ($host) {
            $this->connect($host, $port, $ssl);
        }
    }


    /**
     * Public destructor
     */
    public function __destruct()
    {
        $this->logout();
    }


    /**
     * Open connection to Sieve server
     *
     * @param  string      $host  hostname of IP address of Sieve server
     * @param  int|null    $port  of Sieve server, default is 2000
     * @param  string|bool $ssl   use 'TLS' or false
     * @return string welcome message
     * @throws Zend_Mail_Protocol_Exception
     */
    public function connect($host, $port = null, $ssl = false)
    {
        if ($port === null) {
            $port = 2000;
        }

        $errno  =  0;
        $errstr = '';
        $this->_socket = @fsockopen($host, $port, $errno, $errstr, self::TIMEOUT_CONNECTION);
        if (!$this->_socket) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('cannot connect to host : ' . $errno . ' : ' . $errstr);
        }

        $welcome = $this->readResponse();

        if ($ssl === 'TLS') {
            $result = $this->requestAndResponse('STARTTLS');
            $result = $result && stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$result) {
                /**
                 * @see Zend_Mail_Protocol_Exception
                 */
                require_once 'Zend/Mail/Protocol/Exception.php';
                throw new Zend_Mail_Protocol_Exception('cannot enable TLS');
            }
        }
        
        return $welcome;
    }

    /**
     * send a request
     *
     * @param  string $command your request command
     * @param  array  $tokens  additional parameters to command, use escapeString() to prepare
     * @return null
     * @throws Zend_Mail_Protocol_Exception
     */
    public function sendRequest($command, $tokens = array())
    {
        $line = $command;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (@fputs($this->_socket, $line . ' ' . $token[0] . "\r\n") === false) {
                    /**
                     * @see Zend_Mail_Protocol_Exception
                     */
                    require_once 'Zend/Mail/Protocol/Exception.php';
                    throw new Zend_Mail_Protocol_Exception('cannot write - connection closed?');
                }
                $line = $token[1];
            } else {
                $line .= ' ' . $token;
            }
        }
        
        if (@fputs($this->_socket, $line . "\r\n") === false) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('cannot write - connection closed?');
        }
    }
    
    /**
     * get the next line from socket with error checking, but nothing else
     *
     * @return string next line
     * @throws Zend_Mail_Protocol_Exception
     */
    protected function _nextLine()
    {
        $line = @fgets($this->_socket);
        #echo "READ: $line";
        if ($line === false) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('cannot read - connection closed?');
        }

        return $line;
    }
    
    /**
     * split a given line in tokens. a token is literal of any form or a list
     *
     * @param  string $line line to decode
     * @return array tokens, literals are returned as string, lists as array
     * @throws Zend_Mail_Protocol_Exception
     */
    protected function _decodeLine($line)
    {
        $tokens = array();
        $stack = array();

        /*
            We start to decode the response here. The unterstood tokens are:
                literal
                "literal" or also "lit\\er\"al"
                {bytes}<NL>literal
                (literals*)
            All tokens are returned in an array. Literals in braces (the last unterstood
            token in the list) are returned as an array of tokens. I.e. the following response:
                "foo" baz {3}<NL>bar ("f\\\"oo" bar)
            would be returned as:
                array('foo', 'baz', 'bar', array('f\\\"oo', 'bar'));
                
            // TODO: add handling of '[' and ']' to parser for easier handling of response text 
        */
        //  replace any trailling <NL> including spaces with a single space
        $line = rtrim($line) . ' ';
        while (($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
            while ($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = array();
                $token = substr($token, 1);
            }
            if ($token[0] == '"') {
                if (preg_match('%^"((.|\\\\|\\")*?)" *%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, strlen($matches[0]));
                    continue;
                }
            }
            if ($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos - 1);
                // a literal can be {1234+} and {1234}
                // see http://tools.ietf.org/html/rfc2244#section-2.6.3
                $chars = rtrim($chars, '+');
                if (is_numeric($chars)) {
                    $token = '';
                    while (strlen($token) < $chars) {
                        $token .= $this->_nextLine();
                    }
                    $line = '';
                    if (strlen($token) > $chars) {
                        $line = substr($token, $chars);
                        $token = substr($token, 0, $chars);
                    } else {
                        $line .= $this->_nextLine();
                    }
                    $tokens[] = $token;
                    $line = trim($line) . ' ';
                    continue;
                }
            }
            if ($stack && $token[strlen($token) - 1] == ')') {
                // closing braces are not seperated by spaces, so we need to count them
                $braces = strlen($token);
                $token = rtrim($token, ')');
                // only count braces if more than one
                $braces -= strlen($token) + 1;
                // only add if token had more than just closing braces
                if ($token) {
                    $tokens[] = $token;
                }
                $token = $tokens;
                $tokens = array_pop($stack);
                // special handline if more than one closing brace
                while ($braces-- > 0) {
                    $tokens[] = $token;
                    $token = $tokens;
                    $tokens = array_pop($stack);
                }
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }

        // maybe the server forgot to send some closing braces
        while ($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }
        
        return $tokens;
    }
    
    /**
     * read a response "line" (could also be more than one real line if response has {..}<NL>)
     * and do a simple decode
     *
     * @param  array|string  $tokens    decoded tokens are returned by reference, if $dontParse
     *                                  is true the unparsed line is returned here
     * @param  string        $wantedTag check for this tag for response code. Default '*' is
     *                                  continuation tag.
     * @param  bool          $dontParse if true only the unparsed line is returned $tokens
     * @return bool if returned tag matches wanted tag
     * @throws Zend_Mail_Protocol_Exception
     */
    public function readLine(&$tokens = array(), $dontParse = false)
    {
        $line = $this->_nextLine($tag);
        if (!$dontParse) {
            $tokens = $this->_decodeLine($line);
        } else {
            $tokens = $line;
        }

        // if tag is wanted tag we might be at the end of a multiline response
        return $tag == $wantedTag;
    }
    
    /**
     * read a response
     *
     * @param  boolean $dontParse not used currently
     * @return string response
     * @throws Zend_Mail_Protocol_Exception
     */
    public function readResponse($dontParse = false)
    {
        $lines = array();
        
        while (!feof($this->_socket)) {
            $this->readLine($tokens, $dontParse);
            
            if($tokens[0] == 'OK') {
                break;
            } elseif($tokens[0] == 'NO') {
                throw new Zend_Mail_Protocol_Exception($tokens[1]);
            }        
            
            $lines[] = $tokens;
        }
        
        #var_dump($lines);
        
        return $lines;
    }
    
    /**
     * send a request and get response at once
     *
     * @param  string $command   command as in sendRequest()
     * @param  array  $tokens    parameters as in sendRequest()
     * @param  bool   $dontParse if true unparsed lines are returned instead of tokens
     * @return mixed response as in readResponse()
     * @throws Zend_Mail_Protocol_Exception
     */
    public function requestAndResponse($command, $tokens = array(), $dontParse = false)
    {
        $this->sendRequest($command, $tokens);
        $response = $this->readResponse($dontParse);

        return $response;
    }    

    /**
     * End communication with Sieve server (also closes socket)
     *
     * @return null
     */
    public function logout()
    {
        $result = false;
        if ($this->_socket) {
            try {
                $result = $this->requestAndResponse('LOGOUT');
            } catch (Zend_Mail_Protocol_Exception $e) {
                // ignoring exception
            }
            fclose($this->_socket);
            $this->_socket = null;
        }
        return $result;
        
    }

    /**
     * The HAVESPACE command is used to query the server for available
     * space.
     *
     * @todo test with a Sieve server supporting this command
     *
     * @param string    $scriptName     the script name
     * @param int       $size           the required size
     */
    public function haveSpace($scriptName, $size)
    {
        $result = $this->requestAndResponse('HAVESPACE', $this->escapeString($scriptName, $size));
    }
    
    /**
     * Get supported features from Sieve server
     *
     * @return array list of capabilities
     */
    public function capability()
    {
        $lines = $this->requestAndResponse('CAPABILITY');
        
        $capabilities = array();
        
        foreach($lines as $line) {
            list($name, $value) = $line;
            
            $name = strtoupper($name);
            
            switch($name) {
                case 'SASL':
                case 'SIEVE':
                case 'NOTIFY':
                    $capabilities[$name] = explode(' ', rtrim($value));
                    break;
                    
                default:
                    $capabilities[$name] = $value;
                    break;
            }
        }

        return $capabilities;
    }
    
    /**
     * List scripts the user has on Sieve server
     *
     * @return array list of scripts
     */
    public function listScripts()
    {
        $lines = $this->requestAndResponse('LISTSCRIPTS');
        
        $scripts = array();
        
        foreach($lines as $scriptData) {
            #var_dump($scriptData);
            $scripts[$scriptData[0]] = array(
                'name'      => $scriptData[0],
                'active'    => isset($scriptData[1]) ? true : false
            );
        }
        
        return $scripts;
    }
    
    /**
     * Return the server to the non-authenticated state
     *
     * @todo test with a Sieve server supporting this command
     */
    public function unAuthenticate()
    {
        $this->requestAndResponse('UNAUTHENTICATE');
    }
    
    /**
     * send noop
     *
     * @todo test with a Sieve server supporting this command
     *  
     * @param string   $content    a string to echo from Sieve server
     */
    public function noop($content)
    {
        $lines = $this->requestAndResponse('NOOP', array($this->escapeString($content)));        
    }

    /**
     * Submit a Sieve script to the Sieve server
     * 
     * @param string    $name       the name of the script
     * @param string    $content    the content of the script
     */
    public function putScript($name, $content)
    {
        $this->requestAndResponse('PUTSCRIPT', $this->escapeString($name, $content));
    }

    /**
     * Verify Sieve script validity without storing the script on the server
     * 
     * @todo test with a Sieve server supporting this command
     * 
     * @param string    $content    the script to validate
     */
    public function checkScript($content)
    {
        $this->requestAndResponse('CHECKSCRIPT', $this->escapeString($content));
    }
    
    /**
     * Rename script on Sieve server
     * 
     * @todo test with a Sieve server supporting this command
     * 
     * @param string    $oldName    the old name of the script
     * @param string    $newName    the new name of the script
     */
    public function renameScript($oldName, $newName)
    {
        $this->requestAndResponse('RENAMESCRIPT', $this->escapeString($oldName, $newName));
    }
    
    /**
     * Delete script on Sieve server
     * @param string    $name   the name of the script to delete
     */
    public function deleteScript($name)
    {
        $this->requestAndResponse('DELETESCRIPT', array($this->escapeString($name)));
    }
    
    /**
     * Set active script
     * 
     * @param string    $name   the name of the script to activate (set to "" to disable any active script)
     */
    public function setActive($name)
    {
        $this->requestAndResponse('SETACTIVE', array($this->escapeString($name)));
    }
    
    /**
     * escape one or more literals i.e. for sendRequest
     *
     * @param  string|array $string the literal/-s
     * @return string|array escape literals, literals with newline ar returned
     *                      as array('{size}', 'string');
     */
    public function escapeString($string)
    {
        if (func_num_args() < 2) {
            if (strpos($string, "\n") !== false) {
                return array('{' . strlen($string) . '+}', $string);
            } else {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $string) . '"';
            }
        }
        $result = array();
        foreach (func_get_args() as $string) {
            $result[] = $this->escapeString($string);
        }
        return $result;
    }
    
    /**
     * Retrieve script from Sieve server
     *
     * @param string    $name   the name of the script
     * @return string the script
     */
    public function getScript($name)
    {
        $lines = $this->requestAndResponse('GETSCRIPT', array($this->escapeString($name)));
        
        $script = implode($lines[0]);
        
        return $script;
    }
    

    /**
     * Login to Sieve server
     *
     * @todo currently only plain auth is implemented
     *
     * @param  string $username  username
     * @param  string $password  password
     * @return void
     * @throws Zend_Mail_Protocol_Exception
     */
    public function authenticate($username, $password)
    {
        $token = base64_encode(chr(0) . $username . chr(0) . $password);
        $result = $this->requestAndResponse('AUTHENTICATE',  $this->escapeString('PLAIN', $token));
    }

}
