/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Tinebase.container');

/**
 * Tinebase container class
 * 
 * @todo add generic container model
 * @todo internal cache (store)
 */
Tine.Tinebase.container = {
    /**
     * constant for read grant
     */
    GRANT_READ: 1,
    /**
     * constant for add grant
     */
    GRANT_ADD: 2,
    /**
     * constant for edit grant
     */
    GRANT_EDIT: 4,
    /**
     * constant for delete grant
     */
    GRANT_DELETE: 8,
    /**
     * constant for admin grant
     */
    GRANT_ADMIN: 16,
    /** 
     * type for internal contaier
     * for example the internal addressbook
     */
    TYPE_INTERNAL: 'internal',
    /**
     * type for personal containers
     */
    TYPE_PERSONAL: 'personal',
    /**
     * type for shared container
     */
    TYPE_SHARED: 'shared'
};