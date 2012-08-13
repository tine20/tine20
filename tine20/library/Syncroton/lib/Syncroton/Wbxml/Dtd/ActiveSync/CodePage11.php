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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage11 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
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