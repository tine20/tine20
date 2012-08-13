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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage10 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
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
        'CertificateCount'        => 0x15,
        'Availability'            => 0x16,
        'StartTime'               => 0x17,
        'EndTime'                 => 0x18,
        'MergedFreeBusy'          => 0x19,
        'Picture'                 => 0x1a,
        'MaxSize'                 => 0x1b,
        'Data'                    => 0x1c,
        'MaxPictures'             => 0x1d
    );
}