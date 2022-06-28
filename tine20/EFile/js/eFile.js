import '../styles/efile.less'
import './Application'
import './nodeConstraintsProvider'
import './createEFileAction'
import './nodeEFileHookPanel'
import './nodeFileMetadataPanel'

Promise.all([
    Tine.Tinebase.ApplicationStarter.isInitialised(),
    // appMgr comes from parent window ;-(
    Tine.Tinebase.appMgr.isInitialised('Filemanager')
]).then(() => {
    const basePaths = Array.from(Tine.Tinebase.configManager.get('basePath', 'EFile'));
    Tine.Filemanager.Model.Node.registerStyleProvider((node) => {
        const path = _.get(node, 'data.path', node.path)
        const tierType = _.get(node, 'data.efile_tier_type', node.efile_tier_type)
            || (basePaths.indexOf(path) > -1 && path !== '/shared/' ? 'masterPlan' : null);
        
        if (tierType && tierType !== 'document') {
            return 'efile-tier efile-tiertype-' + tierType;
        }
    });
});
