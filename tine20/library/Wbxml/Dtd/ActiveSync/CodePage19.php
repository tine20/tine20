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
 */
 
class Wbxml_Dtd_ActiveSync_CodePage19 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 19;
    
    protected $_codePageName    = 'DocumentLibrary';
        
    protected $_tags = array(     
        'LinkId'                => 0x05,
        'DisplayName'           => 0x06,
        'IsFolder'              => 0x07,
        'CreationDate'          => 0x08,
        'LastModifiedDate'      => 0x09,
        'IsHidden'              => 0x0a,
        'ContentLength'         => 0x0b,
        'ContentType'           => 0x0c
    );
}