<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncroton_Model_Policy extends Syncroton_Model_AEntry implements Syncroton_Model_IPolicy
{
    protected $_xmlBaseElement = 'EASProvisionDoc';
    
    protected $_properties = array(
        'Internal' => array(
            'id'                                   => array('type' => 'string'),
            'description'                          => array('type' => 'string'),
            'name'                                 => array('type' => 'string'),
            'policyKey'                            => array('type' => 'string'),
        ),
        'Provision' => array(
            'allowBluetooth'                       => array('type' => 'number'),
            'allowSMIMEEncryptionAlgorithmNegotiation' => array('type' => 'number'),
            'allowBrowser'                         => array('type' => 'number'),
            'allowCamera'                          => array('type' => 'number'),
            'allowConsumerEmail'                   => array('type' => 'number'),
            'allowDesktopSync'                     => array('type' => 'number'),
            'allowHTMLEmail'                       => array('type' => 'number'),
            'allowInternetSharing'                 => array('type' => 'number'),
            'allowIrDA'                            => array('type' => 'number'),
            'allowPOPIMAPEmail'                    => array('type' => 'number'),
            'allowRemoteDesktop'                   => array('type' => 'number'),
            'allowSimpleDevicePassword'            => array('type' => 'number'),
            'allowSMIMEEncryptionAlgorithmNegotiation' => array('type' => 'number'),
            'allowSMIMESoftCerts'                  => array('type' => 'number'),
            'allowStorageCard'                     => array('type' => 'number'),
            'allowTextMessaging'                   => array('type' => 'number'),
            'allowUnsignedApplications'            => array('type' => 'number'),
            'allowUnsignedInstallationPackages'    => array('type' => 'number'),
            'allowWifi'                            => array('type' => 'number'),
            'alphanumericDevicePasswordRequired'   => array('type' => 'number'),
            'approvedApplicationList'              => array('type' => 'container'),
            'attachmentsEnabled'                   => array('type' => 'number'),
            'devicePasswordEnabled'                => array('type' => 'number'),
            'devicePasswordExpiration'             => array('type' => 'number'),
            'devicePasswordHistory'                => array('type' => 'number'),
            'maxAttachmentSize'                    => array('type' => 'number'),
            'maxCalendarAgeFilter'                 => array('type' => 'number'),
            'maxDevicePasswordFailedAttempts'      => array('type' => 'number'),
            'maxEmailAgeFilter'                    => array('type' => 'number'),
            'maxEmailBodyTruncationSize'           => array('type' => 'number'),
            'maxEmailHTMLBodyTruncationSize'       => array('type' => 'number'),
            'maxInactivityTimeDeviceLock'          => array('type' => 'number'),
            'minDevicePasswordComplexCharacters'   => array('type' => 'number'),
            'minDevicePasswordLength'              => array('type' => 'number'),
            'passwordRecoveryEnabled'              => array('type' => 'number'),
            'requireDeviceEncryption'              => array('type' => 'number'),
            'requireEncryptedSMIMEMessages'        => array('type' => 'number'),
            'requireEncryptionSMIMEAlgorithm'      => array('type' => 'number'),
            'requireManualSyncWhenRoaming'         => array('type' => 'number'),
            'requireSignedSMIMEAlgorithm'          => array('type' => 'number'),
            'requireSignedSMIMEMessages'           => array('type' => 'number'),
            'requireStorageCardEncryption'         => array('type' => 'number'),
            'unapprovedInROMApplicationList'       => array('type' => 'container')
        )
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $this->_addXMLNamespaces($_domParrent);
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }
    
            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
            
            if ($nameSpace == 'Internal') {
                continue;
            }
    
            $nameSpace = 'uri:' . $nameSpace;
    
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
    
            switch($elementName) {
                case 'approvedApplicationList':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, ucfirst($elementName));
    
                    foreach($value as $hash) {
                        $hashElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Hash');
                        $hashElement->appendChild($_domParrent->ownerDocument->createTextNode($hash));
    
                        $element->appendChild($hashElement);
                    }
    
                    $_domParrent->appendChild($element);
    
                    break;
    
                case 'unapprovedInROMApplicationList':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, ucfirst($elementName));
    
                    foreach($value as $applications) {
                        $hashElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'ApplicationName');
                        $hashElement->appendChild($_domParrent->ownerDocument->createTextNode($hash));
    
                        $element->appendChild($hashElement);
                    }
    
                    $_domParrent->appendChild($element);
    
                    break;
    
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, ucfirst($elementName));
    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Y-m-d\TH:i:s.000\Z");
                    }
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
    
                    $_domParrent->appendChild($element);
            }
        }
    }
}

