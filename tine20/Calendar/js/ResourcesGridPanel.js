/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.ResourcesGridPanel
 * @extends   Tine.Tinebase.widgets.app.GridPanel
 * Resources Grid Panel <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Calendar.ResourcesGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Calendar.Model.Resource,
    
    // grid specific
    defaultSortInfo: {field: 'name', dir: 'ASC'},
    
    // not yet
    evalGrants: false,
    
    newRecordIcon: 'cal-resource',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.gridConfig = {
        };
        
        this.gridConfig.columns = [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 150,
            sortable: true,
            dataIndex: 'name'
        }, {
            id: 'email',
            header: this.app.i18n._("Email"),
            width: 150,
            sortable: true,
            dataIndex: 'email'
        }, new Ext.ux.grid.CheckColumn({
            header: _('Location'),
            dataIndex: 'is_location',
            width: 55
        })];
        
        this.supr().initComponent.call(this);
    },
    
    initLayout: function() {
        this.supr().initLayout.call(this);
        
        this.items.push({
            region : 'north',
            border : false,
            items  : this.actionToolbar
        });
    }
});