<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Factory.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  Wbxml
 */

class Syncroton_Wbxml_Dtd_Factory
{
    const ACTIVESYNC='AirSync';
    
    const SYNCML='SyncML';
    
    /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return Addressbook_Backend_Interface
     */
    static public function factory ($_type)
    {
        switch ($_type) {
            case self::ACTIVESYNC:
                $instance = new Syncroton_Wbxml_Dtd_ActiveSync();
                break;
                
            case self::SYNCML:
                $instance = new Syncroton_Wbxml_Dtd_Syncml();
                break;
                                
            default:
                throw new Syncroton_Wbxml_Exception('unsupported DTD: ' . $_type);
                break;
        }
        return $instance;
    }
}    
