<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Guilherme Striquer Bisotto <guilherme.bisotto@serpro.gov.br>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class for Session and Session Namespaces in Core
 * 
 * @package     Tinebase
 * @subpackage  Session
 */
class Tinebase_Session extends Tinebase_Session_Abstract
{
    /**
     * Session namespace for Tinebase Core data
     */
    const NAMESPACE_NAME = 'Tinebase_Core_Session_Namespace';
    
    /**
     * Register Validator for account status
     */
    public static function registerValidatorAccountStatus()
    {
        Zend_Session::registerValidator(new Tinebase_Session_Validator_AccountStatus());
    }
    
    /**
     * Register Validator for Http User Agent
     */
    public static function registerValidatorHttpUserAgent()
    {
        Zend_Session::registerValidator(new Zend_Session_Validator_HttpUserAgent());
    }
    
    /**
     * Register Validator for Ip Address
     */
    public static function registerValidatorIpAddress()
    {
        Zend_Session::registerValidator(new Zend_Session_Validator_IpAddress());
    }
}
