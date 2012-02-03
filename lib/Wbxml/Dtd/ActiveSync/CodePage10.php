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
 
class Wbxml_Dtd_ActiveSync_CodePage10 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 10;
    
    protected $_codePageName    = 'ResolveRecipients';
        
    protected $_tags = array(     
        'ResolveRecipients'       => 0x05,
        'Response'                => 0x06,
        'Status'                  => 0x07,
        'Type'                    => 0x08,
        'Recipient'               => 0x09,
        'DisplayName'             => 0x0a,
        'EmailAddress'            => 0x0b,
        'Certificates'            => 0x0c,
        'Certificate'             => 0x0d,
        'MiniCertificate'         => 0x0e,
        'Options'                 => 0x0f,
        'To'                      => 0x10,
        'CertificateRetrieval'    => 0x11,
        'RecipientCount'          => 0x12,
        'MaxCertificates'         => 0x13,
        'MaxAmbiguousRecipients'  => 0x14,
        'CertificateCount'        => 0x15
    );
}