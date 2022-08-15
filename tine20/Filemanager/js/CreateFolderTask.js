export default class CreateFolderTask {
    retry = 3;

    handle(args) {
        return new Promise(async (resolve, reject) => {
            await Tine.Filemanager.createNode(args.uploadId, 'folder', [], false)
                .then(async (response) => {
                    args.nodeData = response;
                    args.nodeData.status = 'complete';
                    const task = await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
                    
                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.update',
                        data: task.args.nodeData
                    });
        
                    return resolve(task);
                }).catch(async (e) => {
                    args.nodeData.status = (e.message === 'file exists') ? 'complete' : 'failed';
                    const task = await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
                    return resolve(task);
                });
        });
    }
}
