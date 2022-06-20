/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './AbstractEditDialog';
import DDSortPlugin from 'ux/grid/DDSortPlugin';

const AbstractGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    quickaddMode: 'sorted',
    autoExpandColumn: 'title',
    quickaddMandatory: 'title',

    allowCreateNew: true,
    enableBbar: true,
    editDialogConfig: {
        mode: 'local'
    },

    ddSortCol: 'sorting',
    sortInc: 10000,
    isFormField: true,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);

        this.store = new Ext.data.GroupingStore({
            // groupField: 'grouping',
            reader: new Ext.data.JsonReader({}, this.recordClass),
            sortInfo: {
                field: 'sorting',
                direction: 'ASC'
            }
        });
        this.store.addSorted = this.store.addSorted.createSequence((pos) => { this.lastAddPos = pos });

        this.view = new Ext.grid.GroupingView({
            emptyGroupText: this.app.i18n._('Generic'),
            // forceFit: true,
            showGroupName: false,
            // enableNoGroups: false,
            enableGroupingMenu: false,
            hideGroupedColumn: true
        });

        // skip defaults
        this.quickaddRecord = new this.recordClass({ grouping: '', sorting: this.sortInc }, Ext.id());

        this.on('beforeedit', this.onBeforeEditPosition, this);
        this.on('afteredit', this.onAfterEditPosition, this);
        this.on('beforeaddrecord', this.onNewProduct, this);
        this.on('beforeremoverecord', this.onBeforeRemovePosition, this);

        this.store.on('add', this.checkGroupingState, this, { buffer: 100 });
        this.store.on('update', this.checkGroupingState, this, { buffer: 100 });
        this.store.on('remove', this.checkGroupingState, this, { buffer: 100 });

        this.store.on('remove', this.onRemovePosition, this);

        this.enableDragDrop = true;
        this.ddGroup = this.recordClass.getRecordName();
        this.plugins = [new DDSortPlugin({
            ddSortCol: this.ddSortCol
        })];
        return this.supr().initComponent.call(this);
    },

    // quickadd
    onNewProduct(position, grid) {
        _.defer(async () => {
            this.store.suspendEvents();
            // copy product properties to position
            let productData = position.get('title');
            const lang = this.editDialog.getForm().findField('document_language').getValue();

            position.setFromProduct(productData, lang);
            position.setId(Tine.Tinebase.data.Record.generateUID());
            if (!position.get('grouping')) {
                position.set('grouping', this.quickaddRecord.get('grouping'));
            }

            // unfold subproducts
            const positions = [position];
            if (productData.unfold_type) {
                if (productData.unfold_type === 'SET') {
                    position.set('unit', null);
                    position.set('quantity', null);
                    position.clearPrice();
                }
                // Note: subprductMapping / subproducts are not resolved in search -> fetch it
                productData = await Tine.Sales.getProduct(productData.id);
                position.set('product_id', productData)
                _.forEach(productData.subproducts, (subproductMapping, idx) => {
                    const subposition = new this.recordClass({}, Tine.Tinebase.data.Record.generateUID());
                    // NOTE: need to create record to do conversions (string -> int) here!
                    const product = Tine.Tinebase.data.Record.setFromJson(subproductMapping.product_id, Tine.Sales.Model.Product);
                    subposition.setFromProduct(product.data, lang);
                    subposition.set('quantity', subproductMapping.quantity);
                    subposition.computePrice();
                    subposition.set('parent_id', position.id);
                    // NOTE: sorting of subproductmapping sorts inside subpositions only (atm)
                    subposition.set('sorting', position.get('sorting') ? position.get('sorting') + 100 * idx : null);
                    // @TODO where to store shortcut?
                    // @TODO where to start unfold_type? do we need to remember?
                    if (productData.unfold_type === 'BUNDLE') {
                        subposition.clearPrice();
                    }
                    if (!subposition.get('grouping')) {
                        subposition.set('grouping', position.get('grouping'));
                    }
                    positions.push(subposition);
                });
            }

            // add sorted positions
            // shall we ignore sorting of own group?
            const unsorted = _.reduce(positions, (unsorted, pos) => {
                if (pos.get('sorting')) {
                    this.store.addSorted(pos);
                    return unsorted;
                }
                return unsorted.concat(pos);
            }, []);


            const grouped = _.groupBy(unsorted, 'data.grouping');

            // sort in own group
            const ownGroup = _.get(grouped, this.quickaddRecord.get('grouping'), []);
            if (ownGroup.length) {
                // @TODO applySorting is wrong here as we e.g. can append
                // -> use only if not last!
                this.applySorting(ownGroup, this.quickaddRecord, 'above');
                ownGroup.forEach((r) => { this.store.addSorted(r) });
            }

            // append remaining groups
            _.forEach(grouped, (poss, grp) => {
                if (grp !== this.quickaddRecord.get('grouping')) {
                    const last = this.store.data.items.filter((r) => { return r !== this.quickaddRecord && r.get('grouping') == grp })
                        .sort((r) => { return r.get('sorting') })
                        .pop();
                    const sorting = _.get(last, 'data.sorting', 0);
                    _.forEach(poss, (pos, idx) => {
                        pos.set('sorting', sorting + (idx+1)* this.sortInc);
                        this.store.addSorted(pos);
                    });
                }
            });

            this.applySorting([this.quickaddRecord], this.lastAddPos, 'below');

            this.store.resumeEvents();
            this.store.applySort();
            this.checkGroupingState();
            _.defer(() => {this.getView().refresh()});

            let row = this.store.indexOf(position);
            this.selModel.selectRow(row);
            _.delay(() => {
                const col = this.colModel.columns.indexOf(this.colModel.columns.find((c) => c.dataIndex === 'title'));
                this.startEditing(row, col);
            }, 100);

            positions.forEach((pos) => { pos.commit() });
            this.applyNumbering();
            this.fireEvent('change', this)
        });
        return false;
    },

    applySorting: DDSortPlugin.prototype.applySorting,

    applyNumbering() {
        const counters = {};
        this.store.each((pos) => {
            if (pos === this.quickaddRecord) return;

            const rangeKey = (pos.get('grouping') || '') + '_' + (pos.get('parent_id') || '');
            counters[rangeKey] = (counters[rangeKey] || 0) + 1;

            // @TODO implement better groupPrefix
            const groupPrefix = pos.get('grouping') ? pos.get('grouping')[0] : '';
            const parentPrefix = pos.get('parent_id') ? this.store.getById(pos.get('parent_id')).get('pos_number') : '';

            const posNumber = (parentPrefix ? parentPrefix + '.' : (groupPrefix ? groupPrefix + ' ' : '')) + counters[rangeKey];
            pos.data.pos_number = posNumber;
        });
    },

    // checks and applies if grid is grouped
    checkGroupingState: function() {
        // grouping might get lost when grp record is removed/reinserted on update
        this.store.groupBy(this.store.data.items.filter((r) => { return !!r.get('grouping') }).length ? 'grouping' : '');
        this.store.applySort();
    },

    onBeforeEditPosition(e) {
        if (!e.record.isProductType() && ['title'].concat(e.record.get('type') === 'TEXT' ? 'description' : []).indexOf(e.field) < 0 ) {
            e.cancel = true;
        }

        if (e.record.isProductType() && ['title', 'description', 'quantity', 'unit'].indexOf(e.field) < 0
            // @FIXME product BUNDLE detection
            && e.record.get('parent_id') && this.store.getById(e.record.get('parent_id')).get('gross_price')) {
            e.cancel = true;
        }

        if (e.record.isProductType() && ['title', 'description'].indexOf(e.field) < 0
            // @FIXME product SET detection
            && !e.record.get('gross_price') && this.store.find('pos_number', new RegExp(e.record.get('pos_number') + '\..+')) >= 0) {
            e.cancel = true;
        }

        if (e.field === 'title' && Ext.fly(Ext.EventObject.getTarget()).hasClass('sales-document-positiongrid-description')) {
            e.cancel = true;
            // const target = Ext.EventObject.getTarget();
            _.defer(() => {
                // @TODO start a textarea editor
                // NOTE: inline might become hard as we need enter key for newlines
                // what about tab key, shouldn't it switch to description?
            })
        }
    },

    onAfterEditPosition(e) {
        this.colModel.columns.find(c => c.dataIndex === 'position_discount_sum').editor.field.checkState(null, e.record)

        e.record.computePrice();
        // @TODO compute document total / fire some event?
        this.fireEvent('change', this)
    },

    onBeforeRemovePosition(pos) {
        return pos === this.quickaddRecord ? false : null;
    },

    onRemovePosition() {
        this.fireEvent('change', this)
    },

    getColumnModel() {
        const modelConfig = this.recordClass.getModelConfiguration();
        const colMgr = _.bind(Tine.widgets.grid.ColumnManager.get, Tine.widgets.grid.ColumnManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _, 'editDialog', _);
        const fieldMgr = _.bind(Tine.widgets.form.FieldManager.getByModelConfig, Tine.widgets.form.FieldManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _, 'propertyGrid', _);
        const i18n = this.app.i18n;

        // init all columns
        this.columns = Object.keys(modelConfig.fields).reduce((columns, fieldName) => {
            const col = colMgr(fieldName, { sortable: false });
            if (col) {
                if (['type', 'pos_number', 'gross_price'].indexOf(fieldName) < 0) {
                    col.editor = Ext.ComponentMgr.create(fieldMgr(fieldName));
                }
                columns.push(col);
            }
            return columns;
        }, []);

        // some adjustments
        Object.assign(this.columns.find((c) => c.dataIndex === 'type'),{ header: '', width: 15, renderer: _.bind(this.typeRenderer, this) });
        Object.assign(this.columns.find((c) => c.dataIndex === 'pos_number'),{ width: 40 });
        // @TODO product_id picker nur sales produkte die noch aktiv
        Object.assign(this.columns.find((c) => c.dataIndex === 'title'),{ quickaddField: this.getProductPicker(), renderer: _.bind(this.titleRenderer, this) });
        Object.assign(this.columns.find((c) => c.dataIndex === 'unit_price') || {},{ header: i18n._('Price') });
        Object.assign(this.columns.find((c) => c.dataIndex === 'position_discount_sum') || {},{ header: i18n._('Discount') });
        Object.assign(this.columns.find((c) => c.dataIndex === 'gross_price') || {},{ header: i18n._('Total') });

        const colModel = this.supr().getColumnModel.call(this);

        // manage discount field mode
        const checkDiscountFieldMode = () => {
            this.colModel.columns.find(c => c.dataIndex === 'position_discount_sum').editor.field.singleField =
                this.colModel.columns.find(c => c.dataIndex === 'position_discount_percentage').hidden;

        }
        colModel.on('hiddenchange', checkDiscountFieldMode)

        return colModel;
    },

    getProductPicker() {
        const fieldMgr = _.bind(Tine.widgets.form.FieldManager.getByModelConfig, Tine.widgets.form.FieldManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _, 'propertyGrid', _);

        this.productPicker = this.productPicker || Ext.ComponentMgr.create(fieldMgr('product_id', {
            blurOnSelect: true,
        }))
        return this.productPicker
    },

    titleRenderer(value, metadata, record) {
        let str = '<div class="sales-document-positiongrid-title">' + Tine.Tinebase.EncodingHelper.encode(record.get('title')) + '</div>';
        if (record.get('description')) {
            str += '<div class="sales-document-positiongrid-description">' + Tine.Tinebase.EncodingHelper.encode(record.get('description')) + '</div>';
        }
        return str;
    },

    typeRenderer() {},

    // @TODO have modelconfig type for this fields!
    discountRenderer(value, metadata, record) {
        if (record.get('type') !== 'PRODUCT') return '';

        if (this.colModel.columns.find(c => c.dataIndex === 'position_discount_percentage').hidden) {
            return record.get('position_discount_type') === 'PERCENTAGE' ?
                (Tine.Tinebase.common.percentRenderer(value, 'float') + ' %') :
                (Ext.util.Format.money(value) + ' ' + Tine.Tinebase.registry.get('currencySymbol'));
        }
        return Ext.util.Format.money(value) + ' ' + Tine.Tinebase.registry.get('currencySymbol')
    },

    setValue: function(value) {
        this.store.loadData(value || [], false);
        const last = this.store.getAt(this.store.getCount() -1);
        if (last) {
            this.quickaddRecord.set('sorting', last.get('sorting') + this.sortInc);
        }
        this.store.add(this.quickaddRecord);
    },
    getValue: function () {
        const data = [];
        Tine.Tinebase.common.assertComparable(data);

        this.store.each((record) => {
            if (record !== this.quickaddRecord) {
                data.push(record.data);
            }
        });

        return data;
    },
    checkState(editDialog, record) {
        if (!this.editDialog) {
            this.editDialog = editDialog
            this.getProductPicker().localizedLangPicker = this.editDialog.getForm().findField('document_language')
        }
    },
    /* needed for isFormField cycle */
    markInvalid: Ext.form.Field.prototype.markInvalid,
    clearInvalid: Ext.form.Field.prototype.clearInvalid,
    getMessageHandler: Ext.form.Field.prototype.getMessageHandler,
    getName: Ext.form.Field.prototype.getName,
    validate: function() { return true; }
});

export default AbstractGridPanel;
