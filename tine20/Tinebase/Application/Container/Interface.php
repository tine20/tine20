<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 *  interface to handle container in each application controller
 *
 * any record in Tine 2.0 is tied to a container. the rights of an account on a record gets
 * calculated by the grants given to this account on the container holding the record (if you know what i mean ;-))
 *
 * @package     Tinebase
 * @subpackage  Container
 */
interface Tinebase_Application_Container_Interface
{
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_accountId the account object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId);
}
