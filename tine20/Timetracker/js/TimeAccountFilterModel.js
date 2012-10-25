/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Timetracker');

Tine.Timetracker.TimeAccountFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    /**
     * @cfg {Record} foreignRecordClass needed for explicit defined filters
     */
    foreignRecordClass : Tine.Timetracker.Model.Timeaccount,
    
    /**
     * @cfg {String} linkType {relation|foreignId} needed for explicit defined filters
     */
    linkType: 'foreignId',
    
    /**
     * @cfg {String} filterName server side filterGroup Name, needed for explicit defined filters
     */
    filterName: 'TimeaccountFilter',
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: 'timeaccount_id',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.app.i18n.n_hidden(this.foreignRecordClass.getMeta('recordName'), this.foreignRecordClass.getMeta('recordsName'), 1);
        
        this.pickerConfig = this.pickerConfig || {};
        
        Tine.Timetracker.TimeAccountFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccount'] = Tine.Timetracker.TimeAccountFilterModel;