<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Generic implementation of Tinebase_Record_Abstract
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Generic extends Tinebase_Record_Abstract
{
    protected $_identifier = 'id';

    public function setValidators(array $validators)
    {
        $this->_validators = $validators;
    }
}