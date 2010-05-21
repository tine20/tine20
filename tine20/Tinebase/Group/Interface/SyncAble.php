<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * syncable group backend interface
 *
 * @package     Tinebase
 * @subpackage  Group
 */
interface Tinebase_Group_Interface_SyncAble
{
    /**
     * resolve groupid(for example ldap gidnumber) to uuid(for example ldap entryuuid)
     *
     * @param   string  $_groupId
     * @return  string  the uuid for groupid
     */
    public function resolveSyncAbleGidToUUid($_groupId);
}
