import validatorFactory from './tierValidatorFactory'
import getTierTypes from './tierTypes'

export default Ext.extend(Ext.form.ComboBox, {
    currentNode: null,
    
    // private
    mode: 'local',
    displayField: 'label',
    valueField: 'tierType',
    forceSelection: true,
    triggerAction: 'all',
    
    initComponent: async function() {
        this.app = Tine.Tinebase.appMgr.get('EFile');
        
        const label = Tine.Filemanager.Model.Node.getModelConfiguration().fields["efile_tier_type"].label;
        this.fieldLabel = this.app.i18n._hidden(label);
        
        this.tierTypes = await getTierTypes();
        this.store = new Ext.data.JsonStore({
            fields: Object.keys(this.tierTypes[0]),
            data: this.tierTypes
        });
        
        this.supr().initComponent.call(this);
    },

    setNode: function(nodeRecord) {
        const tierType =  _.get(nodeRecord, 'data.efile_tier_type', _.get(nodeRecord, 'efile_tier_type'));
        this.setValue(tierType);

        this.currentNode = _.get(nodeRecord, 'data');
    },

    doQuery: async function(q, forceAll) {
        const currentNode = _.get(this, 'currentNode', {});
        if (currentNode) {
            this.store.filter(this.valueField, '-');
            this.onBeforeLoad();
            this.expand();
            
            const [parentNode, childNodes, validator] = await Promise.all([
                Tine.Filemanager.getNode(currentNode.parent_id),
                Tine.Filemanager.searchNodes([{field: 'path', operator: 'equals', value: currentNode.path}]),
                validatorFactory()
            ]);

            this.store.filterBy((tierType) => {
                return validator.validate(_.assign({}, currentNode, {
                    efile_tier_type: tierType.get('tierType'),
                    oldrecord: currentNode,
                    parent: parentNode,
                    children: childNodes
                }));
            });
            this.onLoad();
        }
    }
    
});
