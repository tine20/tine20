/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * key field combo
 * 
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @class       Tine.Tinebase.widgets.keyfield.ComboBox
 * @extends     Ext.form.ComboBox
 */
Tine.Tinebase.widgets.keyfield.ComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {String/Application} app
     */
    app: null,
    
    /**
     * @cfg {String} keyFieldName 
     * name of key field
     */
    keyFieldName: null,
    
    /**
     * @cfg {Boolean} showIcon
     * show icon in list and value
     */
    showIcon: true,
    
    /**
     * sort by a field
     * 
     * @cfg {String} sortBy
     */
    sortBy: null,
    
    /* begin config */
    blurOnSelect  : true,
    expandOnFocus : true,
    mode          : 'local',
    displayField  : 'i18nValue',
    valueField    : 'id',
    /* end config */
    
    initComponent: function() {
        this.app = Ext.isString(this.app) ? Tine.Tinebase.appMgr.get(this.app) : this.app;
    
        // get keyField config
        this.keyFieldConfig = this.app.getRegistry().get('config')[this.keyFieldName];
    
        if (! this.value && (this.keyFieldConfig && Ext.isObject(this.keyFieldConfig.value) && this.keyFieldConfig.value.hasOwnProperty('default'))) {
            this.value = this.keyFieldConfig.value['default'];
            //@todo see if keyFieldConfig.definition has a default
        }
        
        this.store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app, this.keyFieldName);
        if (this.sortBy) {
            this.store.sort(this.sortBy);
        }
        
        this.showIcon = this.showIcon && this.store.find('icon', /^.+$/) > -1;
        
        this.initTpl();
        Tine.Tinebase.widgets.keyfield.ComboBox.superclass.initComponent.call(this);
    },
    
    initTpl: function() {
        if (this.showIcon) {
            this.tpl = '<tpl for="."><div class="x-combo-list-item"><img src="{icon}" class="tine-keyfield-icon"/>{' + this.displayField + '}</div></tpl>';
        }
    }
});

Ext.reg('widget-keyfieldcombo', Tine.Tinebase.widgets.keyfield.ComboBox);