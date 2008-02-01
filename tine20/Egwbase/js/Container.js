/*
 * egroupware 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Egw.Egwbase.container');

/**
 * Egwbase container class
 * 
 * @todo add generic container model
 * @todo internal cache (store)
 */
Egw.Egwbase.container = {
    /**
     * constant for no grants
     */
    GRANT_NONE: 0,
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
     * constant for all grants
     */
    GRANT_ANY: 31,
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
    TYPE_SHARED: 'shared',
	
	/**
	 * Models of Egwbase container
	 * @property {Object}
	 */
	models: {
		containerGrant: Ext.data.Record.create([
            {name: 'accountId'},
            {name: 'accountName'}, // nasty namespace! need to be fixed when all apps use the grants widgets
            {name: 'readGrant'},
            {name: 'addGrant'},
            {name: 'editGrant'},
            {name: 'deleteGrant'}
        ])
	}
};