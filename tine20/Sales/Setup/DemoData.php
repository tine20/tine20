<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Sales initialization
 *
 * @package     Setup
 */
class Sales_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * The contract controller
     *
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;

    /**
     * required apps
     *
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'Addressbook');

    /**
     * The product controller
     *
     * @var Sales_Controller_Product
     */
    protected $_productController  = NULL;

    /**
     * the application name to work on
     *
     * @var string
     */
    protected $_appName = 'Sales';
    /**
     * models to work on
     * @var array
     */
    protected $_models = array('product', 'customer', 'contract', 'invoice', 'orderconfirmation', 'offer');

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_productController     = Sales_Controller_Product::getInstance();
        $this->_contractController    = Sales_Controller_Contract::getInstance();

        $this->_loadCostCentersAndDivisions();
    }

    /**
     * the singleton pattern
     *
     * @return Sales_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     *
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Sales_Controller_Contract::getInstance();

        $f = new Sales_Model_ContractFilter(array(
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created by Tine 2.0 DemoData'),
        ), 'AND');

        return ($c->search($f)->count() > 1) ? true : false;
    }

    /**
     * creates the products - no containers, just "shared"
     */
    protected function _createSharedProducts()
    {
        $l = Sales_Model_ProductLocalization::FLD_LANGUAGE;
        $t = Sales_Model_ProductLocalization::FLD_TEXT;

        $products = array(
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 100 MBit Ethernet Switch',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 100 mbit ethernet switch',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 100 MBit Ethernet Switch RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 100 mbit ethernet switch, standard plug',
                ]],
                'salesprice' => 28.13,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 100 mbit ethernet switch PoE',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port 100 MBit Ethernet Switch PoE',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 100 mbit ethernet switch, PoE, standard plug',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port Fast Ethernet Switch, PoE, RJ45',
                ]],
                'salesprice' => 1029.99,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port gigabit ethernet switch',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port 1 Gigabit ethernet switch',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 Port 1 Gigabit Ethernet Switch RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '10 port gigabit ethernet switch, standard plug',
                ]],
                'salesprice' => 78.87,
            ),
            array(
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port gigabit ethernet switch PoE',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port 1 Gigabit ethernet switch PoE',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 Port 1 Gigabit Ethernet Switch PoE RJ45 Stecker',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => '28 port gigabit ethernet switch, PoE standard plug',
                ]],
                'salesprice' => 3496.45,
            ),
        );

        $default = array(
            'manufacturer' => 'SwitchCo',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung'
        );

        foreach($products as $key => $product) {
            $switches[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }

        $products = array(
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 5a red'], [$l => 'de', $t => '10m Kat. 5a rot']],
                'description' => [[ $l => 'en', $t => '10m Cat. 5a red cable up to 100MBit.'], [$l => 'de', $t => '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.']],
                'salesprice' => 5.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 5a blue'], [$l => 'de', $t => '10m Kat. 5a blau']],
                'description' => [[ $l => 'en', $t => '10m Cat. 5a blue cable up to 100MBit.'], [$l => 'de', $t => '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.']],
                'salesprice' => 5.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 6 red'], [$l => 'de', $t => '10m Kat. 6 rot']],
                'description' => [[ $l => 'en', $t => '10m Cat. 6 red cable up to 1000MBit.'], [$l => 'de', $t => '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.']],
                'salesprice' => 9.99,
            ),
            array(
                'name' => [[ $l => 'en', $t => '10m Cat. 6 blue'], [$l => 'de', $t => '10m Kat. 6 blau']],
                'description' => [[ $l => 'en', $t => '10m Cat. 6 blue cable up to 1000MBit.'], [$l => 'de', $t => '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.']],
                'salesprice' => 9.99,
            ),
        );

        $default = array(
            'manufacturer' => self::$_en ? 'Salad Cabels' : 'Salat Kabel & Co.',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung'
        );

        $subProductIds = [];

        foreach($products as $key => $product) {
            $subProductIds[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }

        $products = [
            [
                Sales_Model_Product::FLD_SHORTCUT => 'cable-set',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => [[ $l => 'en', $t => 'Cable-set'], [$l => 'de', $t => 'Kabelsatz']],
                'description' => [[ $l => 'en', $t => 'colorful networkcable set'], [$l => 'de', $t => 'Farbiges Netzwerkkabelset']],
                'salesprice' => 38.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_SET,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ared',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ablue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[2],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '6red',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[3],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '6blue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ]
                ]
            ], [
                Sales_Model_Product::FLD_SHORTCUT => 'net-starter',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => [[ $l => 'en', $t => 'Net-Starter'], [$l => 'de', $t => 'Netzstarter']],
                'description' => [[ $l => 'en', $t => 'network starter bundle'], [$l => 'de', $t => 'Netzwerk Starter']],
                'salesprice' => 99.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $switches[$key],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '10sw',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ared',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => '5ablue',
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 5,
                    ]
                ]
            ]
        ];

        foreach($products as $product) {
            $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }

        $products = [
            [
                'name' => 'vCPU',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Dynamic costs',
                Sales_Model_Product::FLD_SHORTCUT => 'vcpu',
                Sales_Model_Product::FLD_IS_SALESPRODUCT => false,
                'description' => 'vCPU',
                'salesprice' => 9.99,
            ], [
                'name' => 'RAM in GB',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Dynamic costs',
                Sales_Model_Product::FLD_SHORTCUT => 'vram',
                Sales_Model_Product::FLD_IS_SALESPRODUCT => false,
                'description' => 'RAM in GB',
                'salesprice' => 9.99,
            ]
        ];

        $default = array(
            'manufacturer' => 'Hosting Giant Ltd',
            'category' => 'Hosting'
        );

        $subProductIds = [];

        foreach($products as $key => $product) {
            $product['name'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['name'],
            ]];
            $product['description'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['description'],
            ]];
            $subProductIds[$key] = $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)))->getId();
        }

        $products = [
            [
                Sales_Model_Product::FLD_SHORTCUT => 'vm-small',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Periocical costs',
                Sales_Model_Product::FLD_DEFAULT_SORTING => 50000,
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => self::$_en ? 'VM small' : 'VM klein',
                'description' => self::$_en ? 'small, general purpose VM with {vcpu.quantity} vCPUs and {vram.quantity}GB RAM' : 'kleine, general purpose VM mit {vcpu.quantity} vCPUs und {vram.quantity}GB RAM',
                'salesprice' => 9.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 2,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vcpu',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',

                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 4,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vram',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',
                    ]
                ]
            ], [
                Sales_Model_Product::FLD_SHORTCUT => 'vm-middle',
                Sales_Model_Product::FLD_DEFAULT_GROUPING => 'Periocical costs',
                Sales_Model_Product::FLD_UNIT => Sales_Model_Product::UNIT_PIECE,
                'name' => self::$_en ? 'VM middle' : 'VM mittel',
                'description' => self::$_en ? 'middle, general purpose VM with {vcpu.quantity} vCPUs and {vram.quantity}GB RAM' : 'mittlere, general purpose VM mit {vcpu.quantity} vCPUs und {vram.quantity}GB RAM',
                'salesprice' => 19.99,
                Sales_Model_Product::FLD_UNFOLD_TYPE => Sales_Model_Product::UNFOLD_TYPE_BUNDLE,
                Sales_Model_Product::FLD_SUBPRODUCTS => [
                    [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[0],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 4,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vcpu',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',

                    ], [
                        Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProductIds[1],
                        Sales_Model_SubProductMapping::FLD_QUANTITY => 8,
                        Sales_Model_SubProductMapping::FLD_SHORTCUT => 'vram',
                        Sales_Model_SubProductMapping::FLD_VARIABLE_POSITION_FLAG => 'SHARED',
                    ]
                ]
            ]
        ];

        $default = array(
            'manufacturer' => 'Hosting Giant Ltd',
            'category' => 'Hosting'
        );

        foreach($products as $product) {
            $product['name'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['name'],
            ]];
            $product['description'] = [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => $product['description'],
            ]];
            $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }
    }

    /**
     * creates the customers with some addresses getting from the addressbook
     */
    protected function _createSharedCustomers()
    {
        $pagination = new Tinebase_Model_Pagination(array('limit' => 6, 'sort' => 'id', 'dir' => 'ASC'));
        // @todo: use shared addresses only
        $filter = new Addressbook_Model_ContactFilter(array(array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_CONTACT)));
        $addresses = Addressbook_Controller_Contact::getInstance()->search($filter, $pagination);

        $customers = array(
            array(
                'name' => 'ELKO Elektronik und Söhne',
                'url' => 'www.elko-elektronik.de',
                'discount' => 0,
                'name_shorthand' => 'ELKO'
            ),
            array(
                'name' => 'Reifenlieferant Gebrüder Platt',
                'url' => 'www.platt-reifen.de',
                'discount' => 0,
                'name_shorthand' => 'PLATT'
            ),
            array(
                'name' => 'Frische Fische Gmbh & Co. KG',
                'url' => 'www.frische-fische-hamburg.de',
                'discount' => 15.2,
                'name_shorthand' => 'FrischeFische'
            ),
        );

        $i=0;

        $customerController = Sales_Controller_Customer::getInstance();
        $addressController = Sales_Controller_Address::getInstance();

        $customerRecords = new Tinebase_Record_RecordSet('Sales_Model_Customer');

        foreach ($customers as $customer) {
            $contactExtern = $addresses->getByIndex($i);
            if ($contactExtern) {
                $customer['cpextern_id'] = $contactExtern->getId();
            }
            $i++;
            $contactIntern = $addresses->getByIndex($i);
            if ($contactIntern) {
                $customer['cpintern_id'] = $contactIntern->getId();
            }
            $i++;
            $customer['iban'] = Tinebase_Record_Abstract::generateUID(20);
            $customer['bic'] = Tinebase_Record_Abstract::generateUID(12);
            $customer['credit_term'] = 30;
            $customer['currency'] = 'EUR';
            $customer['currency_trans_rate'] = 1;
            try {
                $customerRecords->addRecord($customerController->create(new Sales_Model_Customer($customer)));
            } catch (Tinebase_Exception_Duplicate $e) {
                echo 'Skipping creating customer ' . $customer['name'] . ' - exists already.' . PHP_EOL;
            }
        }

        $pagination = new Tinebase_Model_Pagination(array('limit' => 16, 'sort' => 'id', 'dir' => 'DESC'));
        $addresses = Addressbook_Controller_Contact::getInstance()->search($filter, $pagination);

        $i=0;
        foreach($customerRecords as $customer) {
            foreach(array('postal', 'billing', 'delivery', 'billing', 'delivery') as $type) {
                $caddress = $addresses->getByIndex($i);
                $address = new Sales_Model_Address(array(
                    'customer_id' => $customer->getId(),
                    'type'        => $type,
                    'prefix1'     => $caddress->title,
                    'prefix2'     => $caddress->n_fn,
                    'street'      => $caddress->adr_two_street,
                    'postalcode'  => $caddress->adr_two_postalcode,
                    'locality'    => $caddress->adr_two_locality,
                    'region'      => $caddress->adr_two_region,
                    'countryname' => $caddress->adr_two_countryname,
                    'custom1'     => ($type == 'billing') ? Tinebase_Record_Abstract::generateUID(5) : NULL
                ));

                $addressController->create($address);

                $i++;
            }
            // the last customer gets plus one delivery address
            $caddress = $addresses->getByIndex($i);
            $address = new Sales_Model_Address(array(
                'customer_id' => $customer->getId(),
                'type'        => $type,
                'prefix1'     => $caddress->title,
                'prefix2'     => $caddress->n_fn,
                'street'      => $caddress->adr_two_street,
                'postalcode'  => $caddress->adr_two_postalcode,
                'locality'    => $caddress->adr_two_locality,
                'region'      => $caddress->adr_two_region,
                'countryname' => $caddress->adr_two_countryname,
                'custom1'     => NULL
            ));

            $addressController->create($address);
        }

        if (static::$_createFullData) {
            $i=0;
            while ($i < 200) {
                $customerController->create(new Sales_Model_Customer(array('name' => Tinebase_Record_Abstract::generateUID())));
                $i++;
            }
        }
    }

    /**
     * creates the invoices - no containers, just "shared"
     */
    protected function _createSharedInvoices()
    {
        $sic = Sales_Controller_Invoice::getInstance();

        $now = new Tinebase_DateTime();
        $now->setTimezone(Tinebase_Core::getUserTimezone());
        $now->setDate($now->format('Y'), $now->format('m'), 1);
        $now->setTime(3,0,0);

        $date = clone $this->_referenceDate;

        while ($date < $now) {
            $sic->createAutoInvoices($date);
            $date->addMonth(1);
        }
    }

    /**
     * creates the contracts - no containers, just "shared"
     */
    protected function _createSharedContracts()
    {
        $cNumber = 1;

        $container = $this->_contractController->getSharedContractsContainer();
        $cid = $container->getId();
        $ccs = array($this->_developmentCostCenter, $this->_marketingCostCenter);

        $i = 0;

        $this->_setReferenceDate();

        $customers = Sales_Controller_Customer::getInstance()->getAll();
        $addresses = Sales_Controller_Address::getInstance()->getAll();

        $customersCount = $customers->count();
        $ccIndex = 0;


        $timeaccoountProduct = Sales_Controller_Product::getInstance()->create(new Sales_Model_Product([
            'name' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => 'Timetracker Product',
            ]],
            'description' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => self::$_locale,
                Sales_Model_ProductLocalization::FLD_TEXT => 'this is a generic timetracker used in demo data',
            ]],
            'salesprice' => 100,
            'accountable' => 'TimetrackerTimeaccount'
        ]));

        while ($i < $customersCount) {
            $costcenter = $ccs[$i%2];
            $i++;

            $customer = $customers->getByIndex($ccIndex);

            $address = $addresses->filter('customer_id', $customer->getId())->filter('type', 'billing')->getFirstRecord();
            $addressId = $address ? $address->getId() : NULL;

            $title = self::$_de ? ('Vertrag für KST ' . $costcenter->number . ' - ' . $costcenter->name) : ('Contract for costcenter ' . $costcenter->number . ' - ' . $costcenter->name) . ' ' . Tinebase_Record_Abstract::generateUID(3);
            $ccid = $costcenter->getId();

            $contract = new Sales_Model_Contract(array(
                'number'       => $cNumber,
                'title'        => $title,
                'description'  => 'Created by Tine 2.0 DemoData',
                'container_id' => $cid,
                'status'       => 'OPEN',
                'cleared'      => 'NOT_YET_CLEARED',
                'start_date'   => clone $this->_referenceDate,
                'billing_address_id' => $addressId
            ));

            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->search(new Timetracker_Model_TimeaccountFilter(array(
                array('field' => 'title', 'operator' => 'equals', 'value' => 'Test Timeaccount ' . $i))));
            $timeaccount = $timeaccount->getFirstRecord();
            if (!$timeaccount) {
                $timeaccount = new Timetracker_Model_Timeaccount();
                $timeaccount->title = 'Test Timeaccount ' . $i;
                $timeaccount->number = $i;
                $timeaccount->is_billable = true;
                $timeaccount->status = 'to bill';
                $timeaccount->price = 120;
                $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount);

                $timeaccountRelation = array(
                    'own_model'              => Sales_Model_Contract::class,
                    'own_backend'            => Tasks_Backend_Factory::SQL,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => Timetracker_Model_Timeaccount::class,
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $timeaccount->getId(),
                    'type'                   => 'TIME_ACCOUNT'
                );
            }


            for($ts = 0; $ts < 6; $ts++) {
                $timesheet = new Timetracker_Model_Timesheet();
                $timesheet->timeaccount_id = $timeaccount->getId();
                $timesheet->is_billable = true;
                $timesheet->description = $ts . ' - ' . $i . ' Test Task';
                $timesheet->account_id = Tinebase_Core::getUser()->getId();
                $timesheet->start_date = (clone $this->_referenceDate)->addDay($i);
                $timesheet->duration = 30;
                $timesheet->accounting_time = 30;
                Timetracker_Controller_Timesheet::getInstance()->create($timesheet);
            }

            $relations = array(
                array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tasks_Backend_Factory::SQL,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => Tinebase_Model_CostCenter::class,
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $ccid,
                    'type'                   => 'LEAD_COST_CENTER'
                ),
                array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tasks_Backend_Factory::SQL,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Sales_Model_Customer',
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $customer->getId(),
                    'type'                   => 'CUSTOMER'
                ),

            );

            if ($timeaccountRelation) {
                $relations[] = $timeaccountRelation;
            }

            $genericProduct = Sales_Controller_Product::getInstance()->create(new Sales_Model_Product([
                'name' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'Generic Product',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'Generisches Produkt',
                ]],
                'description' => [[
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'ein generisches produkt aus den demo daten',
                ], [
                    Sales_Model_ProductLocalization::FLD_LANGUAGE => 'de',
                    Sales_Model_ProductLocalization::FLD_TEXT => 'ein generisches produkt aus den demo daten',
                ]], 'salesprice' => 100,
            ]));

            $contract->products = [
                [
                    'product_id' => $genericProduct->getId(),
                    'quantity' => 1
                ],
                [
                    'product_id' => $timeaccoountProduct->getId(),
                    'quantity' => 1
                ]
            ];

            $contract->relations = $relations;

            $this->_contractController->create($contract);
            $cNumber++;
            $ccIndex++;
            if ($ccIndex == $customersCount) {
                $ccIndex = 0;
            }
        }
    }

    /**
     * creates some order confirmations
     */
    protected function _createSharedOrderconfirmations()
    {
        $i = 1;

        $this->_setReferenceDate();

        $contracts = Sales_Controller_Contract::getInstance()->getAll('number');

        // create for each contract a order confirmation
        foreach($contracts as $contract) {
            $relations = array(array(
                'own_model'              => 'Sales_Model_OrderConfirmation',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Contract',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $contract->getId(),
                'type'                   => 'CONTRACT'
            ));

            $oc = Sales_Controller_OrderConfirmation::getInstance()->create(new Sales_Model_OrderConfirmation(array(
                'number' => $i,
                'title'  => self::$_de ? ('Auftragsbestätigung für Vertrag ' . $contract->title) : ('Order Confirmation for Contract' . $contract->title),
                'description' => 'Created by Tine 2.0 DemoData',
                'relations' => $relations
            )));

            $i++;
        }
    }

    /**
     * creates some offers
     */
    protected function _createSharedOffers()
    {
        $i = 0;

        $this->_setReferenceDate();

        $customers          = Sales_Controller_Customer::getInstance()->getAll('number');
        $orderconfirmations = Sales_Controller_OrderConfirmation::getInstance()->getAll('number');

        foreach ($customers as $customer) {
            $oc = $orderconfirmations->getByIndex($i);
            $i++;
            $relations = array(array(
                'own_model'              => 'Sales_Model_Offer',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Customer',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $customer->getId(),
                'type'                   => 'OFFER'
            ), array(
                'own_model'              => 'Sales_Model_Offer',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_OrderConfirmation',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $oc->getId(),
                'type'                   => 'OFFER'
            ));
            Sales_Controller_Offer::getInstance()->create(new Sales_Model_Offer(array(
                'number' => $i,
                'title'  => self::$_de ? ('Angebot für Kunde ' . $customer->name) : ('Offer for Customer' . $customer->name),
                'description' => 'Created by Tine 2.0 DemoData',
                'relations' => $relations
            )));
        }
    }

    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {

    }

    /**
     * create some costcenters
     *
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $controller = Tinebase_Controller_CostCenter::getInstance();
        $this->_costCenters = new Tinebase_Record_RecordSet(Tinebase_Model_CostCenter::class);
        $ccs = (static::$_de)
        ? array('Management', 'Marketing', 'Entwicklung', 'Produktion', 'Verwaltung',     'Controlling')
        : array('Management', 'Marketing', 'Development', 'Production', 'Administration', 'Controlling')
        ;

        $id = 1;
        foreach($ccs as $title) {
            $cc = new Tinebase_Model_CostCenter(
                array('name' => $title, 'number' => $id)
            );
            try {
                $controller->create($cc);
            } catch (Zend_Db_Statement_Exception $e) {
            } catch (Tinebase_Exception_Duplicate $e) {
            }

            $id++;
        }

        $divisionsArray = (static::$_de)
        ? array('Management', 'EDV', 'Marketing', 'Public Relations', 'Produktion', 'Verwaltung')
        : array('Management', 'IT', 'Marketing', 'Public Relations', 'Production', 'Administration')
        ;

        foreach($divisionsArray as $divisionName) {
            try {
                HumanResources_Controller_Division::getInstance()->create(new HumanResources_Model_Division(array('title' => $divisionName)));
            } catch (Zend_Db_Statement_Exception $e) {
            } catch (Tinebase_Exception_Duplicate $e) {}
        }

        $this->_loadCostCentersAndDivisions();
    }
}
