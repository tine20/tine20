/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

// @TODO: implement selection model
// @TODO: enable edit&delete btns // have action updater
// @TODO: show relation type - resove all relation types e.g. SITE -> Standort
Tine.Addressbook.StructurePanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * @property {cytoscape} cy
     */
    cy: null,

    // private
    recordClass: Tine.Tinebase.Model.Path,
    border: false,
    layout: 'border',
    usePagingToolbar: false,
    autoRefreshInterval: null,
    listenMessageBus: false,

    initComponent: function() {
        var me = this;

        this.app = Tine.Tinebase.appMgr.get('Addressbook');

        this.recordProxy = new Tine.Tinebase.data.RecordProxy({
            recordClass: this.recordClass
        });

        this.detailsPanel = new Ext.Container({
            layout: 'card',
            activeItem: 0,
            border: false,
            items: [new Ext.ux.display.DisplayPanel ({
                id: 'none',
                border: false,
                layout: 'hbox',
                defaults:{margins:'0 5 0 0'},
                layoutConfig: {
                    padding:'5',
                    align:'stretch'
                },
                items: [{
                    cls : 'x-ux-display',
                    layout: 'ux.display',
                    flex: 1,
                    layoutConfig: {
                        background: 'solid',
                        declaration: this.app.i18n._('Select record to see its details')
                    }
                }, {
                    flex: 1,
                    border: false,
                    layout: 'ux.display',
                    layoutConfig: {
                        background: 'border'
                    },
                    html: [
                        '<p>', this.app.i18n._('This module visualises hierarchically depended relations between records of different types.'), '</p>',
                        '<p>', this.app.i18n._('Hierarchically depended relations are created automatically for some data types like users and groups, but can also be created manually via the corresponding relation types.'), '</p>'
                    ].join('')
                }]
            })],
            doBind: function() {}
        });
        Tine.Addressbook.StructurePanel.superclass.initComponent.call(this);
    },

    initGrid: function() {
        var me = this,
            view = {};

        this.cyPanel = new Ext.Panel({
            // ref: '../cyPanel',
            tbar: [this.refresh = new Ext.Toolbar.Button({
                tooltip: Ext.PagingToolbar.prototype.refreshText,
                overflowText: Ext.PagingToolbar.prototype.refreshText,
                iconCls: 'x-tbar-loading',
                handler: this.doRefresh,
                scope: this
            }), '-'],
            layout: 'fit',
            border: false,
            getStore: function() {
                return me.store;
            },
            getView: function() {
                return view;
            },
            getSelectionModel: function() {
                return {
                    getSelections: function() {
                        return this.selectedNode;
                    }
                };
            }
        });

        // abstract code needs this name
        this.grid = this.cyPanel;
    },

    initActions: function() {
        Tine.Addressbook.StructurePanel.superclass.initActions.apply(this, arguments);
        this.action_addInNewWindow.hide();
        this.action_editInNewWindow.hide();
        this.action_deleteRecord.hide();
        this.actions_print.hide();
    },

    // NOTE: not yet, reloading(relayout) resets viewport
    initMessageBus: function() {
        postal.subscribe({
            channel: "recordchange",
            topic: '*.*.update',
            callback: this.onRecordChanges.createDelegate(this)
        });
    },

    /**
     * bus notified about record changes
     */
    onRecordChanges: function(data, e) {
        var me = this,
            _ = window.lodash,
            appName = e.topic.split('.')[0],
            modelName = e.topic.split('.')[1],
            model = [appName, 'Model', modelName].join('_'),
            id = data.id;

        if (me.cy) {
            var eles = me.cy.filter('[id="' + id+'"]').filter('[model="' + model + '"]');
            if (eles.length) {
                me.doRefresh()
            }
        }
    },

    doRefresh: function() {
        this.store.reload();
    },

    onRender: function() {
        var me = this;

        me.isReady = Promise.all([
            me.cyPanel.afterIsRendered(),
            new Promise(function (resolve, reject) {
                require.ensure(['cytoscape'], function () {
                    resolve(require('cytoscape'));
                }, 'Tinebase/js/cytoscape');
            })])
            .then(function(values) {
                me.renderCy(values[1]);
            });

        Tine.Addressbook.StructurePanel.superclass.onRender.apply(this, arguments);
    },

    renderCy: function(cytoscape) {
        // init cy
        var me = this,
            _ = window.lodash;//,
            // elements = values[0];

        me.cy = cytoscape({
            container: me.cyPanel.body.dom,
            elements: [],
            boxSelectionEnabled: false,
            autounselectify: true,
            style: [
                {
                    selector: 'node',
                    css: {
                        'shape': 'rectangle',
                        'height': 20,
                        'width': function (ele) {
                            return 20 + _.get(ele.data('recordData'), 'tags', []).length * 18;
                        },
                        'background-position-x': 0,
                        'background-position-y': 0,
                        'border-color': '#ffffff',
                        'background-opacity': 0,
                        'border-width': 3,
                        'border-opacity': 0,
                        'background-color': '#ffffff',
                        'label': 'data(name)',
                        'background-image': me.createImage,
                        'background-fit': 'contain',
                        'overlay-padding': '20'

                    }
                },
                {
                    selector: 'node:selected',
                    css: {
                        'overlay-color': 'blue',
                        'overlay-opacity': '0.2',
                        'overlay-padding': '20'
                    }
                },
                {
                    selector: 'edge',
                    css: {
                        // 'label': 'data(type)',
                        'curve-style': 'bezier',
                        'target-arrow-shape': 'triangle',
                        'width': 4,
                        'line-color': '#ddd',
                        'target-arrow-color': '#ddd'
                    }
                }
            ]
        });

        me.cy.on('tap', _.bind(me.onTap, me));
    },

    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        Tine.Addressbook.StructurePanel.superclass.onStoreBeforeload.apply(this, arguments);

        // filter = [{field: 'shadow_path', operator: 'contains', value: '{SITE}'}]

        var me = this;
        me.refresh.disable();
        me.showLoadMask();
    },

    /**
     * called after a new set of Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        Tine.Addressbook.StructurePanel.superclass.onStoreLoad.apply(this, arguments);

        var me = this,
            _ = window.lodash,
            elements = me.computeElements(records);

        me.resolveRecords(elements)
            .then(me.isReady)
            .then(function() {
                var cy = me.cy;

                cy.startBatch();
                cy.remove('*');
                cy.add(elements);
                cy.endBatch();
                cy.layout(me.getLayoutOpts()).run();
                me.refresh.enable();
                me.hideLoadMask();
            });

    },

    getLayoutOpts: function() {
        return {
            name: 'breadthfirst',
            directed: true,
            padding: 10
        };
    },

    onTap: function(evt){
        var evtTarget = evt.target;

        // for some reason cy internal selection model does not work :-(
        // -> might have to do with autounselectify opton :-)
        if (this.selectedNode) {
            this.selectedNode.style({
                'overlay-opacity': '0'
            });
        }

        if (evtTarget === this.cy) {
            this.detailsPanel.layout.setActiveItem(0);
            return;
        }

        if (evtTarget.isNode()) {
            var node = evtTarget,
                model = node.data('model'),
                id = node.data('id');

            this.selectedNode = node;
            this.selectedNode.style({
                'overlay-opacity': '0.2'
            });

            // update detailsPanel
            var recordClass = Tine.Tinebase.data.RecordMgr.get(model),
                recordData = node.data('recordData'),
                record = new recordClass(recordData, node.data('id'));

            this.updateDetails(record);
            // @TODO later: update actions

            if (recordData && Ext.isDate(this.onTapLastTime) && this.onTapLastTime.getElapsed() < 500) {
                this.editRecord(record);
            }
            this.onTapLastTime = new Date();
        }
    },

    /**
     * NOTE: this is bullshit! The logic for opening a EditDialog is contained in the center panel
     *       but we don't want to fetch the centerpanel - we don't even know where to find it in some cases!
     *       -> let openWindow method deal with records!
     *       -> we have the same problem in the relations genericPickerGridPanel!
     * @param record
     */
    editRecord: function(record) {
        var _ = window.lodash,
            appName = record.appName,
            modelName = record.modelName,
            openMethod = _.get(Tine, appName + '.' + modelName + 'EditDialog.openWindow');

        if (openMethod) {
            openMethod({
                record: record,
                listeners: {
                    scope: this,
                    'update': this.doRefresh
                }
            });
        }
    },

    updateDetails: function(record) {
        var key = record.appName + '.' + record.modelName,
            detailsPanel = this.detailsPanel.get(key);

        if (! detailsPanel) {
            switch (key) {
                case 'Addressbook.Contact':
                    var dp = new Tine.Addressbook.ContactGridDetailsPanel({
                            i18n: Tine.Tinebase.appMgr.get('Addressbook').i18n
                        }),
                        detailsPanel = dp.getSingleRecordPanel();
                    break;
                default:
                    detailsPanel = new Ext.Panel({
                        id: key,
                        loadRecord: Ext.emptyFn,
                        border: false,
                        layout: 'fit',
                        padding: 5,
                        items: [{
                            cls : 'x-ux-display',
                            layout: 'ux.display',
                            layoutConfig: {
                                background: 'solid'
                            }
                        }]
                    });
                    break;
            }

            this.detailsPanel.items.add(key, detailsPanel);
        }

        this.detailsPanel.layout.setActiveItem(key);
        detailsPanel.loadRecord(record);
    },

    resolveRecords: function(elements) {
        var _ = window.lodash,
            me = this,
            promises = [];

        _.each(_.groupBy(elements.nodes, 'data.model'), function(data, modelName) {
            var ids = _.map(data, 'data.id'),
                method = _.get(Tine, modelName.replace(/_.*$/, '') + '.search' + modelName.replace(/^.*_/, '') + 's');

            promises.push(method([{field: 'id', operator: 'in', value: ids}], {})
                .then(function(response) {
                    _.each(response.results, function(recordData) {
                        _.set(_.find(data, function(o) {
                            return _.get(o, 'data.id') == recordData.id;
                        }), 'data.recordData', recordData);
                    })
                })
            );
        });

        return Promise.all(promises);
    },

    // els & edges
    computeElements: function(paths) {
        var _ = window.lodash,
            me = this,
            elements = {nodes: [], edges: []},
            processed = [];

        _.each(paths, function(path) {
            var pathData = path.data,
                names = String(pathData.path).replace(/^\//, '').split('/'),
                ids = String(pathData.shadow_path).replace(/^\//, '').split('/'),
                lastId = false,
                type = '';

            _.each(names, function(name) {
                var parts = ids.shift().match(/\{(.+)\}([^{]+)(?:\{(.*)\})?/),
                    model = parts[1],
                    id = parts[2],
                    elId;

                // @TODO: map list-role to edgetype?
                if (model == 'Addressbook_Model_ListRole') return;

                name = name.replace(/{.*}$/, '');

                if (lastId) {
                    // edge
                    elId = lastId + id + type;
                    if (processed.indexOf(elId) < 0) {
                        elements.edges.push({data: {id: elId, source: lastId, target: id, type: type}});
                        processed.push(elId);
                    }
                }
                // node
                elId = model + id;
                if (processed.indexOf(elId) < 0) {
                    elements.nodes.push({data: {id: id, name: name, model: model}});
                    processed.push(elId);
                }

                lastId = id;
                type = parts[3];
            });
        });

        return elements;
    },

    createImage: function(ele) {
        var _ = window.lodash,
            tags = _.get(ele.data('recordData'), 'tags', []),
            recordImage = _.get(ele.data('recordData'), 'jpegphoto'),
            iconCls = '.' + ele.data('model').replace('_Model_', ''),
            url = function(data) {return data},
            iconData = Ext.util.CSS.getRule(iconCls).style.backgroundImage,
            iconImage = new Image(),
            canvas = document.createElement('canvas'),
            ctx = canvas.getContext('2d');

        canvas.width = 20 + tags.length * 18;
        canvas.height = 18;

        iconImage.src = eval(iconData);
        ctx.drawImage(iconImage, 0, 0, 18, 18);

        ctx.strokeStyle = "#000000";
        _.each(tags, function(tag, idx) {
            ctx.fillStyle = tag.color;
            ctx.beginPath();
            ctx.arc(20 + 9 + idx*18, 8, 7, 0, Math.PI*2, true);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
        });

        return 'url("' + canvas.toDataURL() + '")';
    },

    showLoadMask: function() {
        var me = this;
        return me.afterIsRendered()
            .then(function() {
                if (! me.loadMask) {
                    me.loadMask = new Ext.LoadMask(me.getEl(), {msg: me.app.i18n._("Loading Structure data...")});
                }
                me.loadMask.show.defer(100, me.loadMask);
            });
    },

    hideLoadMask: function() {
        this.loadMask.hide.defer(100, this.loadMask);
        return Promise.resolve();
    }
});

Ext.reg('addressbook.structurepanel', Tine.Addressbook.StructurePanel);


Tine.Addressbook.StructureWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    recordClass: Tine.Tinebase.Model.Path,
    hasContainerTreePanel: false,
    hasFavoritesPanel: true
});