/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         use new filter syntax in onBeforeQuery when TagFilter is refactored and extends Tinebase_Model_Filter_FilterGroup 
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagCombo
 * @extends     Ext.ux.form.ClearableComboBox
 */
Tine.widgets.tags.TagCombo = Ext.extend(Ext.ux.form.ClearableComboBox, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Bool} findGlobalTags true to find global tags during search (default: true)
     */
    findGlobalTags: true,

    /**
     * @cfg {Bool} onlyUsableTags true to find only usable flags for the user (default: true)
     */
    onlyUsableTags: false,
    
    id: 'TagCombo',
    emptyText: null,
    typeAhead: true,
    mode: 'remote',
    triggerAction: 'all',
    displayField:'name',
    valueField:'id',
    width: 100,
    minChars: 3,
    
    /**
     * @private
     */
    initComponent: function() {
        this.emptyText = this.emptyText ? this.emptyText : _('tag name');
        
        this.initStore();
        this.initTemplate();
        
        Tine.widgets.tags.TagCombo.superclass.initComponent.call(this);
        
        this.on('select', function(){
            var v = this.getValue();
            if(String(v) !== String(this.startValue)){
                this.fireEvent('change', this, v, this.startValue);
            }
        }, this);
        
        this.on('beforequery', this.onBeforeQuery, this);
    },
    
    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        var filter = {
            name: (qevent.query && qevent.query != '') ? '%' + qevent.query + '%' : '',
            application: this.app ? this.app.appName : '',
            grant: (this.onlyUsableTags) ? 'use' : 'view' 
        };
        
        this.store.baseParams.filter = filter;
    },

    /**
     * set value
     * 
     * @param {} value
     */
    setValue: function(value) {
        if(typeof value === 'object' && Object.prototype.toString.call(value) === '[object Object]') {
            this.store.loadData({results: [value]});
            value = value.id;
        }
        Tine.widgets.tags.TagCombo.superclass.setValue.call(this, value);
    },
    
    /**
     * init store
     */
    initStore: function() {
        this.store = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.Tinebase.Model.Tag,
            baseParams: {
                method: 'Tinebase.searchTags',
                paging : {}
            }
        });        
    },
    
    /**
     * init template
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="x-combo-list-item">',
                '<div style="width: 8px; height: 8px; background-color:{values.color};',
                    ' border: 1px solid black; float: left; margin-right: 4px; margin-top: 2px;">&#160;</div>',
                '{[this.encode(values.name)]}</div></tpl>',
            {
                encode: function(value) {
                     if (value) {
                        return Ext.util.Format.htmlEncode(value);
                    } else {
                        return '';
                    }
                }
            }
        );        
    }
});
