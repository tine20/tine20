export default class CreateFolderTask {
    retry = 3;

    handle(args) {
        return new Promise(async (resolve, reject) => {
            if (args.existNode) {
                resolve(true);
            }
            
            await Tine.Filemanager.createNode(args.uploadId, 'folder', [], false)
                .then(async (response) => {
                    Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
                    response.status = 'complete';

                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.update',
                        data: response
                    });

                    resolve(true);
                }).catch(async (e) => {
                    if (e.message === 'file exists') {
                        return resolve(true);
                    } else {
                        // retry
                        return resolve(false);
                    }
                });
        });
    }
}
