/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Addressbook.ContactFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Addressbook.ContactFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    field: 'contact_id',
    defaultOperator: 'equals',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Addressbook.ContactFilterModel.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        
        this.operators = ['equals'/*, 'notin'*/];
        this.label = this.label || this.app.i18n._('Contact');
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Addressbook.SearchCombo({
            app: this.app,
            filter: filter,
            listWidth: 400,
            listAlign : 'tr-br?',
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            getValue: function() {
                return this.selectedRecord.id;
            },
            onSelect: function(record) {
                this.setValue(record);
                this.collapse();
        
                this.fireEvent('select', this, record);
                if (this.blurOnSelect) {
                    this.fireEvent('blur', this);
                }
            },
            setValue: function(value) {
                this.selectedRecord = value;
                var displayValue = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderUserName.call(this, value);
                Tine.Addressbook.SearchCombo.superclass.setValue.call(this, displayValue);
            }
        });
        value.on('select', this.onFiltertrigger, this);
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['addressbook.contact'] = Tine.Addressbook.ContactFilterModel;

