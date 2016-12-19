/**
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Sipgate');

/**
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.LineFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Sipgate.LineFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    ownField: 'line_id',
    linkType: 'foreignId',
    foreignRecordClass: Tine.Sipgate.Model.Line,
    filterName: 'LineFilter',
    ownRecordClass: Tine.Sipgate.Model.Connection,
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sipgate');
        this.label = this.app.i18n._('Line');
        this.pickerConfig = {allowBlank: true };

        Tine.Sipgate.LineFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['sipgate.line'] = Tine.Sipgate.LineFilterModel;