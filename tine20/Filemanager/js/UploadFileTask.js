export default class UploadFileTask {
    retry = 3;

    async handle(args) {
        console.log(`FilemanagerUploadFileTask on window : ${postal.instanceId()}`);
        // Should return true or false value (boolean) that end of the all process
        // If process rejected, current task will be removed from task pool in worker.
        return new Promise(async (resolve, reject) => {
            const upload = Tine.Tinebase.uploadManager.getUpload(args.uploadId);

            if (!upload) {
                reject('upload is not found!');
            }
    
            const updateTask = async function (args) {
                try {
                    const task = await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
                    if (task) {
                        window.postal.publish({
                            channel: "recordchange",
                            topic: 'Filemanager.Node.update',
                            data: task.args.nodeData
                        });
                    } else {
                        if (Tine.Tinebase.uploadManager.applyBatchAction[args.batchID] === 'stop') {
                            args.nodeData.status = 'cancelled';
                            window.postal.publish({
                                channel: "recordchange",
                                topic: 'Filemanager.Node.update',
                                data: args.nodeData
                            });
                            return resolve(true);
                        }
                    }
                } catch (e) {
                    Tine.Tinebase.uploadManager.unregisterUpload(args.uploadId);
                    reject('update task to storage failed');
                }
            };

            upload.on('uploadprogress', async (upload, fileRecord) => {
                args.nodeData.status = fileRecord.get('status');
                args.nodeData.progress = fileRecord.get('progress');
                args.nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${fileRecord.get('type')}; progress=${fileRecord.get('progress')}`;
                await updateTask(args);
            });

            upload.on('uploadcomplete', async (upload, fileRecord) => {
                args.nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${fileRecord.get('type')}; progress=${fileRecord.get('progress')}`;
                args.nodeData.progress = fileRecord.get('progress');
    
                await updateTask(args);

                //need to update grid with existing node id
                args.nodeData = await Tine.Filemanager.createNode(args.uploadId, fileRecord.get('type'), fileRecord.get('id'), true);
                args.nodeData.progress = 100;
                args.nodeData.status = 'complete';
                
                await updateTask(args);
    
                resolve(true);
            });

            upload.on('uploadfailure', async (upload, fileRecord) => {
                args.nodeData.status = 'failed';
                await updateTask(args);
                reject('upload failed');
            });

            try {
                const type = `vnd.adobe.partial-upload; final_type=${args.nodeData.type}`;
                args.nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], args?.overwrite);
                args.nodeData.status = 'uploading';
                args.nodeData.size = args?.fileSize;

                Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
            } catch (e) {
                if (e.data.code === 403) {
                    args.nodeData.status = 'failed';
                    const task = await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
    
                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.delete',
                        data: task.args.nodeData
                    });

                    Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
                    
                    reject(e.message);
                    throw e;
                }

                if (e.message === 'file exists') {
                     const button = await new Promise((resolve) => {
                         if (Tine.Tinebase.uploadManager.applyBatchAction[args.batchID]) {
                             const button = Tine.Tinebase.uploadManager.applyBatchAction[args.batchID];
                             resolve(button);
                         } else {
                             Tine.Filemanager.DuplicateFileUploadDialog.openWindow({
                                 uploadId: args.uploadId,
                                 batchID: args.batchID,
                                 fileName: args.nodeData.name,
                                 fileType: 'file',
                                 scope: this,
                                 handler: async function (button) {
                                     resolve(button);
                                 }
                             });
                         }
                     });

                    if (button === 'stop' || button === 'skip') {
                        args.nodeData.status = 'cancelled';
                        await updateTask(args);
    
                        Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
                        return resolve(true);
                    }

                    args.nodeData = await Tine.Filemanager.getNode(_.get(e, 'data.existingnodesinfo[0].id'));
                    args.nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${args.nodeData.contenttype}; progress=0`;
                    args.nodeData.status = 'uploading';
                    args.overwrite = true;
                }

                if (e.message === 'Node not found') {
                    const type = `vnd.adobe.partial-upload; final_type=${args.nodeData.type}; progress=0`;
                    args.nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], _.get(args, 'overwrite', false));
                }
            }
    
            await updateTask(args);

            try {
                upload.upload();
            } catch (e) {
                reject('upload failed');
            }
        });
    }
}
