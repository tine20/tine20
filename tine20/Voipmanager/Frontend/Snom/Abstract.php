<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Snom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for Snom frontend
 *
 * @package     Voipmanager
 * @subpackage  Snom
 */
abstract class Voipmanager_Frontend_Snom_Abstract extends Tinebase_Frontend_Abstract
{
    /**
     * authenticate the phone against the database
     *
     */
    protected function _authenticate()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' PHP_AUTH_USER not set');
            header('WWW-Authenticate: Basic realm="Tine 2.0"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' authenticate ' . $_SERVER['PHP_AUTH_USER']);
        
        $authAdapter = new Zend_Auth_Adapter_DbTable(Tinebase_Core::getDb());
        $authAdapter->setTableName(SQL_TABLE_PREFIX . 'snom_phones')
            ->setIdentityColumn('http_client_user')
            ->setCredentialColumn('http_client_pass')
            ->setIdentity($_SERVER['PHP_AUTH_USER'])
            ->setCredential($_SERVER['PHP_AUTH_PW']);

        // Perform the authentication query, saving the result
        $authResult = $authAdapter->authenticate();
        
        if (!$authResult->isValid()) {
            Zend_Registry::get('logger')->warn(__METHOD__ . '::' . __LINE__ . ' authentication failed for ' . $_SERVER['PHP_AUTH_USER']);
            header('WWW-Authenticate: Basic realm="Tine 2.0"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }                
    }
    
    /**
     * generate URL with query parameters to access this installation again
     *
     * @return string the complete URI http://hostname/path/index.php
     */
    protected function _getBaseUrl()
    {
        $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $name = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':' . $_SERVER['SERVER_PORT'] : '' ;
        
        $baseURL = $protocol . $name . $port . $_SERVER['PHP_SELF'];
        
        return $baseURL;
    }
    
    /**
     * generate URL with query parameters to access this installation again
     *
     * @return string the complete URI http://hostname/path/index.php
     */
    public static function getBaseUrl()
    {
        $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $name = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':' . $_SERVER['SERVER_PORT'] : '' ;
        
        $baseURL = $protocol . $name . $port . $_SERVER['PHP_SELF'];
        
        return $baseURL;
    }
}