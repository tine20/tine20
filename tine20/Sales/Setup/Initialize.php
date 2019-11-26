<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     Sales
 */
class Sales_Setup_Initialize extends Setup_Initialize
{
    /**
    * init favorites
    */
    protected function _initializeFavorites() {
        // Products
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'model'             => 'Sales_Model_ProductFilter',
        );
        
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Products", // _('My Products')
                'description'       => "Products created by me", // _('Products created by me')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Contracts", // _('My Contracts')
                'description'       => "Contracts created by me", // _('Contracts created by myself')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Customers
        $commonValues['model'] = 'Sales_Model_CustomerFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Customers", // _('All Customers')
                'description' => "All customer records", // _('All customer records')
                'filters'     => array(
                ),
            ))
        ));
        
        // Offers
        $commonValues['model'] = 'Sales_Model_OfferFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Offers", // _('All Offers')
                'description' => "All offer records", // _('All offer records')
                'filters'     => array(
                ),
            ))
        ));
        
        static::createDefaultFavoritesForSub20();

        static::createDefaultFavoritesForSub22();

        static::createDefaultFavoritesForSub24();

        static::createDefaultFavoritesForContracts();
    }
    
    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Sales_Scheduler_Task::addUpdateProductLifespanTask($scheduler);
    }

    /**
     * add more contract favorites
     */
    public static function createDefaultFavoritesForContracts()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Inactive Contracts", // _('Inactive Contracts')
                'description' => "Contracts that have already been terminated", // _('Contracts that have already been terminated')
                'filters' => [
                    ['field' => 'end_date', 'operator' => 'before', 'value' => Tinebase_Model_Filter_Date::DAY_THIS]
                ],
            ))
        ));
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Active Contracts", // _('Active Contracts')
                'description' => "Contracts that are still running", // _('Contracts that are still running')
                'filters' => [
                    ['condition' => 'OR', 'filters' => [
                        ['field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST],
                        ['field' => 'end_date', 'operator' => 'isnull', 'value' => null],
                    ]]
                ],
            ))
        ));
    }

    /**
     * creates default favorited for version 8.22 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub22()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_SupplierFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Suppliers",        // _('All Suppliers')
                'description' => "All supllier records", // _('All supllier records')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.24 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub24()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_PurchaseInvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Purchase Imvoices", // _('All Purchase Imvoices')
                'description' => "All purchase invoices", // _('All purchase invoices')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.20 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub20()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Products
        $commonValues['model'] = 'Sales_Model_ProductFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Products", // _('All Products')
                'description' => "All product records", // _('All product records')
                'filters' => array(),
            ))
        ));

        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Contracts", // _('All Contracts')
                'description' => "All contract records", // _('All contract records')
                'filters' => array(),
            ))
        ));

        // Invoices
        $commonValues['model'] = 'Sales_Model_InvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Invoices", // _('All Invoices')
                'description' => "All invoice records", // _('All invoice records')
                'filters' => array(),
            ))
        ));

        // CostCenters
        $commonValues['model'] = 'Sales_Model_CostCenterFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Cost Centers", // _('All Cost Centers')
                'description' => "All cost center records", // _('All costcenter records')
                'filters' => array(),
            ))
        ));

        // Divisions
        $commonValues['model'] = 'Sales_Model_DivisionFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Divisions", // _('All Divisions')
                'description' => "All division records", // _('All division records')
                'filters' => array(),
            ))
        ));

        // OrderConfirmations
        $commonValues['model'] = 'Sales_Model_OrderConfirmationFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Order Confirmations", // _('All Order Confirmations')
                'description' => "All order confirmation records", // _('All order confirmation records')
                'filters' => array(),
            ))
        ));
    }
}
