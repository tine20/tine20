/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * Recursive Filter
 *
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.RecursiveFilter
 * @extends     Ext.Toolbar
 *
 * @param       {Object} config
 * @constructor
 */
Tine.Filemanager.RecursiveFilter = Ext.extend(Ext.Toolbar, {

    /**
     * @cfg {Tine.widgets.grid.GridPanel}
     */
    gridPanel: null,

    field: 'recursive',
    operator: 'equals',
    style: 'background-image: none;',

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Filemanager');

        Ext.applyIf(this, new Tine.widgets.grid.FilterPlugin());
        this.init(this.gridPanel);

        this.globalBtn = new Ext.Button({
            text: this.app.i18n._('Whole Filemanager'),
            pressed: false,
            ref: '../globalBtn',
            xtype: 'tbbtnlockedtoggle',
            toggleGroup: 'Calendar_Toolbar_tgViewTypes',
            handler: this.onFilterChange,
            scope: this
        });

        this.localBtn = new Ext.Button({
            text: this.app.i18n._('This Folder'),
            pressed: true,
            ref: '../localBtn',
            xtype: 'tbbtnlockedtoggle',
            toggleGroup: 'Calendar_Toolbar_tgViewTypes',
            handler: this.onFilterChange,
            scope: this
        });

        this.items = [{
            xtype: 'tbtext',
            text: this.app.i18n._('Search') + ': '
        }, this.globalBtn, this.localBtn];

        this.supr().initComponent.call(this);
    },

    getValue: function() {
        return this.globalBtn.pressed ?
            {field: this.field, operator: this.operator, value: true} : null;
    },

    setValue: function(filters) {
        // only show if we have a contributing filter
        var setVisible = false;

        for (var i=0; i<filters.length; i++) {
            switch (filters[i].field) {
                case 'query':
                    setVisible |= !!filters[i].value;
                    break;
                case 'type':
                case 'path':
                    break;
                case 'recursive':
                    this.globalBtn.toggle(!!filters[i].value);
                    this.localBtn.toggle(!filters[i].value);
                    break;
                default:
                    setVisible |= true;
                    break;
            }
        }

        this.setVisible(setVisible);

        if (! setVisible) {
            // reset own filter
            this.globalBtn.toggle(false);
            this.localBtn.toggle(true);
        }
    }
});