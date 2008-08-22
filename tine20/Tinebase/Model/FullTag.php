<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * defines the datatype for one full (including all rights and contexts) tag
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Model_FullTag extends Tinebase_Model_Tag
{
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators['rights']   = array('allowEmpty' => true);
        $this->_validators['contexts'] = array('allowEmpty' => true);
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}