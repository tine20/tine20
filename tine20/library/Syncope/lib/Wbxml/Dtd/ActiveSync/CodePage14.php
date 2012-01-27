<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:AirSync.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @todo        add missing tags
 */
 
class Wbxml_Dtd_ActiveSync_CodePage14 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 14;
    
    protected $_codePageName    = 'Provision';
        
    protected $_tags = array(     
        'Provision'              => 0x05,
        'Policies'               => 0x06,
        'Policy'                 => 0x07,
        'PolicyType'             => 0x08,
        'PolicyKey'              => 0x09,
        'Data'                   => 0x0a,
        'Status'                 => 0x0b,
        'RemoteWipe'             => 0x0c,
        'EASProvisionDoc'        => 0x0d,
        'DevicePasswordEnabled'  => 0x0e 
    );
}