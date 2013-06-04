<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Tinebase_Model_Filter_IntId
 * 
 * filters one or more integer ids. This class is needed only for the container model.
 * If the container class got converted to hash id's this class can be removed.
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @deprecated
 */
class Tinebase_Model_Filter_IntId extends Tinebase_Model_Filter_Id
{
    /**
     * enforce string data type for correct sql quoting
     */
    protected function _enforceType()
    {
        if (is_array($this->_value)) {
            foreach ($this->_value as &$value) {
                if (!ctype_digit($value)) {
                    throw new Tinebase_Exception_UnexpectedValue("$value is not a number");
                } 
                $value = (int) $value;
            }
        } else {
            if (!ctype_digit($this->_value)) {
                throw new Tinebase_Exception_UnexpectedValue("$this->_value is not a number");
            } 
            $this->_value = (int) $this->_value;
        }
    }
}
