/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './PositionEditDialog';
import DDSortPlugin from 'ux/grid/DDSortPlugin';

const PositionGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
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
            position.setFromProduct(productData);
            position.setId(Tine.Tinebase.data.Record.generateUID());
            if (!position.get('grouping')) {
                position.set('grouping', this.quickaddRecord.get('grouping'));
            }

            // unfold subproducts
            const positions = [position];
            if (productData.unfold_type) {
                // Note: subprductMapping / subproducts are not resolved in search -> fetch it
                productData = await Tine.Sales.getProduct(productData.id);
                position.get('product_id', productData)
                _.forEach(productData.subproducts, (subproductMapping) => {
                    const subposition = new this.recordClass({}, Tine.Tinebase.data.Record.generateUID());
                    subposition.setFromProduct(subproductMapping.product_id);
                    positions.push(subposition);
                    if (!subposition.get('grouping')) {
                        subposition.set('grouping', position.get('grouping'));
                    }
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
                this.applySorting([this.quickaddRecord], ownGroup.pop(), 'below');

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

            // @TODO pos numbering
            this.fireEvent('change', this)
        });
        return false;
    },

    applySorting: DDSortPlugin.prototype.applySorting,

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
    },

    onAfterEditPosition(e) {
        this.colModel.columns.find(c => c.dataIndex === 'position_discount_sum').editor.field.checkState(null, e.record)

        e.record.computePrice();
        // @TODO compute document total / fire some event?
        this.fireEvent('change', this)
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
            const col = colMgr(fieldName);
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
        // @TODO product_id picker nur sales produkte die noch aktiv
        Object.assign(this.columns.find((c) => c.dataIndex === 'title'),{ quickaddField: Ext.ComponentMgr.create(fieldMgr('product_id', {blurOnSelect: true})) });
        Object.assign(this.columns.find((c) => c.dataIndex === 'unit_price'),{ header: i18n._('Price') });
        Object.assign(this.columns.find((c) => c.dataIndex === 'position_discount_sum'),{ header: i18n._('Discount') });
        Object.assign(this.columns.find((c) => c.dataIndex === 'gross_price'),{ header: i18n._('Total') });

        const colModel = this.supr().getColumnModel.call(this);

        // manage discount field mode
        const checkDiscountFieldMode = () => {
            this.colModel.columns.find(c => c.dataIndex === 'position_discount_sum').editor.field.singleField =
                this.colModel.columns.find(c => c.dataIndex === 'position_discount_percentage').hidden;

        }
        colModel.on('hiddenchange', checkDiscountFieldMode)

        return colModel;
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

    setValue: function(value) {},
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

    /* needed for isFormField cycle */
    markInvalid: Ext.form.Field.prototype.markInvalid,
    clearInvalid: Ext.form.Field.prototype.clearInvalid,
    getMessageHandler: Ext.form.Field.prototype.getMessageHandler,
    getName: Ext.form.Field.prototype.getName,
    validate: function() { return true; }
});

Ext.reg('sales-position-gridpanel', PositionGridPanel)

Tine.widgets.form.FieldManager.register('Sales', 'Document_Offer', 'positions', {
    xtype: 'sales-position-gridpanel',
    recordClass: 'Sales.DocumentPosition_Offer',
    height: 500
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

export default PositionGridPanel;