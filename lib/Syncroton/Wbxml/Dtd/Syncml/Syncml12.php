<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  Syncml
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:Syncml12.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  Syncml
 */
 
class Syncroton_Wbxml_Dtd_Syncml_Syncml12 extends Syncroton_Wbxml_Dtd_Syncml_Abstract
{     
          
    /**
     * section 8.2
     *
     * @var array
     */
    protected $_codePages = array(
        0x00 => array(
            'codePageNumber'=> 0x00,
            'dtdname'       => 'SyncML',
            'dpi'           => '-//SYNCML//DTD SyncML 1.2//EN',
            'url'           => 'http://www.openmobilealliance.org/tech/DTD/OMA-TS-SyncML_RepPro_DTD-V1_2.dtd',
            'urn'           => 'SYNCML:SYNCML1.2',
            'tags'          => array(     
                'Add'           => 0x05,
                'Alert'         => 0x06,
                'Archive'       => 0x07,
                'Atomic'        => 0x08,
                'Chal'          => 0x09,
                'Cmd'           => 0x0a,
                'CmdID'         => 0x0b,
                'CmdRef'        => 0x0c,
                'Copy'          => 0x0d,
                'Cred'          => 0x0e,
                'Data'          => 0x0f,
                'Delete'        => 0x10,
                'Exec'          => 0x11,
                'Final'         => 0x12,
                'Get'           => 0x13,
                'Item'          => 0x14,
                'Lang'          => 0x15,
                'LocName'       => 0x16,
                'LocURI'        => 0x17,
                'Map'           => 0x18,
                'MapItem'       => 0x19,
                'Meta'          => 0x1a,
                'MsgID'         => 0x1b,
                'MsgRef'        => 0x1c,
                'NoResp'        => 0x1d,
                'NoResults'     => 0x1e,
                'Put'           => 0x1f,
                'Replace'       => 0x20,
                'RespURI'       => 0x21,
                'Results'       => 0x22,
                'Search'        => 0x23,
                'Sequence'      => 0x24,
                'SessionID'     => 0x25,
                'SftDel'        => 0x26,
                'Source'        => 0x27,
                'SourceRef'     => 0x28,
                'Status'        => 0x29,
                'Sync'          => 0x2a,
                'SyncBody'      => 0x2b,
                'SyncHdr'       => 0x2c,
                'SyncML'        => 0x2d,
                'Target'        => 0x2e,
                'TargetRef'     => 0x2f,
                'Reserved for future use.' => 0x30,
                'VerDTD'        => 0x31,
                'VerProto'      => 0x32,
                'NumberOfChanges' =>  0x33,
                'MoreData'      =>  0x34,
                'Field'         =>  0x35,
                'Filter'        =>  0x36,
                'Record'        =>  0x37,
                'FilterType'    =>  0x38,
                'SourceParent'  =>  0x39,
                'TargetParent'  =>  0x3a,
                'Move'          =>  0x3b,
                'Correlator'    =>  0x3c
            )
        ),
        0x01 => array(
            'codePageNumber'=> 0x01,
            'dtdname'       => 'MetInf',
            'dpi'           => '-//OMA//DTD SYNCML-METINF 1.2//EN',
            'url'           => 'http://www.openmobilealliance.org/tech/DTD/OMA-TS-SyncML_MetaInfo_DTD-V1_2.dtd',
            'urn'           => 'syncml:metinf1.2',
            'tags'          => array(     
                'Anchor'       => 0x05,
                'EMI'          => 0x06,
                'Format'       => 0x07,
                'FreeID'       => 0x08,
                'FreeMem'      => 0x09,
                'Last'         => 0x0a,
                'Mark'         => 0x0b,
                'MaxMsgSize'   => 0x0c,
                'Mem'          => 0x0d,
                'MetInf'       => 0x0e,
                'Next'         => 0x0f,
                'NextNonce'    => 0x10,
                'SharedMem'    => 0x11,
                'Size'         => 0x12,
                'Type'         => 0x13,
                'Version'      => 0x14,
                'MaxObjSize'   => 0x15,
                'FieldLevel'   => 0x16
            )
        ) 
    );
}