/**
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Sipgate');

Tine.Sipgate.AccountFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    /**
     * @cfg {Record} foreignRecordClass needed for explicit defined filters
     */
    foreignRecordClass : Tine.Sipgate.Model.Account,
    ownRecordClass: Tine.Sipgate.Model.Line,
    /**
     * @cfg {String} linkType {relation|foreignId} needed for explicit defined filters
     */
    linkType: 'foreignId',
    
    /**
     * @cfg {String} filterName server side filterGroup Name, needed for explicit defined filters
     */
    filterName: 'AccountFilter',
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: 'account_id',
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sipgate');
        this.label = this.app.i18n._('Account');
        
        this.pickerConfig = this.pickerConfig || {};
        Ext.applyIf(this.pickerConfig, {showClosed: true});
        
        Tine.Sipgate.AccountFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sipgate.account'] = Tine.Sipgate.AccountFilterModel;