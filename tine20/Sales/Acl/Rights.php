<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * this class handles the rights for the crm application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 * 
 * TODO: make the manage_ right generic for each model
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Sales_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage products
     * @staticvar string
     */
    const MANAGE_PRODUCTS = 'manage_products';
    
    /**
     * the right to manage contracts
     * @staticvar string
     */
    const MANAGE_CONTRACTS = 'manage_contracts';
    
    /**
     * the right to manage cost centers
     * @staticvar string
     */
    const MANAGE_COSTCENTERS = 'manage_costcenters';
    
    /**
     * the right to manage customers
     * 
     * @staticvar string
     */
    const MANAGE_CUSTOMERS = 'manage_customers';
    
    /**
     * the right to manage offers
     *
     * @staticvar string
     */
    const MANAGE_OFFERS = 'manage_offers';
    
    /**
     * the right to manage order confirmations
     *
     * @staticvar string
     */
    const MANAGE_ORDERCONFIRMATIONS = 'manage_orderconfirmations';
    
    /**
     * the right to change the number of order confirmations after creating
     *
     * @staticvar string
     */
    const CHANGE_OC_NUMBER = 'change_oc_number';
    
    /**
     * the right to change or set the number of invoices
     *
     * @staticvar string
     */
    const SET_INVOICE_NUMBER = 'set_invoice_number';
    
    /**
     * the right to manage divisions
     *
     * @staticvar string
     */
    const MANAGE_DIVISIONS = 'manage_divisions';
    
    /**
     * the right to manage invoices
     * 
     * @staticvar string
     */
    const MANAGE_INVOICES = 'manage_invoices';
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Sales_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        
        $allRights = parent::getAllApplicationRights();
        
        $addRights = array ( 
            self::MANAGE_PRODUCTS,
            self::MANAGE_CONTRACTS,
            self::MANAGE_COSTCENTERS,
            self::MANAGE_CUSTOMERS,
            self::MANAGE_INVOICES,
            self::MANAGE_DIVISIONS,
            self::MANAGE_ORDERCONFIRMATIONS,
            self::MANAGE_OFFERS,
            self::CHANGE_OC_NUMBER,
            self::SET_INVOICE_NUMBER,
        );
        
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Sales');
        
        $rightDescriptions = array(
            self::MANAGE_PRODUCTS => array(
                'text'          => $translate->_('manage products'),
                'description'   => $translate->_('add, edit and delete products'),
            ),
            self::MANAGE_CONTRACTS => array(
                'text'          => $translate->_('manage contracts'),
                'description'   => $translate->_('add, edit and delete contracts'),
            ),
            self::MANAGE_COSTCENTERS => array(
                'text'          => $translate->_('manage cost centers'),
                'description'   => $translate->_('add, edit and delete cost centers'),
            ),
            self::MANAGE_CUSTOMERS => array(
                'text'          => $translate->_('manage customers'),
                'description'   => $translate->_('add, edit and delete customers'),
            ),
            self::MANAGE_INVOICES => array(
                'text'          => $translate->_('manage invoices'),
                'description'   => $translate->_('add, edit and delete invoices'),
            ),
            self::MANAGE_DIVISIONS => array(
                'text'          => $translate->_('manage divisions'),
                'description'   => $translate->_('add, edit and delete divisions'),
            ),
            self::MANAGE_ORDERCONFIRMATIONS => array(
                'text'          => $translate->_('manage order confirmations'),
                'description'   => $translate->_('add, edit and delete order confirmations'),
            ),
            self::MANAGE_OFFERS => array(
                'text'          => $translate->_('manage offers'),
                'description'   => $translate->_('add, edit and delete offers'),
            ),
            self::CHANGE_OC_NUMBER => array(
                'text'          => $translate->_('change number of an order confirmations'),
                'description'   => $translate->_('allow to change the number of an order confirmation on update'),
            ),
            self::SET_INVOICE_NUMBER => array(
                'text'          => $translate->_('set number of invoices'),
                'description'   => $translate->_('allow to set the number of an invoice'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
