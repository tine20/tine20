/*
 * backport from Extjs 4.x  Ext.layout.container.Accordion
 */

Ext.ns('Ext.ux', 'Ext.ux.layout');

Ext.ux.layout.MultiAccordionLayout = function(config) {
    var me = this;

    Ext.ux.layout.MultiAccordionLayout.superclass.constructor(arguments);

    if (me.animate) {
        me.animatePolicy = Ext.apply({}, me.defaultAnimatePolicy);
    } else {
        me.animatePolicy = null;
    }
};

Ext.extend(Ext.ux.layout.MultiAccordionLayout, Ext.layout.VBoxLayout, {
    align: 'stretch',

    /**
     * @cfg {Boolean} fill
     * True to adjust the active item's height to fill the available space in the container, false to use the
     * item's current height, or auto height if not explicitly set.
     */
    fill : true,

    /**
     * @cfg {Boolean} autoWidth
     * Child Panels have their width actively managed to fit within the accordion's width.
     * @removed This config is ignored in ExtJS 4
     */

    /**
     * @cfg {Boolean} titleCollapse
     * True to allow expand/collapse of each contained panel by clicking anywhere on the title bar, false to allow
     * expand/collapse only when the toggle tool button is clicked.  When set to false,
     * {@link #hideCollapseTool} should be false also.
     */
    titleCollapse : true,

    /**
     * @cfg {Boolean} hideCollapseTool
     * True to hide the contained Panels' collapse/expand toggle buttons, false to display them.
     * When set to true, {@link #titleCollapse} is automatically set to true.
     */
    hideCollapseTool : false,

    /**
     * @cfg {Boolean} collapseFirst
     * True to make sure the collapse/expand toggle button always renders first (to the left of) any other tools
     * in the contained Panels' title bars, false to render it last. By default, this will use the
     * {@link Ext.panel.Panel#collapseFirst} setting on the panel. If the config option is specified on the layout,
     * it will override the panel value.
     */
    collapseFirst : undefined,

    /**
     * @cfg {Boolean} animate
     * True to slide the contained panels open and closed during expand/collapse using animation, false to open and
     * close directly with no animation. Note: The layout performs animated collapsing
     * and expanding, *not* the child Panels.
     */
    animate : true,
    /**
     * @cfg {Boolean} activeOnTop
     * Only valid when {@link #multi} is `false` and {@link #animate} is `false`.
     *
     * True to swap the position of each panel as it is expanded so that it becomes the first item in the container,
     * false to keep the panels in the rendered order.
     */
    activeOnTop : false,
    /**
     * @cfg {Boolean} multi
     * Set to true to enable multiple accordion items to be open at once.
     */
    multi: true,

    defaultAnimatePolicy: {
        y: true,
        height: true
    },

    // private
    onLayout : function(ct, target) {
        var items = ct.items.items;
        this.beforeRenderItems(items);

        this.container.on('afterlayout', this.updatePanelClasses, this);
        Ext.ux.layout.MultiAccordionLayout.superclass.onLayout.apply(this, arguments);
    },

    beforeRenderItems: function (items) {
        var me = this,
            ln = items.length,
            i = 0,
            owner = me.container.ownerCt,
            collapseFirst = me.collapseFirst,
            hasCollapseFirst = Ext.isDefined(collapseFirst),
            expandedItem,
            comp;

        for (; i < ln; i++) {
            comp = items[i];
            if (!comp.rendered) {
                // Set up initial properties for Panels in an accordion.
                if (hasCollapseFirst) {
                    comp.collapseFirst = collapseFirst;
                }
                if (me.hideCollapseTool) {
                    comp.hideCollapseTool = me.hideCollapseTool;
                    comp.titleCollapse = true;
                }
                else if (me.titleCollapse) {
                    comp.titleCollapse = me.titleCollapse;
                }

                delete comp.hideHeader;
                delete comp.width;
                comp.collapsible = true;
                comp.title = comp.title || '&#160;';
                // comp.addBodyCls('x-accordion-body');

                // If only one child Panel is allowed to be expanded
                // then collapse all except the first one found with collapsed:false
                // If we have hasExpanded set, we've already done this
                if (!me.multi && !me.hasExpanded) {
                    // If there is an expanded item, all others must be rendered collapsed.
                    if (me.expandedItem !== undefined) {
                        comp.collapsed = true;
                    }
                    // Otherwise expand the first item with collapsed explicitly configured as false
                    else if (comp.hasOwnProperty('collapsed') && comp.collapsed === false) {
                        me.expandedItem = i;
                    } else {
                        comp.collapsed = true;
                    }

                    // If only one child Panel may be expanded, then intercept expand/show requests.
                    owner.mon(comp, {
                        show: me.onComponentShow,
                        beforeexpand: me.onComponentExpand,
                        scope: me
                    });
                }

                // If we must fill available space, a collapse must be listened for and a sibling must
                // be expanded.
                if (me.fill) {
                    owner.mon(comp, {
                        beforecollapse: me.onComponentCollapse,
                        beforeexpand: me.onComponentExpand,
                        expand: me.layout,
                        collapse: me.layout,
                        scope: me
                    });
                }
            }
        }

        // If no collapsed:false Panels found, make the first one expanded.
        expandedItem = me.expandedItem;
        if (!me.hasExpanded) {
            if (expandedItem === undefined) {
                if (ln) {
                    items[0].collapsed = false;
                }
            } else if (me.activeOnTop) {
                expandedItem = items[expandedItem];
                expandedItem.collapsed = false;
                me.configureItem(expandedItem);
                owner.insert(0, expandedItem);
            }
            me.hasExpanded = true;
        }
    },

    configureItem: function(item) {

        Ext.ux.layout.MultiAccordionLayout.superclass.configureItem.apply(this, arguments);

        // We handle animations for the expand/collapse of items.
        // Items do not have individual borders
        item.animCollapse = item.border = false;

        // If filling available space, all Panels flex.
        if (this.fill) {
            item.flex = 1;
        }
    },

    onChildPanelRender: function(panel) {
        panel.header.addClass('x-accordion-hd');
    },

    updatePanelClasses: function(ct) {
        var children = ct.items.items,
            ln = children.length,
            siblingCollapsed = true,
            i, child, header;

        for (i = 0; i < ln; i++) {
            child = children[i];
            header = child.header;
            header.addClass('x-accordion-hd');

            if (siblingCollapsed) {
                header.removeClass('x-accordion-hd-sibling-expanded');
            } else {
                header.addClass('x-accordion-hd-sibling-expanded');
            }

            if (i + 1 == ln && child.collapsed) {
                header.addClass('x-accordion-hd-last-collapsed');
            } else {
                header.removeClass('x-accordion-hd-last-collapsed');
            }

            siblingCollapsed = child.collapsed;
        }
    },

    // When a Component expands, adjust the heights of the other Components to be just enough to accommodate
    // their headers.
    // The expanded Component receives the only flex value, and so gets all remaining space.
    onComponentExpand: function(toExpand) {
        var me = this,
            owner = me.container.ownerCt,
            multi = me.multi,
            animate = me.animate,
            moveToTop = !multi && !me.animate && me.activeOnTop,
            expanded,
            expandedCount, i,
            previousValue;

        toExpand.flex = 1;

        if (!me.processing) {
            me.processing = true;
            previousValue = owner.deferLayout;
            owner.deferLayout = true;
            expanded = multi ? [] : expanded = toExpand.ownerCt.items.filterBy(function(item) { return !item.collapsed}).items;
            expandedCount = expanded.length;

            // Collapse all other expanded child items (Won't loop if multi is true)
            for (i = 0; i < expandedCount; i++) {
                expanded[i].collapse();
            }

            if (moveToTop) {
                // Prevent extra layout when moving the item
                Ext.suspendLayouts();
                owner.insert(0, toExpand);
                Ext.resumeLayouts();
            }

            owner.deferLayout = previousValue;
            me.processing = false;
        }
    },

    onComponentCollapse: function(comp) {
        var me = this,
            owner = me.container.ownerCt,
            toExpand,
            expanded,
            previousValue;

        if (owner.items.getCount() === 1) {
            // do not allow collapse if there is only one item
            return false;
        }

        comp.flex = false;
        if (!me.processing) {
            me.processing = true;
            previousValue = owner.deferLayout;
            owner.deferLayout = true;
            toExpand = comp.ownerCt.items.itemAt(comp.ownerCt.items.indexOf(comp) +1) ||
                comp.ownerCt.items.itemAt(comp.ownerCt.items.indexOf(comp) -1);

            // If we are allowing multi, and the "toCollapse" component is NOT the only expanded Component,
            // then ask the box layout to collapse it to its header.
            if (me.multi) {
                expanded = comp.ownerCt.items.filterBy(function(item) { return !item.collapsed}).items;

                // If the collapsing Panel is the only expanded one, expand the following Component.
                // All this is handling fill: true, so there must be at least one expanded,
                if (expanded.length === 1) {
                    if (! toExpand) {
                        me.processing = false;
                        return false;
                    }
                    toExpand.expand();
                }

            } else if (toExpand) {
                toExpand.expand();
            }
            owner.deferLayout = previousValue;
            me.processing = false;
        }
    },

    onComponentShow: function(comp) {
        // Showing a Component means that you want to see it, so expand it.
        this.onComponentExpand(comp);
    }
});

Ext.Container.LAYOUTS['ux.multiaccordion'] = Ext.ux.layout.MultiAccordionLayout;