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
    
    /* begin config */
    blurOnSelect  : true,
    expandOnFocus : true,
    mode          : 'local',
    displayField  : 'i18nValue',
    valueField    : 'id',
    /* end config */
    
    initComponent: function() {
        this.store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app, this.keyFieldName);
        
        Tine.Tinebase.widgets.keyfield.ComboBox.superclass.initComponent.call(this);
    }
});

Ext.reg('widget-keyfieldcombo', Tine.Tinebase.widgets.keyfield.ComboBox);