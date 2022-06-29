import validatorFactory from './tierValidatorFactory'

validatorFactory().then((validator) => {
    Tine.Filemanager.nodeActionsMgr.registerConstraintsProvider((action, targetNode, sourceNodes, options) => {
        const path = _.get(targetNode, 'data.path', _.get(targetNode, 'path'));
        const basePaths = Array.from(Tine.Tinebase.configManager.get('basePath', 'EFile'));

        const parentTierType = _.get(targetNode, 'data.efile_tier_type', _.get(targetNode, 'efile_tier_type'))
            || (basePaths.indexOf(path) > -1 && path !== '/shared/' ? 'masterPlan' : null);
        
        let isAllowed = true;
        if (parentTierType && ['create', 'move'].indexOf(action) >= 0) {
            isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                const nodeType = _.get(node, 'data.type', _.get(node, 'type'));
                const nodeTierType = _.get(node, 'data.efile_tier_type', _.get(node, 'efile_tier_type'))
                    || nodeType === 'file' ? 'document' : ''
                    || nodeType === 'folder' && action === 'move' ? 'documentDir' : '';
                
                return allowed && !!validator.validate({
                    parent: targetNode.data,
                    type: nodeType,
                    efile_tier_type: nodeTierType
                });
            }, true);
        }

        if (action === 'copy') {
            isAllowed = false;
        }
        
        if (action === 'move') {
            isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                const nodeTierType =_.get(node, 'data.efile_tier_type', _.get(node, 'efile_tier_type'));

                // things must stay inside eFile
                allowed = allowed && (!nodeTierType || !!parentTierType);
                
                // moving of 'masterPlan', 'fileGroup', 'file' is not allowed
                return allowed && (!nodeTierType || ['masterPlan', 'fileGroup', 'file'].indexOf(nodeTierType) < 0);
            }, true);
        }
        
        return isAllowed;
    });
});
