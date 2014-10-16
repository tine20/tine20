<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Server Abstract with handle function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    /**
     * the request
     *
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $_request = NULL;
    
    /**
     * the request body
     * 
     * @var stream|string
     */
    protected $_body;
    
    /**
     * set to true if server supports sessions
     * 
     * @var boolean
     */
    protected $_supportsSessions = false;
    
    /**
     * 
     */
    public function __construct()
    {
        if ($this->_supportsSessions) {
            Tinebase_Session_Abstract::setSessionEnabled('TINE20SESSID');
        }
    }
    
    /**
     * read auth data from all available sources
     * 
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @throws Tinebase_Exception_NotFound
     * @return array
     */
    protected function _getAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($authData = $this->_getPHPAuthData($request)) {
            return $authData;
        }
        
        if ($authData = $this->_getBasicAuthData($request)) {
            return $authData;
        }
        
        throw new Tinebase_Exception_NotFound('No auth data found');
    }
    
    /**
     * fetch auch from PHP_AUTH*
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getPHPAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($request->getServer('PHP_AUTH_USER')) {
            return array(
                $request->getServer('PHP_AUTH_USER'),
                $request->getServer('PHP_AUTH_PW')
            );
        }
    }
    
    /**
     * fetch basic auth credentials
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getBasicAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($header = $request->getHeaders('Authorization')) {
            return explode(
                ":",
                base64_decode(substr($header->getFieldValue(), 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } elseif ($header = $request->getServer('HTTP_AUTHORIZATION')) {
            return explode(
                ":",
                base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } else {
            // check if (REDIRECT_)*REMOTE_USER is found in SERVER vars
            $name = 'REMOTE_USER';
            
            for ($i=0; $i<5; $i++) {
                if ($header = $request->getServer($name)) {
                    return explode(
                        ":",
                        base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                        2
                    );
                }
                
                $name = 'REDIRECT_' . $name;
            }
        }
    }
}
