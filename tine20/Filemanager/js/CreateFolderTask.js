export default class CreateFolderTask {
    retry = 3;

    handle(args) {
        return new Promise(async (resolve, reject) => {
            const uploadManager = Tine.Tinebase.uploadManager;
            
            await Tine.Filemanager.createNode(args.uploadId, 'folder', [], false)
                .then(async (response) => {
                    args.nodeData = response;
                    args.nodeData.status = uploadManager.status.COMPLETE;
                    const task = await uploadManager.updateTaskByArgs(args);
                    
                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.update',
                        data: task.args.nodeData
                    });                  
                    
                    return resolve(true);
                }).catch(async (e) => {
                    args.nodeData.status = (e.message === 'file exists') ? uploadManager.status.COMPLETE : uploadManager.status.FAILURE;
                    const task = await uploadManager.updateTaskByArgs(args);
                    return resolve(true);
                });
        });
    }
}
