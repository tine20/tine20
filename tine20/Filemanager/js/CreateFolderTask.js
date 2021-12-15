export default class CreateFolderTask {
    retry = 3;

    handle(args) {
        return new Promise(async (resolve, reject) => {
            if (args.existNode) {
                args.nodeData.status = 'complete';
                await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
    
                resolve(true);
            } else {
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
            
                        return resolve(true);
                    }).catch(async (e) => {
                        if (e.message === 'file exists') {
                            args.nodeData.status = 'complete';
                            await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
                
                            return resolve(true);
                        } else {
                            // retry
                            return resolve(false);
                        }
                    });
            }
        });
    }
}
