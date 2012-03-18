<?php

/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncroton_Model_SyncState implements Syncroton_Model_ISyncState
{
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
}

