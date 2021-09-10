/*
 * Tine 2.0
 *
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux.grid');

const DDSortPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.extend(DDSortPlugin, Ext.util.Observable, {
    /*
     * @cfg {String} ddSortCol
     * if grid is sorted by this column drag/drop sorting of records is enabled
     */
    ddSortCol: null,

    sortInc: 10000,

    async init(gridPanel) {
        const me = this;
        this.grid = gridPanel;
        this.store = this.grid.store;
        await this.grid.afterIsRendered();

        this.grid.dropZone = new Ext.dd.DropZone(this.grid.getView().scroller, {
            grid: this.grid,
            ddSortCol: this.ddSortCol,
            app: this.app,
            ddGroup: this.grid.ddGroup,
            ddSortInfo: {
                destRow: '',
                destEdge: '',
                cls: ''
            },

            getTargetFromEvent: function(e) {
                return e.getTarget(this.grid.getView().rowSelector);
            },

            applySortInfoStyle: function(target, e) {
                var targetBox = Ext.fly(target).getBox(),
                    edge = e.getXY()[1] < targetBox.y + targetBox.height/2 ? 'above' : 'below',
                    cls = 'x-gird-drag-sort-' + edge;

                if (this.ddSortInfo.destRow != target || this.ddSortInfo.cls !== cls) {
                    if (this.ddSortInfo.destRow) {
                        Ext.fly(this.ddSortInfo.destRow).removeClass(this.ddSortInfo.cls);
                    }
                    Ext.fly(target).addClass(cls);
                    this.ddSortInfo.destRow = target;
                    this.ddSortInfo.destEdge = edge;
                    this.ddSortInfo.cls = cls;
                }
            },

            removeSortInfoStyle: function(target, e) {
                if (this.ddSortInfo.destRow) {
                    Ext.fly(this.ddSortInfo.destRow).removeClass(this.ddSortInfo.cls);
                }
                this.ddSortInfo.destRow = '';
                this.ddSortInfo.destEdge = '';
                this.ddSortInfo.cls = '';
            },

            onNodeEnter : function(target, dd, e, data) {
                this.applySortInfoStyle(target, e);
            },

            onNodeOut : function(target, dd, e, data) {
                this.removeSortInfoStyle(target, e);
            },

            onNodeOver : function(target, dd, e, data) {
                this.applySortInfoStyle(target, e);

                var sortState = this.grid.getStore().getSortState();
                return sortState.field != this.ddSortCol ?
                    Ext.dd.DropZone.prototype.dropNotAllowed:
                    Ext.dd.DropZone.prototype.dropAllowed;
            },

            onNodeDrop : function(target, dd, e, data) {
                this.applySortInfoStyle(target, e);

                var store = this.grid.getStore(),
                    sortState = store.getSortState(),
                    sortColumn = this.grid.getColumnModel().getColumnById(this.ddSortCol),
                    selectedIds = _.map(data.selections, function(r) {return r.getId()}),
                    ref = this.grid.getStore().getAt(this.grid.getView().findRowIndex(target)),
                    edge = this.ddSortInfo.destEdge;

                this.removeSortInfoStyle(target, e);

                if (sortState.field != this.ddSortCol) {
                    Ext.Msg.alert(
                        String.format(i18n._('Please Sort by "{0}"'), sortColumn.header),
                        String.format(i18n._('To use manual sorting, you need to sort by column "{0}"'), sortColumn.header)
                    );

                    return false;
                }

                me.applySorting(data.selections, ref, edge);
                me.store.applySort();
                _.defer(() => { me.grid.getView().refresh(); });

                //
                // if (store.proxy) {
                //     let pos = refIdx + (refEdge == 'below' ? 1 : 0);
                //
                //     const aboveRef = _.reduce(data.selections, (above, record) => {
                //         return above.concat(store.indexOf(record) < pos ? [record] : []);
                //     }, []);
                //
                //     pos = pos - aboveRef.length;
                //
                //     store.remove(data.selections);
                //     store.insert(pos, data.selections);
                //
                //     if (me.usePagingToolbar) {
                //         me.pagingToolbar.refresh.disable();
                //     }
                //
                //     // @TODO support shaddow sort col (like sort for prio)
                //     // with this idea we don't need a server side method
                //
                //     /* local sort / unpaged results only! (as we don't know values not shown in current page)
                //     const aboveRecord = store.getAt(pos-1);
                //     const aboveSortVal = aboveRecord ? aboveRecord.get(this.ddSortCol) : store.getAt(pos).get(this.ddSortCol)-1;
                //     const belowRecord = store.getAt(pos);
                //     const belowSortVal = belowRecord ? belowRecord.get(this.ddSortCol) : store.getAt(pos-1).get(this.ddSortCol)+1;
                //     const diffValue = belowSortVal-aboveSortVal;
                //     const incrValue = diffValue/(data.selections.length+1);
                //     _.each(data.selections, (record, idx) => {
                //         record.set(this.ddSortCol, aboveSortVal-(idx+1)*incrValue);
                //     });
                //     */
                //
                //     store.proxy.setSortValue(this.ddSortCol, selectedIds, refEdge, refRecord.getId());
                //
                // } else {
                //     return false;
                // }

                return true;
            },


        });
    },

    applySorting: function(records, ref, edge) {
        const refIdx = this.store.indexOf(ref);
        const refSortVal = ref.get(this.ddSortCol);
        const sign = edge === 'above' ? -1 : +1;
        const neighbour = this.store.getAt(refIdx+sign);
        const sortdiff = neighbour ? Math.round(Math.abs(refSortVal-neighbour.get(this.ddSortCol)) / (records.length+1)) : this.sortInc;
        [].concat(records)[sign < 0 ? 'reverse' : 'unique']().forEach((record, idx) => {
            record.set(this.ddSortCol, refSortVal+(idx+1)*sign*sortdiff);
        });
    }

});

export default DDSortPlugin;
