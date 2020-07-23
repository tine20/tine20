<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * the right to manage suppliers
     * 
     * @staticvar string
     */
    const MANAGE_SUPPLIERS = 'manage_suppliers';
    
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
     * the right to manage purchase invoices
     * 
     * @staticvar string
     */
    const MANAGE_PURCHASE_INVOICES = 'manage_purchase_invoices';
    
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
            Tinebase_Acl_Rights::USE_PERSONAL_TAGS,
            self::MANAGE_PRODUCTS,
            self::MANAGE_CONTRACTS,
            self::MANAGE_COSTCENTERS,
            self::MANAGE_CUSTOMERS,
            self::MANAGE_DIVISIONS,
        );
        
        // add rights dependent on feature switches
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $addRights[] = self::MANAGE_INVOICES;
            $addRights[] = self::SET_INVOICE_NUMBER;
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_SUPPLIERS_MODULE)) {
            $addRights[] = self::MANAGE_SUPPLIERS;
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_PURCHASE_INVOICES_MODULE)) {
            $addRights[] = self::MANAGE_PURCHASE_INVOICES;
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_OFFERS_MODULE)) {
            $addRights[] = self::MANAGE_OFFERS;
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_ORDERCONFIRMATIONS_MODULE)) {
            $addRights[] = self::MANAGE_ORDERCONFIRMATIONS;
            $addRights[] = self::CHANGE_OC_NUMBER;
        }
        
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
                'text'          => $translate->_('Manage Products'),
                'description'   => $translate->_('Add, edit and delete products.'),
            ),
            self::MANAGE_CONTRACTS => array(
                'text'          => $translate->_('Manage Contracts'),
                'description'   => $translate->_('Add, edit and delete contracts.'),
            ),
            self::MANAGE_COSTCENTERS => array(
                'text'          => $translate->_('Manage Cost Centers'),
                'description'   => $translate->_('Add, edit and delete cost centers.'),
            ),
            self::MANAGE_CUSTOMERS => array(
                'text'          => $translate->_('Manage Customers'),
                'description'   => $translate->_('Add, edit and delete customers.'),
            ),
            self::MANAGE_SUPPLIERS => array(
                'text'          => $translate->_('Manage Suppliers'),
                'description'   => $translate->_('Add, edit and delete suppliers.'),
            ),
            self::MANAGE_INVOICES => array(
                'text'          => $translate->_('manage invoices'),
                'description'   => $translate->_('Add, edit and delete invoices.'),
            ),
            self::MANAGE_DIVISIONS => array(
                'text'          => $translate->_('Manage Divisions'),
                'description'   => $translate->_('Add, edit and delete divisions.'),
            ),
            self::MANAGE_ORDERCONFIRMATIONS => array(
                'text'          => $translate->_('Manage Order Confirmations'),
                'description'   => $translate->_('Add, edit and delete order confirmations.'),
            ),
            self::MANAGE_OFFERS => array(
                'text'          => $translate->_('Manage Offers'),
                'description'   => $translate->_('Add, edit and delete offers.'),
            ),
            self::MANAGE_PURCHASE_INVOICES => array(
                'text'          => $translate->_('Manage Purchase Invoices'),
                'description'   => $translate->_('Add, edit and delete purchase invoices.'),
            ),
            self::CHANGE_OC_NUMBER => array(
                'text'          => $translate->_('Change number of an order confirmations'),
                'description'   => $translate->_('Allow changing the number of an order confirmation during the update.'),
            ),
            self::SET_INVOICE_NUMBER => array(
                'text'          => $translate->_('Set number of invoices'),
                'description'   => $translate->_('Allow to set the number of an invoice.'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
