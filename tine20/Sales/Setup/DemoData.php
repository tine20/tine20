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
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName         = 'Sales';

    /**
     * models to work on
     * @var unknown_type
     */
    protected $_models = array('product', 'contract');
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Sales_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Setup_DemoData;
        }

        return self::$_instance;
    }
    
//    /**
//     * create some costcenters
//     * 
//     * @see Tinebase_Setup_DemoData_Abstract
//     */
//    protected function _onCreate()
//    {
//    }
    
    protected function _createSharedProducts()
    {
//        $controller = Sales_Controller_Contract::getInstance();
//        
//        $products = array(
//            array(
//                'name' => 'Abo 12/Jahr - "Haus und Tier"', 
//                'description' => '12 Ausgaben der Zeitschrift "Haus und Tier"' . PHP_EOL . 'Erscheint vor dem ersten Dienstag des Monats.',
//                'manufacturer' => '',
//                'category' => 'Lifestyle Abo'
//            ),
//            array(
//                'name' => 'Abo 4/Jahr - "Wald und Wiesen"',
//                'description' => '',
//                'manufacturer' => '-',
//                'category' => 'Natur Abo'
//            ),
//            array(
//                'name' => 'Jahresausgabe 2012 - "Jäger und Sammler"',
//                'description' => '',
//                'manufacturer' => '-',
//                'category' => 'Natur'
//            ),
//            array(
//                'name' => 'Abo Arbeitstag - "Eden - Medway - Merkur"',
//                'description' => '',
//                'manufacturer' => '-',
//                'category' => 'Tageszeitung'
//            ),
//            array(
//                'name' => 'Jubiläumsausgabe - "100 Jahre Eisenbahn"',
//                'description' => '',
//                'manufacturer' => '-',
//                'category' => 'Technik'
//            ),
//            array(
//                'name' => 'Zeitschrift "Eden - Medway - Merkur"',
//                'description' => 'Tageszeitung für das Eden-Medway Gebiet.',
//                'manufacturer' => '-',
//                'category' => 'Tageszeitung Abo'
//            ),
//            array(
//                'name' => 'Abo 12/Jahr - "Haus und Tier"',
//                'description' => '12 Ausgaben der Zeitschrift "Haus und Tier"' . PHP_EOL . 'Erscheint jeden ersten Dienstag des Monats. ' . PHP_EOL . 'Abonnementen mit einem Jahresabo erhalten die Ausgabe bereits einen Tag zuvor.',
//                'manufacturer' => '-',
//                'category' => 'Lifestyle'
//            ),
//            array(
//                'name' => 'Neue Ansichten',
//                'description' => 'Politisches Magazin für optimistische Zeitgenossen. ' . PHP_EOL . 'Erscheint am 1. des Monats.',
//                'manufacturer' => 'Politikatoriat',
//                'category' => 'Politik'),
//            );
//        foreach ($products as $product) {
//            $this->_createProduct($product);
//        }
    }
    
    /**
     * creates the contracts - no containers, just "shared"
     */
    protected function _createSharedContracts()
    {
//        $controller = Sales_Controller_Contract::getInstance();
//        
//        $this->_createContract(
//            array('manager' => array('user' => 'pwulf'), 'status' => 'CLOSED', 'cleared_in' => '2012-11 NKT-45')
//        );
    }
    
//    /**
//     * returns a new contract
//     * return Sales_Model_Contract
//     */
//    protected function _createContract($data)
//    {
//        $relations = array_key_exists('relations', $data) ? $data['relations'] : array();
//        if (array_key_exists('relations', $data)
//    }
    
    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {
        
        
    }
}
