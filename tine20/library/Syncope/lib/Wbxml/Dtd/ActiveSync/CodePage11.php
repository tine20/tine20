<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
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
 
class Wbxml_Dtd_ActiveSync_CodePage11 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 11;
    
    protected $_codePageName    = 'ValidateCert';
        
    protected $_tags = array(     
        'ValidateCert'            => 0x05,
        'Certificates'            => 0x06,
        'Certificate'             => 0x07,
        'CertificateChain'        => 0x08,
        'CheckCRL'                => 0x09,
        'Status'                  => 0x0a
    );
}