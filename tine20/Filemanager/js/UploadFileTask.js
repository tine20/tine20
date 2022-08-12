export default class UploadFileTask {
    retry = 3;

    async handle(args) {
        console.log(`FilemanagerUploadFileTask on window : ${postal.instanceId()}`);
        // Should return true or false value (boolean) that end of the all process
        // If process rejected, current task will be removed from task pool in worker.
        return new Promise(async (resolve, reject) => {
            const upload = await Tine.Tinebase.uploadManager.getUpload(args.uploadId, args.fileObject);
            
            const updateTask = async function (args) {
                if (Tine.Tinebase.uploadManager.getBatchUploadAction(args.batchID) === 'stop') {
                    if (! upload.isPaused()) {
                        upload.setPaused(true);
                        return;
                    }
                }
                
                try {
                    const task = await Tine.Tinebase.uploadManager.updateTaskByArgs(args);
    
                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.update',
                        data: task?.args?.nodeData ?? args.nodeData
                    });
                } catch (e) {
                    await Tine.Tinebase.uploadManager.unregisterUpload(args.uploadId);
                    reject('update task to storage failed');
                }
            };
    
            const getResolvedContentType = function (fileType , progress) {
                return `vnd.adobe.partial-upload; final_type=${fileType}; progress=${progress}`;
            };
    
            if (!upload) {
                args.nodeData.status = 'failed';
                await updateTask(args);
                reject('upload is not found!');
            } else {
                //todo : paused and resume feature might be implemented in the future , but we stop and cancel upload task for now
                upload.on('uploadpaused', async () => {
                    if (! args.existNode) {
                        await Tine.Filemanager.searchNodes([
                            {field: 'path', operator: 'equals', value: args.targetFolderPath},
                            {field: 'contenttype', operator: 'contains', value: 'vnd.adobe.partial-upload'},
                            {field: 'name', operator: 'equals', value: args.nodeData.name},
                        ]).then(async (result) => {
                            if (result?.totalcount === 1) {
                                await Tine.Filemanager.deleteNodes(args.nodeData.path)
                                    .catch((e) => {
                                        reject('remove empty node from server failed');
                                    });
                            } else {
                                reject('search empty node from server failed');
                            }
                        });
                    }
        
                    args.nodeData.status = 'cancelled';
                    await updateTask(args);
        
                    resolve(true);
                });
    
                upload.on('uploadprogress', async (upload, fileRecord) => {
                    args.nodeData.status = fileRecord.get('status');
                    args.nodeData.progress = fileRecord.get('progress');
                    args.nodeData.contenttype = getResolvedContentType(fileRecord.get('type'), fileRecord.get('progress'));
                    await updateTask(args);
                });
    
                upload.on('uploadcomplete', async (upload, fileRecord) => {
                    args.nodeData.contenttype = getResolvedContentType(fileRecord.get('type'), fileRecord.get('progress'));
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
                    const type = getResolvedContentType(args.nodeData.type, -1);
                    args.nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], false);
                    args.nodeData.status = 'uploading';
                    args.nodeData.size = args?.fileSize;
                    await updateTask(args);
                } catch (e) {
                    if (e.data.code === 403) {
                        args.nodeData.status = 'failed';
                        await updateTask(args);
            
                        resolve(true);
                        throw e;
                    }
        
                    if (e.message === 'file exists') {
                        const existingNode = _.get(e, 'data.existingnodesinfo[0]');
                        if (! existingNode.contenttype.includes('vnd.adobe.partial-upload')  && args.overwrite === false) {
                            const button = await new Promise((resolve) => {
                                const action = Tine.Tinebase.uploadManager.getBatchUploadAction(args.batchID);
                                if (action !== '') {
                                    resolve(action);
                                } else {
                                    Tine.Filemanager.DuplicateFileUploadDialog.openWindow({
                                        uploadId: args.uploadId,
                                        batchID: args.batchID,
                                        fileName: args.nodeData.name,
                                        fileType: args.nodeData.type,
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
                    
                                return resolve(true);
                            }
                        }
            
                        args.nodeData = await Tine.Filemanager.getNode(existingNode.id);
                        args.nodeData.contenttype = getResolvedContentType(args.nodeData.contenttype, 0);
                        args.nodeData.status = 'uploading';
                        args.overwrite = true;
                    }
        
                    if (e.message === 'Node not found') {
                        const type = getResolvedContentType(args.nodeData.type, 0);
                        args.nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], _.get(args, 'overwrite', false));
                    }
                }
    
                await updateTask(args);
    
                try {
                    upload.upload();
                } catch (e) {
                    reject('upload failed');
                }
            }
        });
    }
}
