<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Syncml
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:DevInfo11.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  Syncml
 */
 
class Syncroton_Wbxml_Dtd_Syncml_DevInfo11 extends Syncroton_Wbxml_Dtd_Syncml_Abstract
{
    protected $_codePages = array(
        0x00 => array(
            'codePageNumber'=> 0x00,
            'dtdname'       => 'DevInf',
            'dpi'           => '-//SYNCML//DTD DevInf 1.1//EN',
            'url'           => 'http://www.syncml.org/docs/devinf_v11_20020215.dtd',
            'urn'           => 'syncml:devinf1.1',
            'tags'          => array(     
                "CTCap"             => 0x05, 
                "CTType"            => 0x06, 
                "DataStore"         => 0x07, 
                "DataType"          => 0x08, 
                "DevID"             => 0x09, 
                "DevInf"            => 0x0a, 
                "DevTyp"            => 0x0b, 
                "DisplayName"       => 0x0c, 
                "DSMem"             => 0x0d, 
                "Ext"               => 0x0e, 
                "FwV"               => 0x0f, 
                "HwV"               => 0x10, 
                "Man"               => 0x11, 
                "MaxGUIDSize"       => 0x12, 
                "MaxID"             => 0x13, 
                "MaxMem"            => 0x14, 
                "Mod"               => 0x15, 
                "OEM"               => 0x16, 
                "ParamName"         => 0x17, 
                "PropName"          => 0x18, 
                "Rx"                => 0x19, 
                "Rx-Pref"           => 0x1a, 
                "SharedMem"         => 0x1b, 
                "Size"              => 0x1c, 
                "SourceRef"         => 0x1d, 
                "SwV"               => 0x1e, 
                "SyncCap"           => 0x1f, 
                "SyncType"          => 0x20, 
                "Tx"                => 0x21, 
                "Tx-Pref"           => 0x22, 
                "ValEnum"           => 0x23, 
                "VerCT"             => 0x24, 
                "VerDTD"            => 0x25, 
                "XNam"              => 0x26, 
                "XVal"              => 0x27, 
                "UTC"               => 0x28, 
                "SupportNumberOfChanges"=> 0x29, 
                "SupportLargeObjs"  => 0x2a 
            )
        )
    );
}