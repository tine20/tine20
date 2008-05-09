<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: String.php 1703 2008-04-03 18:16:32Z lkneschke $
 */

 
class Setup_Backend_Schema_Index_String extends Setup_Backend_Schema_Index_Abstract
{
    public function __construct($_definition)
    {
        try {
            $xmlObject = new SimpleXMLElement($_definition);
        } catch (Exception $e) {
            echo $e->getMessage(); 
            exit;
        }
        $temp = Setup_Backend_Schema_Index_Factory::factory('Xml', $xmlObject);
        
        foreach ($temp as $key => $val) {
            $this->$key = $val;
        }
    }
    protected function _setIndex($_declaration) {}
}