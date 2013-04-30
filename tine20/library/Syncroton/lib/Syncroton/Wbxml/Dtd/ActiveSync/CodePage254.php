<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @link        http://msdn.microsoft.com/en-us/live/jj572363.aspx
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage254 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 254;
    
    protected $_codePageName    = 'WindowsLive';
        
    protected $_tags = array(
        'Annotations'            => 0x05,
        'Annotation'             => 0x06,
        'Name'                   => 0x07,
        'Value'                  => 0x08
    );
}