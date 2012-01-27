<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncope_Model_Device implements Syncope_Model_IDevice
{
    const TYPE_IPHONE  = 'iphone';
    const TYPE_WEBOS   = 'webos';
    const TYPE_ANDROID = 'android';
    
    public function __construct(array $_data = array())
    {
        $this->setFromArray($_data);
    }
    
    public function setFromArray(array $_data)
    {
        foreach($_data as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Returns major firmware version of this device
     * 
     * @return int/string
     */
    public function getMajorVersion()
    {
        switch ($this->devicetype) {
            case Syncope_Model_Device::TYPE_IPHONE:
                if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->useragent, $matches)) {
                    list(, $name, $majorVersion, $minorVersion) = $matches;
                    return $majorVersion;
                }
                break;
            default:
                break;
        }
        
        return 0;
    }
}

