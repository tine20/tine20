/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         add 'one of / in' operator?
 */
Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListMemberFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Addressbook.ListMemberFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    field: 'list',
    defaultOperator: 'equals',

    /**
     * @private
     */
    initComponent: function() {
        Tine.Addressbook.ListMemberFilterModel.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        
        this.operators = ['equals'];
        this.label = this.app.i18n._('Member of List');
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            blurOnSelect: true,
            recordClass: this.field == 'list' ? Tine.Addressbook.Model.List : Tine.Addressbook.Model.Contact,
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('select', this.onFiltertrigger, this);
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['addressbook.listMember'] = Tine.Addressbook.ListMemberFilterModel;
