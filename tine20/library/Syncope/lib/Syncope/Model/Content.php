<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * model for content sent to device
 *
 * @package     Syncope
 * @subpackage  Model
 */
class Syncope_Model_Content implements Syncope_Model_IContent
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

