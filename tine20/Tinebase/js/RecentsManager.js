Ext.ns('Tine.Tinebase')

Tine.Tinebase.RecentsManager = function(config) {
    Ext.apply(this, config);
    
    if (! this.stateId) {
        this.stateId = [this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), this.domain, 'recents'].join('-').toLowerCase();
    }
}

Tine.Tinebase.RecentsManager.prototype = {
    /**
     * @cfg {Tine.data.Record} recordClass
     */
    recordClass: null,
    
    /**
     * @cfg {string} this.domain recents this.domain
     */
    domain: 'all',
    
    addRecentRecord: function (record) {
        var id = record.getId(),
            recents = this.getRecentRecords(record.constructor, this.domain),
            dtSelect = new Date().getTime();
        
        // check if record is already in current recents
        Ext.each(recents, function(r) {
            if (r.getId() == id) {
                recents.remove(r);
                return false;
            }
        }, this);
        
        record.dtSelect = dtSelect;
        recents.push(record);
        
        recents.sort(function(a, b) {return a.dtSelect > b.dtSelect});
        recents.slice(0, 10);
        
        this.saveRecentRecords(recents, record.constructor, this.domain);
    },
    
    getRecentRecords: function() {
        var stateId = this.getStateId(this.recordClass, this.domain), 
            recentsData = Ext.state.Manager.get(stateId),
            rs = [];
            
        if (Ext.isArray(recentsData)) {
            Ext.each(recentsData, function(recentData) {
                var recent = new this.recordClass(recentData);
                recent.dtSelect = recentData.dtSelect
                rs.push(recent);
            }, this);
        }
        
        return rs;
    },
    
    /**
     * @private
     */
    saveRecentRecords: function(recents) {
        var stateId = this.getStateId(this.recordClass, this.domain),
            recentsData = []
        
        Ext.each(recents, function(r) {
            var data = r.data;
            data['dtSelect'] = r.dtSelect;
            
            recentsData.push(data);
        }, this);
        
        Ext.state.Manager.set(stateId, recentsData);
        
    },
    
    getStateId: function() {
        return this.stateId;
    }
    
};