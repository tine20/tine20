/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.persistentfilter.model');


/**
 * Model of a container
 */
Tine.widgets.persistentfilter.model.PersistentFilter = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'application_id'},
    {name: 'account_id'},
    {name: 'model'},
    {name: 'filters'},
    {name: 'name'},
    {name: 'description'}
]), {
    appName: 'Tinebase',
    modelName: 'PersistentFilter',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Favorite', 'Favorites', n); gettext('Events');
    recordName: 'Favorite',
    recordsName: 'Favorites',
    
    /**
     * is a shared persistent filter
     * 
     * @return {Boolean}
     */
    isShared: function() {
        return this.get('account_id') === null;
    },
    
    /**
     * is default of current user
     * 
     * @return {Boolean}
     */
    isDefault: function() {
        var app = Tine.appMgr.getById(this.get('application_id'));
        
        return this.app && this.get('id') === app.getRegistry().get('preferences').get('defaultpersistentfilter');
    }
});
