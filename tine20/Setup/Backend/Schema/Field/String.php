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


class Setup_Backend_Schema_Field_String extends Setup_Backend_Schema_Field_Abstract
{
    public function __construct($_fieldDefinition)
    {
        try {
            $xmlObject = new SimpleXMLElement($_fieldDefinition);
        } catch (Exception $e) {
            echo $e->getMessage(); 
            exit;
        }
        $temp = Setup_Backend_Schema_Field_Factory::factory('Xml', $xmlObject);
        
        foreach ($temp as $key => $val) {
            $this->$key = $val;
        }
    }
}