<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage5 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 5;
    
    protected $_codePageName    = 'Move';
        
    protected $_tags = array(     
        'Moves'                   => 0x05,
        'Move'                    => 0x06,
        'SrcMsgId'                => 0x07,
        'SrcFldId'                => 0x08,
        'DstFldId'                => 0x09,
        'Response'                => 0x0a,
        'Status'                  => 0x0b,
        'DstMsgId'                => 0x0c
    );
}