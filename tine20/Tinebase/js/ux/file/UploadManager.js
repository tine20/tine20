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
    
    this.taskLimit = 2000;
    this.channelPrefix = '/Tine.Tinebase.uploads';
    this.uploads = {};
    this.fileObjects = [];
    this.completeTaskPaths = [];
    this.supportFileHandle = !Ext.isGecko && !Ext.isIE;
    
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
        let tasks = await this.mainChannel.getAllTasks();
        const incompleteTasks = _.filter(tasks, (task) => {
            return (task.status === 'uploading' || task.status === 'pending');
        });
        if (incompleteTasks.length === 0) {
            const actions = Tine.Tinebase.MainScreen.getMainMenu().getActionByPos(55);
            actions[0]?.uploadIdle();
        }
    },

    async restartFailedUploads(tasks) {
        let failedFileUploadPromises = [];
        const permissionPromises = [];
        const folderDepTasks = [];
        const folderEntryPermissions = [];

        let allTasks = await this.mainChannel.getAllTasks();
        tasks =  _.filter(allTasks, {status: 'failed'});
        
        _.each(tasks, (task) => {
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
                    _.each(folderDeps, (dependency) => {
                        const task = _.find(allTasks, {label: dependency});
                        const existDepTask = _.find(folderDepTasks, {label: dependency});
                        if (!existDepTask && task) {
                            folderDepTasks.push(task);
                        }
                    });
                    // get granted entry permission
                    if (folderEntryPermissions[entry] === '') {
                        let permission = await fileHandle.queryPermission();
                        folderEntryPermissions[entry] = permission === 'granted' ? permission : await fileHandle.requestPermission({});
                    }
                    // get file after we got granted entry permission
                    if (folderEntryPermissions[entry] === 'granted') {
                        this.fileObjects[task.args.uploadId] = await fileHandle.getFile();
                        failedFileUploadPromises = _.concat(failedFileUploadPromises, this.resetTasks([task], this.instanceId));
                    }
        
                    resolve(folderEntryPermissions[entry]);
                });
                
                permissionPromises.push(promise);
            }
        });
        
        await Promise.all(permissionPromises).then(async (result) => {
            const resetTaskPromises = _.concat(failedFileUploadPromises, this.resetTasks(folderDepTasks, this.instanceId));
            Promise.allSettled(resetTaskPromises).then(async (result) => {
                await this.addTaskToWorkerChannel();
            });
        });
    },
    
    /**
     * reset task with upload pending configs
     *
     * - force overwrite
     */
    resetTasks(tasks, instanceId) {
        const promises = [];

        _.each(tasks, (task) => {
            promises.push(new Promise(async (resolve) => {
                const isRunning = await this.isRunning(task.instanceId);
                // dependency task might be completed or failed , we reuse it
                if (!isRunning) {
                    await this.stopWorkingChannels(task.instanceId);
                    task.args.overwrite = true;
                    task.args.nodeData.status = 'pending';
                    await this.mainChannel.storage.update(task._id, {
                        status: 'pending',
                        tag: _.size(task.folderDependencies) > 0 ? 'inactive' : 'active',
                        instanceId: instanceId,
                        args: task.args
                    });
                }
                resolve();
            }));
        });

        return promises;
    },
    
    /**
     * update task by upload id
     *
     * - keep task up to date in storage
     * - args must have uploadId
     */
    async updateTaskByArgs(args) {
        try {
            const hasTask = !!(await this.mainChannel.has(args.taskId));

            if (!hasTask) {
                return null;
            }

            args.nodeData.last_upload_time = new Date().toJSON();
            let diff = {args: args};
            diff.status = diff.args.nodeData.status;
            if (diff.status === 'complete') {
               diff.tag = diff.status;
            }

            await this.mainChannel.storage.update(args.taskId, diff);
            const task = await this.mainChannel.get(args.taskId);
            return task[0];
        } catch (e) {
            return null;
        }
    },
    
    /**
     * queue uploads
     *
     */
    async queueUploads(/* any task type */ tasks) {
        let allTasks = await this.mainChannel.getAllTasks();
        
        if (allTasks.length + tasks.length > this.taskLimit) {
            // we remove complete tasks from main channel automatically
            const promises = [];
            const completeTasks = _.filter(tasks, {tag: 'complete'});
            _.each(_.union(_.map(completeTasks, 'instanceId')), (instanceId) => {
                promises.push(new Promise(async (resolve) => {
                    const isRunning = await this.isRunning(instanceId);
                    if (!isRunning) {
                        await this.stopWorkingChannels(instanceId);
                    }
                    resolve();
                }));
            });
            
            await Promise.allSettled(promises);
            await this.mainChannel.clearByTag('complete');
            await this.mainQueueChannel.clearByTag('added');
        }
        
        await this.mainQueueChannel.addBatch(tasks);
        await this.addTaskToWorkerChannel();
    },

    /**
     * get child node by path
     *
     * Tine.Filemanager.nodeBackendMixin.searchRecord method needs all the pending nodeData,
     * so that user can switch and see the pending nodes while uploading
     */
    async getProcessingNodesByPath(path) {
        let tasks = await this.mainChannel.getAllTasks();
        tasks = _.filter(tasks, (task) => {
            const parentPath = this.getParentPath(task.args.nodeData.path);
            return path === `${parentPath}/` && task.args.nodeData.status !== 'complete';
        });
        return _.map(tasks, 'args.nodeData');
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
     * returns all file upload tasks in storage
     *
     * @returns (Ext.ux.file.Upload} Upload object
     */
    async getAllFileUploadTasks() {
        let fileUploadTasks = await this.mainChannel.getAllTasks();
        return _.filter(fileUploadTasks, {handler: 'FilemanagerUploadFileTask'});
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
            
            // get active tasks with curretn instanceId
            await this.activateDependentTasks();
            const allTasks = await this.mainChannel.getAllTasks();
            let tasks = _.filter(allTasks, {tag: 'active', instanceId: this.instanceId, status: 'pending'});
            const tasksToAdd = tasks.slice(0, this.maxChannels);
            
            // add tasks to worker channel
            await _.reduce(tasksToAdd, (prev, task) => {
                return prev.then(async () => {
                    const emptyChannel = await this.getEmptyWorkingChannel();
                    if (!emptyChannel) {
                        return Promise.resolve();
                    }
                    task.args.taskId = task._id;
                    await this.mainChannel.storage.update(task._id, {
                        tag: emptyChannel.name(),
                        status: task.status,
                        args: task.args
                    });
                    await emptyChannel.add(task);

                    const actions = Tine.Tinebase.MainScreen.getMainMenu().getActionByPos(55);
                    actions[0]?.uploadActive();
                    return Promise.resolve();
                })
            }, Promise.resolve());
    
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
        const promises = [];
        let allTasks = await this.mainChannel.getAllTasks();
        let tasks = _.filter(allTasks, (task) => {
            return task.args.batchID === batchID;
        });
        
        this.setBatchAction(batchID, 'replace');
        
        _.each(tasks, (task) => {
            task.args.overwrite = true;
            promises.push(this.mainChannel.storage.update(task._id, {args: task.args}));
        })
        
        await Promise.allSettled(promises);
    },

    /**
     * stop batch uploads
     *
     * in order to update batch uploads in display dialog , postal message should be called for each node
     * TODO: do we have better solution ?
     */
    async stopBatchUploads(batchID) {
        // update task status in storage and ui
        const promises = [];
        let allTasks = await this.mainChannel.getAllTasks();

        this.setBatchAction(batchID, 'stop');
        await this.stopWorkingChannels(this.instanceId);
        
        _.each(allTasks, (task) => {
            if (task.args.batchID === batchID && task.args.nodeData.status !== 'complete') {
                task.args.nodeData.status = 'cancelled';
                promises.push(this.updateTaskByArgs(task.args));
            }
        });
    
        await Promise.allSettled(promises).then(async () => {
            await this.checkBatchUploadComplete();
            this.adding = false;
        });
    },
    
    /**
     * remove complete tasks in storage
     */
    async removeCompleteTasks() {
        const promises = [];
        let allTasks = await this.mainChannel.getAllTasks();
        
        _.each(allTasks, (task) => {
            const status = task.args.nodeData.status;
            if (status === 'complete') {
                promises.push(this.mainChannel.storage.delete(task._id));
            }
        });
    
        await Promise.allSettled(promises);
        this.adding = false;
    },
    
    /**
     * reset all upload channels
     *
     * reset all tasks in main channel and worker channels
     */
    async resetUploadChannels() {
        let allTasks = await this.mainChannel.getAllTasks();
        const promises = [];
        
        _.each(_.union(_.map(allTasks, 'args.batchID')), (batchID) => {
            if (batchID) delete this.applyBatchAction[batchID];
        })

        _.each(_.union(_.map(allTasks, 'instanceId')), (instanceId) => {
            promises.push(this.stopWorkingChannels(instanceId));
        });
    
        await Promise.allSettled(promises);
        await this.mainChannel.clear();
        
        _.each(allTasks, (task) => {
            if (task.args.nodeData.status !== 'complete' && !Ext.isArray(task.args.nodeData.available_revisions)) {
                task.args.nodeData.status = 'cancelled';
    
                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Filemanager.Node.update',
                    data: task.args.nodeData
                });
            }
        });
        
        this.fileObjects = [];
        this.adding = false;
        await this.checkBatchUploadComplete();
    },
    
    /**
     * activate dependent tasks
     *
     * switch inactive task to active once all the dependencies are resolved
     */
    async activateDependentTasks() {
        let allTasks = await this.getAllTasks();
        let tasks = _.filter(allTasks, {tag: 'inactive'});
        const promises = [];
        
        _.each(tasks, (task) => {
            // move tasks to activeChannel if the dependencies task are completed
            const lastDep = _.last(task.folderDependencies);
            const hasUnmetFolderDependencies = !!(lastDep && !this.completeTaskPaths.includes(lastDep));
            if (!hasUnmetFolderDependencies) {
                promises.push(this.mainChannel.storage.update(task._id, {tag: 'active'}));
            }
        });
        
        await Promise.allSettled(promises);
    },

    /**
     * resolve failed upload tasks
     *
     * update status of all failed and unfinished tasks
     * only resolve failed tasks if instanceId s deprecated
     *
     * @returns {Promise<void>}
     */
    async getFailedUploadTasks() {
        if (!this.supportFileHandle) {
            await this.resetUploadChannels();
            return [];
        }
        
        const promises = [];
        let tasks = await this.mainChannel.getAllTasks();
        
        _.each(tasks, (task) => {
            promises.push(new Promise(async (resolve) => {
                const status = task.args.nodeData.status;
                const isUploadFailed = status !== 'complete' && status !== 'cancelled';
                const taskUpdateIncomplete = status !== task.status;
                const isRunning = await this.isRunning(task.instanceId);
                
                if (!isRunning) {
                    await this.stopWorkingChannels(task.instanceId);
                    
                    if (isUploadFailed || taskUpdateIncomplete) {
                        await this.mainChannel.storage.update(task._id, {
                            status: isUploadFailed ? 'failed' : status,
                            tag: 'inactive',
                        });
                    }
                }
                resolve();
            }));
        });
        
        await Promise.allSettled(promises);
        tasks = await this.mainChannel.getAllTasks();
        const fileHandleTasks = _.filter(tasks, {status: 'failed', handler: 'FilemanagerUploadFileTask'});
        return fileHandleTasks;
    },
    
    /**
     * set batch action
     *
     * actions : default , replace , stop
     */
    setBatchAction(batchID, action) {
        if (!batchID) return;
        this.applyBatchAction[batchID] = action;
        return this.applyBatchAction[batchID];
    },
    
    /**
     * get batch action
     *
     * actions : default , replace , stop
     */
    getBatchUploadAction(batchID) {
        if (! this.applyBatchAction[batchID]) {
            this.applyBatchAction[batchID] = '';
        }
        return this.applyBatchAction[batchID];
    },
    
    /**
     * get parent path
     *
     * @param path
     * @returns {string|*}
     */
    getParentPath(path) {
        if (String(path).match(/\/.*\/.+/)) {
            let pathParts = path.split('/');
            pathParts.pop();
            // handle folder path that end with '/' 
            if (path.endsWith('/')) {
                pathParts.pop();
            }
            return pathParts.join('/');
        }
        return '/';
    },
    
    async getAllTasks() {
        let currentInstanceQueueTasks = await this.mainQueueChannel.getAllTasks();
        let allTasks = await this.mainChannel.getAllTasks();
        const queueTasks = _.filter(currentInstanceQueueTasks, {tag: 'queue'});
        const tasksToQueue = [];
        let promises = [];
        
        // reuse create folder task and add tasks from main queue channel to main channel
        _.each(queueTasks, (task) => {
            const uploadId = task.args?.uploadId;
            const existFolderTask = _.find(allTasks, {label: uploadId, handler: 'FilemanagerCreateFolderTask'});
            const defaultData = {
                status: 'pending',
                tag: _.size(task.folderDependencies) > 0 ? 'inactive' : 'active',
                instanceId: this.instanceId
            };
        
            if (existFolderTask) {
                promises.push(this.mainChannel.storage.update(existFolderTask._id, defaultData));
            } else {
                this.fileObjects[uploadId] = task.args?.fileObject ?? null;
                _.assign(task, defaultData);
                tasksToQueue.push(task);
            }
        });
        
        await Promise.allSettled(promises);
        await this.mainChannel.addBatch(tasksToQueue);
        
        // update main queue channel
        promises = [];
        _.each(queueTasks, (task) => {
            promises.push(this.mainQueueChannel.storage.update(task._id, {tag: 'added'}));
        });
        
        await Promise.allSettled(promises);
        allTasks = await this.mainChannel.getAllTasks();
        return allTasks;
    }
});
