<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage24 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 24;
    
    protected $_codePageName    = 'RightsManagement';
        
    protected $_tags = array(     
        'RightsManagementSupport'            => 0x05,
        'RightsManagementTemplates'          => 0x06,
        'RightsManagementTemplate'           => 0x07,
        'RightsManagementLicense'            => 0x08,
        'ReplyAllowed'                       => 0x09,
        'EditAllowed'                        => 0x0a,
        'ReplyAllAllowed'                    => 0x0b,
        'ForwardAllowed'                     => 0x0c,
        'ModifyRecipientsAllowed'            => 0x0d,
        'ExtractAllowed'                     => 0x0e,
        'PrintAllowed'                       => 0x0f,
        'ExportAllowed'                      => 0x10,
        'ProgrammaticAccessAllowed'          => 0x11,
        'RMOwner'                            => 0x12,
        'ContentExpiryDate'                  => 0x13,
        'TemplateName'                       => 0x14,
        'TemplateID'                         => 0x15,
        'TemplateDescription'                => 0x16,
        'ContentOwner'                       => 0x17,
        'RemoveRightsManagementDistribution' => 0x18
    );
    
}