Ext.ns('Ext.ux.file');

/**
 * a simple file upload manager
 * collects all uploads
 *
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.UploadManager
 */
Ext.ux.file.UploadManager = function(config) {
    Ext.apply(this, config);
    Ext.ux.file.UploadManager.superclass.constructor.apply(this, arguments);

    this.instanceId = Tine.Tinebase.data.Record.generateUID(5);
    const bc = new BroadcastChannel('Ext.ux.file.UploadManager');
    bc.onmessage = (event) => {
        if (event.data.cmd === 'isRunning' && (! event.data.instanceId || event.data.instanceId === this.instanceId)) {
            bc.postMessage({ instanceId: this.instanceId, status: 'running' });
        }
    }
    
    this.taskLimit = 1000;
    this.channelPrefix = '/Tine.Tinebase.uploads';
    this.uploads = {};
    this.fileObjects = [];
    this.completeTaskPaths = [];
    this.supportFileHandle = !Ext.isGecko && !Ext.isIE;
    this.uploadingTasks = [];
    
    this.status = {
        CANCELLED: 'cancelled',
        COMPLETE: 'complete',
        ERROR: 'error',
        FAILURE: 'failed',
        PENDING: 'pending',
        UPLOADING: 'uploading',
    }
    
    this.tag = {
        ADDED: 'added',
        ACTIVE: 'active',
        CANCELLED: 'cancelled',
        REMOVE: 'remove',
        INACTIVE: 'inactive',
        COMPLETE: 'complete',
    }
    
    this.action = {
        REPLACE: 'replace',
        SKIP: 'skip',
        STOP: 'stop',
    }
    
    import(/* webpackChunkName: "Tinebase/js/UploadQueue" */ './UploadQueue')
        .then(({default: UploadQueue}) => {
            this.queue = new UploadQueue({
                storage: "localforage",
                prefix: this.channelPrefix,
                timeout: 1500,
                limit: this.taskLimit,
                principle: 'fifo',
            });
            this.mainChannel = this.queue.create(`Channel-Upload-main`);
            this.mainQueueChannel = this.queue.create(`${this.instanceId}-Queue`);  
        }
    );
    
    this.addEvents(
        /**
        * @event uploadcomplete
        * Fires when the upload was done successfully
        * @param {Ext.ux.file.Upload} this
        * @param {Ext.Record} Ext.ux.file.Upload.file
        */
        'uploadcomplete',
        /**
        * @event uploadfailure
        * Fires when the upload failed
        * @param {Ext.ux.file.Upload} this
        * @param {Ext.Record} Ext.ux.file.Upload.file
        */
        'uploadfailure',
        /**
        * @event uploadprogress
        * Fires on upload progress (html5 only)
        * @param {Ext.ux.file.Upload} thistasks
        * @param {Ext.Record} Ext.ux.file.Upload.file
        * @param {XMLHttpRequestProgressEvent}
        */
        'uploadprogress',
        /**
        * @event uploadstart
        * Fires on upload progress (html5 only)
        * @param {Ext.ux.file.Upload} this
        * @param {Ext.Record} Ext.ux.file.Upload.file
        */
        'uploadstart',
        /**
        * @event uploadstart
        * Fires on upload progress (html5 only)
        * @param {Ext.ux.file.Upload} this
        * @param {Ext.Record} Ext.ux.file.Upload.file
        */
        'update'
    );
};

Ext.extend(Ext.ux.file.UploadManager, Ext.util.Observable, {

        /**
         * current running uploads
         */
        runningUploads: 0,

        /**
         * max channel count, also the number of allowed concurrent uploads
         */
        maxChannels: 5,

        /**
         * @cfg (String) upload id prefix
         */
        uploadIdPrefix: "tine-upload-",

        /**
         * holds the uploads
         */
        uploads: null,

        /**
         * counts session uploads
         */
        uploadCount: 0,

        /**
         * if main channel is adding tasks to worker channels
         */
        adding: false,

        /**
         *  store to apply to all status og every batch uploads
         */
        applyBatchAction: [],
    
        /**
         *  store to apply to all status og every batch uploads
         */
        instances: [],
    
    
    async isRunning(instanceId) {
        const bc = new BroadcastChannel('Ext.ux.file.UploadManager');
        const isRunning = await Promise.race([
            new Promise (resolve => {
                bc.onmessage = (event) => {
                    if (event.data.instanceId === instanceId && event.data.status === 'running') {
                        resolve(true);
                    }
                }
                bc.postMessage({cmd: 'isRunning', instanceId});
            }),
            new Promise(resolve => {window.setTimeout(_.partial(resolve, false), 200)})
        ]);
        bc.close();

        return isRunning;
    },
    
    async stopWorkingChannels(instanceId) {
        const promises = [];
        
        for (let i = 0; i < this.maxChannels; i++) {
            promises.push(new Promise (async (resolve, reject) => {
                const channelInCurrentQueue = await this.queue.channel(`${instanceId}-${i}`);
                if (channelInCurrentQueue) {
                    await channelInCurrentQueue.forceStop();
                }

                await this.mainChannel.storage.clear(`${instanceId}-${i}`);
                resolve();
            }));
        }
        await this.mainChannel.storage.clear(`${instanceId}-Queue`);
        await Promise.allSettled(promises);
    },
    
    async getEmptyWorkingChannel() {
        let result = null;
        const promises = [];
        
        for (let i = 0; i < this.maxChannels; i++) {
            promises.push(new Promise (async (resolve, reject) => {
                let channel = await this.queue.channel(`${this.instanceId}-${i}`);
                
                if (!channel){
                    channel = await this.queue.create(`${this.instanceId}-${i}`);
                    channel.on('active:after', async (result) => {
                        this.completeTaskPaths.push(result.args.uploadId);
                        this.unregisterUpload(result.args.uploadId);
                        await this.addTaskToWorkerChannel();
                    });
                    channel.on("error", (err) => {
                        console.log(err);
                    });
                }
                
                const count = await channel.count();
                
                if (count === 0) {
                    if(!channel.running) {
                        await channel.start();
                    }
                    if (!result) {
                        result = channel;
                    }
                    resolve();
                } else {
                    reject();
                }
            }));
        }
        
        await Promise.any(promises).then().catch(() => {});
        return result;
    },

    onInitFs(fs)
    {
        fs.root.getFile('log.txt', {create: true, exclusive: true}, function(fileEntry) {

            fileEntry.isFile === true;
            fileEntry.name === 'log.txt';
            fileEntry.fullPath === '/log.txt';

        }, this.errorHandler);

    },

    errorHandler(e)
    {
        var msg = '';

        switch (e.code) {
            case FileError.QUOTA_EXCEEDED_ERR:
                msg = 'QUOTA_EXCEEDED_ERR';
                break;
            case FileError.NOT_FOUND_ERR:
                msg = 'NOT_FOUND_ERR';
                break;
            case FileError.SECURITY_ERR:
                msg = 'SECURITY_ERR';
                break;
            case FileError.INVALID_MODIFICATION_ERR:
                msg = 'INVALID_MODIFICATION_ERR';
                break;
            case FileError.INVALID_STATE_ERR:
                msg = 'INVALID_STATE_ERR';
                break;
            default:
                msg = 'Unknown Error';
                break;
        }

        console.log('Error: ' + msg);
    },
    
    /**
     * check batch upload complete
     *
     * update icon in main menu, if uploads with same batch id are complete
     */ 
    async checkBatchUploadComplete() {
        const allTasks = await this.getAllTasks();
        const actions = Tine.Tinebase.MainScreen.getMainMenu().getActionByPos(55);
        const incompleteTasks = allTasks.filter(t => this.isInComplete(t.status));
        actions[0]?.update(allTasks, incompleteTasks.length !== 0);
    },
    
    isInComplete(status) {
        return [this.status.PENDING, this.status.UPLOADING].includes(status);
    },
    
    /**
     * restart failed uploads
     *
     */
    async restartFailedUploads(tasks) {
        const failedFileUploadTasks = [];
        const permissionPromises = [];
        const folderDepTasks = [];
        const folderEntryPermissions = [];
        const allTasks = await this.getAllTasks();
    
        tasks.forEach(task => {
            if (task.handler === 'FilemanagerCreateFolderTask') {
                folderDepTasks.push(task);
            }
            if (task.handler === 'FilemanagerUploadFileTask' && this.supportFileHandle) {
                const promise = new Promise(async (resolve) => {
                    const fileHandle = task.args.fileObject;
                    const folderDeps = task.folderDependencies;
                    const entry = folderDeps[0] ?? 'root';
                    
                    if (!typeof fileHandle.queryPermission === 'function'
                        || !typeof fileHandle.getFile === 'function') 
                    {
                        resolve('skip');
                    }
                    // collect folder entries
                    if ((entry && !folderEntryPermissions.includes(entry)) || !entry) {
                        folderEntryPermissions[entry] = '';
                    }
                    // collect uniq folder deps task for upload task
                    folderDeps.forEach(depPath => {
                        const task = allTasks.find(t => t.label === depPath);
                        const existDepTask = folderDepTasks.find(t => t.label === depPath);
                        if (!existDepTask && task) {
                            folderDepTasks.push(task);
                        }
                    });
                    // get granted entry permission
                    if (folderEntryPermissions[entry] === '') {
                        const permission = await fileHandle.queryPermission();
                        folderEntryPermissions[entry] = permission === 'granted' ? permission : await fileHandle.requestPermission({});
                    }
                    // get file after we got granted entry permission
                    if (folderEntryPermissions[entry] === 'granted') {
                        this.fileObjects[task.args.uploadId] = await fileHandle.getFile();
                        failedFileUploadTasks.push(task);
                    }
        
                    resolve(folderEntryPermissions[entry]);
                });
                
                permissionPromises.push(promise);
            }
        });
        
        await Promise.all(permissionPromises).then(async (result) => {
            await this.resetTasks(folderDepTasks.concat(failedFileUploadTasks) , this.instanceId);
            await this.addTaskToWorkerChannel();
        });
    },
    
    /**
     * reset deprecated tasks with upload pending configs
     *
     * - force overwrite
     */ 
    async resetTasks(tasks, instanceId) {
        await this.removeChannelsByTasks(tasks);
    
        tasks.forEach(t => {
            t.args.overwrite = true;
            t.args.nodeData.status = this.status.PENDING;
            t.status = this.status.PENDING;
            t.tag = t.folderDependencies.length > 0 ? this.tag.INACTIVE : this.tag.ACTIVE;
            t.instanceId = instanceId;
        });
    
        await this.mainChannel.storage.updateBatch(tasks);
    },
    
    /**
     * update task by upload id
     *
     * - keep task up to date in storage
     * - args must have uploadId
     */
    async updateTaskByArgs(args, saveToStorage = true) {
        try {
            const idx = _.findIndex(this.uploadingTasks, {'_id' : args.taskId});
            if (idx === -1) return null;

            args.nodeData.last_upload_time = new Date().toJSON();
            const diff = {args: args};
            diff.status = diff.args.nodeData.status;
            diff.reason = diff.args.nodeData.reason;
            
            if (diff.status === this.status.COMPLETE) {
               diff.tag = this.tag.COMPLETE;
            }
    
            if (this.getBatchUploadAction(args.batchID) === 'stop') {
                diff.tag = this.tag.REMOVE;
            }
    
            _.assign(this.uploadingTasks[idx], diff);
            
            if (saveToStorage) {
                await this.mainChannel.storage.update(args.taskId, diff);
            }

            return this.uploadingTasks[idx];
        } catch (e) {
            return null;
        }
    },
    
    /**
     * queue uploads
     *
     */
    async queueUploads(/* any task type */ tasks) {
        const allTasks = await this.getAllTasks();
        
        if (allTasks.length + tasks.length > this.taskLimit) {
            await this.removeChannelsByTasks(allTasks);
            await this.mainChannel.clear();
        }

        tasks.forEach((task) => {
            this.fileObjects[task.args?.uploadId] = task.args?.fileObject ?? null;
        });
    
        await this.moveQueuedTasksToMainChannel(tasks);
        await this.addTaskToWorkerChannel();
    },

    /**
     * get child node by path
     *
     * Tine.Filemanager.nodeBackendMixin.searchRecord method needs all the pending nodeData,
     * so that user can switch and see the pending nodes while uploading
     */
    async getProcessingNodesByPath(path) {
        const allTasks = await this.getAllTasks();
        return allTasks
            .filter(t => {
                const parentPath = Tine.Filemanager.Model.Node.dirname(t.args.nodeData.path);
                return path === parentPath && t.tag !== this.tag.REMOVE;
            })
            .map(t => t.args.nodeData);
    },

    /**
     * every upload in the upload manager gets queued initially
     *
     * @returns {String} upload id
     */
    generateUploadId() {
        return this.uploadIdPrefix + (1000 + this.uploadCount++).toString();
    },

    /**
     * returns upload by uploadId
     *
     * @param uploadId {String} upload id
     * @param fileObject
     * @returns (Ext.ux.file.Upload} Upload object
     */ 
    async getUpload(uploadId, fileObject) {
        if (!uploadId) return null;
        
        if (this.fileObjects[uploadId] && !this.uploads[uploadId]) {
            if (typeof this.fileObjects[uploadId].getFile === 'function') {
                this.fileObjects[uploadId] = await this.fileObjects[uploadId].getFile();
            }
            
            this.uploads[uploadId] = new Ext.ux.file.Upload({
                file: this.fileObjects[uploadId],
                id: uploadId,
                isFolder: false,
            });
        }
        return this.uploads[uploadId];
    },
    
    /**
     * returns all tasks from main channel
     *
     */
    async getAllTasks() {
        return await this.mainChannel.storage.all();
    },
    
    /**
     * remove upload from the upload manager
     *
     * @param uploadId
     */
    unregisterUpload(uploadId) {
        if (this.uploads[uploadId]) {
            delete this.uploads[uploadId];
        }
    
        const idx =  _.findIndex(this.uploadingTasks, {'label' : uploadId});
        if (idx > -1) {
            delete this.uploadingTasks[idx];
        }
    },

    /**
     * on upload complete handler
     */
    onUploadComplete() {
        Tine.Tinebase.uploadManager.runningUploads
            = Math.max(0, Tine.Tinebase.uploadManager.runningUploads - 1);
    },

    /**
     * on upload start handler
     */
    onUploadStart() {
        Tine.Tinebase.uploadManager.runningUploads
            = Math.min(Tine.Tinebase.uploadManager.maxChannels, Tine.Tinebase.uploadManager.runningUploads + 1);
    },

    /**
     *  add task to worker channel
     *  - execute when a task is completed
     *  - execute once a task is queued in main channel
     *  - it scans all worker channels , collect empty channels and add tasks
     *
     * @returns {Promise<void>}
     */
    async addTaskToWorkerChannel() {
        // worker channels would call this method parallel , but we want to execute this method in serialize way ,
        // so we use adding flag to deal with race condition
        if (this.adding === false) {
            this.adding = true;
            // get active tasks with current instanceId
            const tasks = await this.activateDependentTasks();
            const activeTasks = tasks.filter(t => {
                return t.tag === this.tag.ACTIVE && t.instanceId === this.instanceId && t.status === this.status.PENDING;
            });
            const tasksToAdd = activeTasks.slice(0, this.maxChannels);
            
            // add tasks to worker channel
            await tasksToAdd.reduce((prev, task) => {
                return prev.then(async () => {
                    const emptyChannel = await this.getEmptyWorkingChannel();
                    if (!emptyChannel) {
                        return Promise.resolve();
                    }
                    task.args.taskId = task._id;
                    await emptyChannel.add(task);
                    
                    task.tag = emptyChannel.name();
                    this.uploadingTasks.push(task);
                    return Promise.resolve();
                })
            }, Promise.resolve());
            
            await this.mainChannel.storage.updateBatch(tasksToAdd);
            await this.checkBatchUploadComplete();
            this.adding = false;
        }
    },

    /**
     * overwrite batch uploads
     *
     * set overwrite flog for the tasks that need to be overwritten
     */
    async overwriteBatchUploads(batchID) {
        const allTasks = await this.getAllTasks();
        const tasks = allTasks.filter(t => t.args.batchID === batchID);
    
        tasks.forEach(t => t.args.overwrite = true);
        this.setBatchAction(batchID, this.action.REPLACE);

        await this.mainChannel.storage.updateBatch(tasks);
    },

    /**
     * stop batch uploads
     *
     * in order to update batch uploads in display dialog , postal message should be called for each node
     */
    async stopBatchUploads(batchID) {
        this.setBatchAction(batchID, this.action.STOP);
        await this.stopWorkingChannels(this.instanceId);
        
        const allTasks = await this.getAllTasks();
        const tasks = allTasks.filter(t => t.args.batchID === batchID);
        
        tasks.forEach(t => {
            if (this.isInComplete(t.args.nodeData.status)) {
                t.status = this.status.CANCELLED;
                t.args.nodeData.status = t.status;
            }
            t.tag = this.tag.REMOVE;
        })
        
        await this.mainChannel.storage.updateBatch(tasks);
        await this.checkBatchUploadComplete();
        this.adding = false;
    },
    
    /**
     * remove complete tasks in storage
     */
    async removeCompleteTasks() {
        await this.mainChannel.clearByTag(this.tag.COMPLETE);
        this.adding = false;
    },
    
    /**
     * remove failed tasks first from server and local storage
     */
    async removeFailedTasks(tasks) {
        // always delete uploading nodes
        const uploadingTasks = tasks.filter(t => t.args.nodeData.status === this.status.UPLOADING);
        await uploadingTasks.reduce((prev, t) => {
            return prev.then(async () => {
                await Tine.Filemanager.searchNodes([
                    {field: 'path', operator: 'equals', value: t.args.targetFolderPath},
                    {field: 'contenttype', operator: 'contains', value: 'vnd.adobe.partial-upload'},
                ]).then(async (result) => {
                    if (result?.totalcount === 0) return;
                    await Tine.Filemanager.deleteNodes(result.results.map(n => n.path))
                        .catch((e) => {
                            throw new Ext.Error('remove empty node from server failed');
                        });
                    
                }).catch((e) => {});
                return Promise.resolve();
            })
        }, Promise.resolve())
        
        await this.resolveUploadsStatus(tasks);
        await this.checkBatchUploadComplete();
    },
    
    /**
     * remove failed tasks first from server and local storage
     */
    async removeTasksByNode(nodes) {
        const paths = nodes.map(n => n.data.path);
        // complete folder tasks should be removed
        // cancelled files and failed files should be removed
        const allTasks = await this.getAllTasks();
        const tasks = allTasks
            .filter(t => {
                const taskPaths = t.folderDependencies.concat(t.label);
                return taskPaths.filter(path => paths.includes(path)).length > 0;
            });
        
        tasks.forEach(t => t.tag = this.tag.REMOVE);
        
        await this.mainChannel.storage.updateBatch(tasks);
        await this.removeChannelsByTasks(tasks);
        await this.checkBatchUploadComplete();
        return tasks;
    },
    
    /**
     * reset all upload channels
     *
     * reset all tasks in main channel and worker channels
     * no tasks will be left in all channels
     */
    async resetUploadChannels(clearStorage = true) {
        const allTasks = await this.getAllTasks();
        const batchIDs = [...new Set(allTasks.map(t => t.args.batchID))];
        
        batchIDs.forEach(batchID => { delete this.applyBatchAction[batchID]; });
        await this.removeChannelsByTasks(allTasks, true);
        //fixme: main ch should not be cleared before all deprecated instances
        if (clearStorage) {
            await this.mainChannel.clear();
        }
        
        this.fileObjects = [];
        this.adding = false;
        await this.checkBatchUploadComplete();
    },
    
    /**
     * resolve task status of all uploads
     *
     */
    async resolveUploadsStatus(tasks) {
        const instances = await this.removeChannelsByTasks(tasks);
        tasks = tasks.filter(t => {
            return t.status !== this.status.COMPLETE && !instances[t.instanceId]
        });
    
        tasks.forEach(task => {
            const status = task.args.nodeData.status;
            const taskUpdateError = status !== task.status;
            const availableRevisions = task.args.nodeData.available_revisions;
            
            if (status === this.status.PENDING) {
                task.status = this.status.CANCELLED;
                task.reason = 'deprecated pending upload';
                task.tag = this.tag.CANCELLED;
            }
            
            if (status === this.status.UPLOADING) {
                task.status = this.status.CANCELLED;
                task.reason = 'upload incomplete';
                task.tag = this.tag.CANCELLED;
            }
    
            if (status === this.status.FAILURE) {
                task.tag = this.tag.REMOVE;
            }
    
            if (taskUpdateError) {
                task.status = this.status.FAILURE;
                task.reason = 'task update incomplete';
                task.tag = this.tag.REMOVE;
            }

            task.args.nodeData.status = task.status;
            task.args.nodeData.reason = task.reason;
    
            window.postal.publish({
                channel: "recordchange",
                topic: 'Filemanager.Node.update',
                data: task.args.nodeData
            });
        });
    
        await this.mainChannel.storage.updateBatch(tasks);
        return await this.getAllTasks();
    },
    
    /**
     * activate dependent tasks
     *
     * switch inactive task to active once all the dependencies are resolved
     */
    async activateDependentTasks() {
        const allTasks = await this.getAllTasks();
        const inactiveTasks = allTasks.filter(t => t.tag === this.tag.INACTIVE);
        const activeTasks = allTasks.filter(t => t.tag === this.tag.ACTIVE);
        
        // move tasks to activeChannel if the dependencies task are completed
        inactiveTasks.forEach(task => {
            const lastDep = _.last(task.folderDependencies);
            const hasUnmetFolderDependencies = !!(lastDep && !this.completeTaskPaths.includes(lastDep));
            if (!hasUnmetFolderDependencies) {
                task.tag = this.tag.ACTIVE;
            }
        });
        
        await this.mainChannel.storage.updateBatch(inactiveTasks);
        return activeTasks.concat(inactiveTasks);
    },
    
    /**
     * resolve deprecated instances
     *
     * - check all instances running status
     * - stop deprecated working channels
     */
    async removeChannelsByTasks(tasks, includeRunning = false) {
        const instanceIds = [...new Set(tasks.map(t => t.instanceId))];
        await instanceIds.reduce((prev, instanceId) => {
            return prev.then(async () => {
                this.instances[instanceId] = await this.isRunning(instanceId);
                if (includeRunning || !this.instances[instanceId]) {
                    await this.stopWorkingChannels(instanceId);
                }
                return Promise.resolve();
            })
        }, Promise.resolve());

        return this.instances;
    },

    /**
     * get failed file and folder upload tasks
     *
     * update status of all failed and unfinished tasks
     * only resolve failed tasks if instanceId s deprecated
     *
     * @returns {Promise<void>}
     */
    async getFailedUploadTasks() {
        const allTasks = await this.getAllTasks();
        await this.removeChannelsByTasks(allTasks);
        this.mainChannel.clearByTag(this.tag.REMOVE);
        
        const failedStatus = [this.status.PENDING, this.status.UPLOADING, this.status.FAILURE];
        const failedTasks = allTasks.filter(t => failedStatus.includes(t.status) && t.tag !== this.tag.REMOVE);
        
        await this.checkBatchUploadComplete();
        
        return failedTasks;
    },
    
    /**
     * set batch action
     *
     */
    setBatchAction(batchID, action) {
        if (!batchID) return;
        this.applyBatchAction[batchID] = action;
        return this.applyBatchAction[batchID];
    },
    
    /**
     * get batch action
     *
     */
    getBatchUploadAction(batchID) {
        if (! this.applyBatchAction[batchID]) {
            this.applyBatchAction[batchID] = '';
        }
        return this.applyBatchAction[batchID];
    },
    
    /**
     * move queued tasks to main channel
     *
     * @param tasks
     */
    async moveQueuedTasksToMainChannel(tasks) {
        await this.mainQueueChannel.clearByTag(this.tag.ADDED);
        await this.mainQueueChannel.addBatch(tasks);
    
        tasks.forEach(task => {
            this.fileObjects[task.args?.uploadId] = task.args?.fileObject ?? null;
        });
        
        const allTasks = await this.getAllTasks();
        const queueTasks = await this.mainQueueChannel.storage.all();
        const tasksToQueue = [];
        const existFolderTasks = [];
        
        // reuse create folder task and add tasks from main queue channel to main channel
        queueTasks.forEach(task => {
            const uploadId = task.args?.uploadId;
            const existFolderTask = allTasks.find(t => { 
                return t.label === uploadId && t.handler ==='FilemanagerCreateFolderTask'
            });
            const defaultData = {
                status: this.status.PENDING,
                tag: task.folderDependencies.length > 0 ? this.tag.INACTIVE : this.tag.ACTIVE,
                instanceId: this.instanceId
            };
            
            _.assign(task, defaultData);
            
            if (existFolderTask) {
                _.assign(existFolderTask, defaultData);
                existFolderTasks.push(existFolderTask);
            } else {
                tasksToQueue.push(task);
            }
        });
        
        await this.mainChannel.storage.updateBatch(existFolderTasks);
        await this.mainChannel.addBatch(tasksToQueue);
    
        queueTasks.forEach(t => t.tag = this.tag.ADDED);        
        // update main queue channel
        await this.mainQueueChannel.storage.updateBatch(queueTasks);
    }
});
