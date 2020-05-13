/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.TimelineLabel
 * @extends     Ext.Component
 *
 * Calendar timeline view
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.TimelineLabel = Ext.extend(Ext.Component, {
    /**
     * @cfg {String} label
     * Note: must be escaped properly
     */
    label: '',

    iconCls: 'cal-timelinelabel-noicon',

    initialHeight: 25,
    collapsed: true,
    collapsedCls: 'cal-timelinelabel-collapsed',
    cls: 'cal-timelinelabel',


    initComponent: function() {
        if (this.collapsed) {
            this.cls = this.cls + ' ' + this.collapsedCls;
        }
        this.style = 'height: ' + this.initialHeight + 'px;';
        this.tpl = [
            '<div class="cal-timelinelabel-collapsetool"></div>',
            '<div class="cal-timelinelabel-icon {iconCls}"></div>',
            '{label}',
        ];
        this.data = {
            label: this.label,
            iconCls: this.iconCls
        };

        Tine.Calendar.TimelineLabel.superclass.initComponent.call(this);
    },

    afterRender: function() {
        Tine.Calendar.TimelineLabel.superclass.afterRender.call(this);
        this.getEl().on('click', this.toggleCollapsed, this);
    },

    toggleCollapsed: function() {
        return this.collapsed ? this.expand() : this.collapse();
    },

    collapse: function() {
        if (! this.collapsed) {
            this.getEl().addClass(this.collapsedCls);
            this.collapsed = true;
            this.getEl().dom.style.height = this.initialHeight + 'px';
            this.fireEvent('collapse', this, this.initialHeight);
        }
    },

    expand: function() {
        var o = {expandedHeight: this.initialHeight};
        this.collapsed = false;
        this.getEl().removeClass(this.collapsedCls);
        this.fireEvent('beforeexpand', this, o);
        this.getEl().dom.style.height = o.expandedHeight + 'px';
    }


});