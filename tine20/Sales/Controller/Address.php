<?php
/**
 * address controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * address controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Address extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Address();
        $this->_modelName = 'Sales_Model_Address';
        $this->_doContainerACLChecks = FALSE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Address
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Address
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * resolves all virtual fields for the address
     *
     * @param array $address
     * @return array with property => value
     */
    public function resolveVirtualFields($address)
    {
        $ft = '';
        
        $i18n = Tinebase_Translation::getTranslation($this->_applicationName)->getAdapter();
        $type = isset($address['type']) ? $address['type'] : 'postal';
        
        $ft .= isset($address['postbox']) ? $address['postbox'] : (isset($address['street']) ? $address['street'] : '');
        $ft .= ', ';
        $ft .= isset($address['postalcode']) ? $address['postalcode'] : '';
        $ft .= ' ';
        $ft .= isset($address['locality']) ? $address['locality'] : '';
        $ft .= ' (';
        $ft .= $i18n->_($type);
        
        if ($type == 'billing') {
            $ft .= ' - ' . $address['custom1'];
        }
        
        $ft .= ')';
        
        $address['fulltext'] = $ft;
        
        return $address;
    }
    
    /**
     * @todo make this better, faster
     *
     * @param array $resultSet
     *
     * @return array
     */
    public function resolveMultipleVirtualFields($resultSet)
    {
        foreach($resultSet as &$result) {
            $result = $this->resolveVirtualFields($result);
        }
    
        return $resultSet;
    }
}
