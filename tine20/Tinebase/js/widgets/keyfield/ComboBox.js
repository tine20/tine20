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
    forceSelection: true,
    /* end config */
    
    initComponent: function() {
        this.app = Ext.isString(this.app) ? Tine.Tinebase.appMgr.get(this.app) : this.app;

        // get keyField config
        this.keyFieldConfig = this.app.getRegistry().get('config')[this.keyFieldName];

        var definition = this.keyFieldConfig.definition,
            options = definition && definition.options || {};

        this.parentField = options.parentField;
        this.transitions = this.app.getRegistry().get('config')[definition?.options?.transitionsConfig];

        if (! this.value && ! this.parentField && (this.keyFieldConfig && Ext.isObject(this.keyFieldConfig.value) && this.keyFieldConfig.value.hasOwnProperty('default'))) {
            this.value = this.keyFieldConfig.value['default'];
        }

        this.store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app, this.keyFieldName);

        if (this.sortBy) {
            this.store.sort(this.sortBy);
        }
        
        this.showIcon = this.showIcon && this.store.find('icon', /^.+$/) > -1;
        
        this.initTpl();

        this.on('afterrender', this.onAfterRender, this, {buffer: 50});
        this.on('beforeselect', this.onBeforeSelect, this);

        Tine.Tinebase.widgets.keyfield.ComboBox.superclass.initComponent.call(this);
    },
    
    initTpl: function() {
        if (this.showIcon) {
            this.tpl = '<tpl for="."><div class="x-combo-list-item {_itemCls}"><tpl if="icon"><img src="{icon}" class="tine-keyfield-icon"/></tpl>{' + this.displayField + '}</div></tpl>';
        }
    },

    setValue(value, record) {
        const allowedTargetStatus = this.transitions ? this.transitions.value[value || '']?.targetStatus : null;

        this.store.each((record) => {
            const selectable = allowedTargetStatus ? record.id === value || allowedTargetStatus.indexOf(record.id) >= 0 : true;
            record.set('_itemCls', selectable ? '' : 'x-combo-list-unselectable-item');
        });

        return Tine.Tinebase.widgets.keyfield.ComboBox.superclass.setValue.apply(this, arguments);
    },

    onBeforeSelect(combo, record) {
        const transistionAllowed = record.get('_itemCls') !== 'x-combo-list-unselectable-item';
        if (! transistionAllowed) {
            const msg = this.app.formatMessage('Transition from {current} to {target} is not allowed.', {
                current: this.store.getById(this.getValue()).get(this.displayField),
                target: record.get(this.displayField)
            });
            Ext.ux.MessageBox.msg(this.app.i18n._('Not Allowed'), msg);
        }
        return transistionAllowed
    },

    onAfterRender: function() {
        if (this.parentField) {
            var formPanel = this.findParentBy(function (c) {
                    return Ext.isFunction(c.getForm)
                }),
                form = formPanel ? formPanel.getForm() : null,
                parentField = form ? form.findField(this.parentField) : null;

            if (parentField) {
                parentField.setValue = parentField.setValue.createSequence(this.applyParentValue, this);

                this.applyParentValue(parentField.getValue());
            }
        }
    },

    applyParentValue: function(parentValue){
        this.store.filter('id', parentValue);

        var parentRe = new RegExp('^' + window.lodash.escapeRegExp(parentValue)),
            defaultValues = this.keyFieldConfig && Ext.isObject(this.keyFieldConfig.value) && this.keyFieldConfig.value.hasOwnProperty('default') ? this.keyFieldConfig.value['default'] : false,
            defaultValue = '';

        // apply default if current value is not appropriate
        if (! String(this.getValue()).match(parentRe)) {
            Ext.each(defaultValues, function (candidate) {
                if (String(candidate).match(parentRe)) {
                    defaultValue = candidate;
                    return false;
                }
            }, this);

            this.setValue(defaultValue);
        }
    },

    doQuery: function() {
        this.onLoad();
    }
});

Ext.reg('widget-keyfieldcombo', Tine.Tinebase.widgets.keyfield.ComboBox);
