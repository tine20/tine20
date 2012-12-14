Ext.ns('Ext.ux.form');

/**
 * make a combo box recent aware
 *  => present recents on empty query
 * 
 */
Ext.ux.form.ComboBoxRecentsPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.form.ComboBoxRecentsPlugin.prototype = {
    
    recordClass: null,
    domain: 'all',
    
    init: function(cmp) {
        this.cmp = cmp;
        
        this.recentsManager = new Tine.Tinebase.RecentsManager({
            recordClass: this.recordClass,
            domain: this.domain
        });
        
        // @TODO have a interface idea for 'other'
//        this.cmp.doQuery = this.cmp.doQuery.createInterceptor(this.onBeforeQuery, this);
        
        this.cmp.on('select', this.onSelect, this);
    },
    
    /**
     * fill store with recents for empty query
     * @param {String} q
     */
    onBeforeQuery: function(q) {
        if (! q) {
            var store = this.cmp.store,
                recents = this.recentsManager.getRecentRecords();
                
            if (recents) {
                var other = {};
                other[recordType.getMeta('idProperty')] = 'other';
                other[recordType.getMeta('titleProperty')] = String.format(_('choose other {0}...'), recordType.getRecordsName());
                recents.push(new recordClass(other));
                
                this.cmp.store.loadRecords(recents, {add: false}, true);
                return false;
            }
        }
    },
    
    onSelect: function(cmp, record, index) {
        if (record && Ext.isFunction(record.get)) {
            this.recentsManager.addRecentRecord(record, this.domain);
        }
    },
    
    getStateId: function(model, domain) {
        return [model.getMeta('appName'), model.getMeta('modelName'), domain, 'recents'].join('-').toLowerCase();
    }
    
};

Ext.preg('Ext.ux.form.ComboBoxRecentsPlugin', Ext.ux.form.ComboBoxRecentsPlugin);