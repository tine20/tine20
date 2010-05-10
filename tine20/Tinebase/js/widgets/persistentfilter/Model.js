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
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.model.PersistentFilter
 * @extends     Tine.Tinebase.data.Record
 * 
 * <p>Model of a Persistent Filter</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} data
 * @constructor
 * Create a new Tine.widgets.persistentfilter.model.PersistentFilter Record
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
        var app = Tine.Tinebase.appMgr.getById(this.get('application_id'));
        
        return this.app && this.get('id') === app.getRegistry().get('preferences').get('defaultpersistentfilter');
    }
});

/**
 * @namespace   Tine.widgets.persistentfilter
 * 
 * @param       {String} appName
 * @return      {model.PersistentFilter} or null
 */
Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite = function(appName) {
    var app = Tine.Tinebase.appMgr.get(appName),
        appPrefs = app.getRegistry().get('preferences'),
        defaultFavoriteId = appPrefs ? appPrefs.get('defaultpersistentfilter') : null;
   
    return defaultFavoriteId ? Tine.widgets.persistentfilter.store.getPersistentFilterStore().getById(defaultFavoriteId) : null
};

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.Tinebase.data.RecordProxy
 * @singelton   Tine.widgets.persistentfilter.model.persistentFilterProxy
 * 
 * <p>Backend for Persistent Filter</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 */
Tine.widgets.persistentfilter.model.persistentFilterProxy = new Tine.Tinebase.data.RecordProxy({
    appName: 'Tinebase_PersistentFilter',
    modelName: 'PersistentFilter',
    recordClass: Tine.widgets.persistentfilter.model.PersistentFilter
});