<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 *  interface to handle containers in each application controller or webdav frontends
 *
 * any record in Tine 2.0 is tied to a container. the rights of an account on a record gets
 * calculated by the grants given to this account on the container holding the record (if you know what i mean ;-))
 *
 * @package     Tinebase
 * @subpackage  Container
 *
 * TODO add interface for container models (Tinebase_Model_Container, Tinebase_Model_Tree_Node, ...)
 */
interface Tinebase_Container_Interface
{
    /**
     * check if the given user user has a certain grant
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Record_Interface        $_containerId
     * @param   array|string                        $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant);

    /**
     * return users which made personal containers accessible to given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Model_Application   $recordClass
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @param   bool                                $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_User
     */
    public function getOtherUsers($_accountId, $recordClass, $_grant, $_ignoreACL = FALSE, $_andGrants = FALSE);

    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Record_Interface    $recordClass
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @param   bool                                $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getSharedContainer($_accountId, $recordClass, $_grant, $_ignoreACL = FALSE, $_andGrants = FALSE);

    /**
     * returns the personal container of a given account accessible by a another given account
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   string|Tinebase_Record_Interface    $_recordClass
     * @param   int|Tinebase_Model_User             $_owner
     * @param   array|string                        $_grant
     * @param   bool                                $_ignoreACL
     * @return  Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getPersonalContainer($_accountId, $_recordClass, $_owner, $_grant = Tinebase_Model_Grants::GRANT_READ, $_ignoreACL = false);

    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     *
     * @param   string|Tinebase_Model_User          $accountId
     * @param   string|Tinebase_Model_Application   $recordClass
     * @param   array|string                        $grant
     * @param   bool                                $onlyIds return only ids
     * @param   bool                                $ignoreACL
     * @return  Tinebase_Record_RecordSet|array
     * @throws  Tinebase_Exception_NotFound
     */
    public function getContainerByACL($accountId, $recordClass, $grant, $onlyIds = FALSE, $ignoreACL = FALSE);

    /**
     * gets default container of given user for given app
     *  - did and still does return personal first container by using the application name instead of the recordClass name
     *  - allows now to use different models with default container in one application
     *
     * @param   string|Tinebase_Record_Interface $recordClass
     * @param   string|Tinebase_Model_User       $accountId use current user if omitted
     * @param   string                           $defaultContainerPreferenceName
     * @return  Tinebase_Record_Interface
     */
    public function getDefaultContainer($recordClass, $accountId = NULL, $defaultContainerPreferenceName = NULL);
}
