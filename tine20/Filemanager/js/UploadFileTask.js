export default class UploadFileTask {
    retry = 3;
    
    async handle(args) {
        console.log(`FilemanagerUploadFileTask on window : ${postal.instanceId()}`);
        // Should return true or false value (boolean) that end of the all process
        // If process rejected, current task will be removed from task pool in worker.
        return new Promise(async (resolve, reject) => {
            const upload = Tine.Tinebase.uploadManager.getUpload(args.uploadId);
            let nodeData = [];

            if (!upload) {
                reject('upload is not found!');
            }

            upload.on('uploadprogress', async (upload, fileRecord) => {
                nodeData.status = fileRecord.get('status');
                nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${fileRecord.get('type')}; progress=${fileRecord.get('progress')}`;

                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Filemanager.Node.update',
                    data: nodeData
                });
                
            });

            upload.on('uploadcomplete', async (upload, fileRecord) => {
                nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${fileRecord.get('type')}; progress=${fileRecord.get('progress')}`;
                
                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Filemanager.Node.update',
                    data: nodeData
                });
                
                //need to update grid with existing node id
                const nodeDataNew = await Tine.Filemanager.createNode(args.uploadId, fileRecord.get('type'), fileRecord.get('id'), true);
                nodeDataNew.progress = 100;
                nodeDataNew.status = 'complete';
                
                fileRecord.set('contenttype', nodeDataNew.contenttype);
                fileRecord.set('created_by', Tine.Tinebase.registry.get('currentAccount'));
                fileRecord.set('creation_time',  nodeDataNew.creation_time);
                fileRecord.set('revision',   nodeDataNew.revision);
                fileRecord.set('last_modified_by',  nodeDataNew.last_modified_by);
                fileRecord.set('last_modified_time',  nodeDataNew.last_modified_time);
                fileRecord.set('name',  nodeDataNew.name);
                fileRecord.set('path',  nodeDataNew.path);
                fileRecord.set('size',  nodeDataNew.size);
                fileRecord.set('status', 'complete');
                fileRecord.set('progress', 100);
                
                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Filemanager.Node.update',
                    data: nodeDataNew
                });
                
                Tine.Tinebase.uploadManager.unregisterUpload(args.uploadId);
                resolve(true);
            });
            
            upload.on('uploadfailure', (upload, fileRecord) => {
                Tine.Tinebase.uploadManager.unregisterUpload(args.uploadId);
                reject('upload failed');
            });

            try {
                const type = `vnd.adobe.partial-upload; final_type=${args.nodeData.type}`;
                nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], _.get(args, 'overwrite', false));
                Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
            } catch (e) {
                if (e.data.code === 403) {
                    window.postal.publish({
                        channel: "recordchange",
                        topic: 'Filemanager.Node.delete',
                        data: args.nodeData
                    });

                    Tine.Tinebase.uploadManager.removeVirtualNode(args.uploadId);
                    reject(e.message);
                    throw e;
                }
                
                if (e.message === 'file exists') {
                    const button = await new Promise((resolve) => {
                        Tine.widgets.dialog.FileListDialog.openWindow({
                            modal: true,
                            allowCancel: false,
                            height: 180,
                            width: 300,
                            title: 'Do you want to overwrite this file?',
                            text: args.nodeData.name ,
                            scope: this,
                            handler: async function (button) {
                                resolve(button);
                            }
                        });
                    });
                    
                    if (button === 'no') {
                        return resolve(true);
                    }
                    
                    nodeData = await Tine.Filemanager.getNode(_.get(e, 'data.existingnodesinfo[0].id'));
                    nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${nodeData.contenttype}; progress=0`;
                    nodeData.status = 'pending';
                    args.overwrite = true;
                } 
                
                if (e.message === 'Node not found') {
                    const type = `vnd.adobe.partial-upload; final_type=${args.nodeData.type}; progress=0`;
                    nodeData = await Tine.Filemanager.createNode(args.uploadId, type, [], _.get(args, 'overwrite', false));
                }
            }
            
            window.postal.publish({
                channel: "recordchange",
                topic: 'Filemanager.Node.update',
                data: nodeData
            });
            try {
                upload.upload();
            } catch (e) {
                reject('upload failed');
            }
        });
    }
}
