<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Abstract
 * 
 * acl filter interface
 * 
 * A ACL filter constrict the results of a filter group based on the required
 * grants needed by the current user.
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
interface Tinebase_Model_Filter_AclFilter
{
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants);

}
