export default async () => {
    await Tine.Tinebase.appMgr.isInitialised('EFile');
    const app = Tine.Tinebase.appMgr.get('EFile');
    
    return [{
        tierType: 'masterPlan',
        label: app.i18n._('Master Plan'),
        nodeType: 'folder'
    }, {
        tierType: 'fileGroup',
        label: app.i18n._('File Group'),
        nodeType: 'folder'
    }, {
        tierType: 'file',
        label: app.i18n._('File'),
        nodeType: 'folder'
    }, {
        tierType: 'subFile',
        label: app.i18n._('Sub File'),
        nodeType: 'folder'
    }, {
        tierType: 'case',
        label: app.i18n._('Case'),
        nodeType: 'folder'
    }, {
        tierType: 'documentDir',
        label: app.i18n._('Document Directory'),
        nodeType: 'folder'
    }, {
        tierType: 'document',
        label: app.i18n._('Document'),
        nodeType: 'file'
    }]
} 
