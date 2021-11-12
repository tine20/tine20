import UploadQueue from './UploadQueue';
import FilemanagerCreateFolderTask from 'Filemanager/js/CreateFolderTask';
import FilemanagerUploadFileTask from 'Filemanager/js/UploadFileTask';
import Queue from "storage-based-queue";

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

    UploadQueue.workers({FilemanagerCreateFolderTask});
    UploadQueue.workers({FilemanagerUploadFileTask});

    this.queue = new UploadQueue({
        storage: "inmemory",
        prefix: "/Tine.Tinebase.uploads",
        timeout: 1500,
        limit: 2000,
        principle: 'fifo',
    });

    this.uploads = {};
    this.workerChannels = [];
    this.fileObjects = [];
    this.mainChannel = null;
    this.virtualNodes = {};
    this.tasks = {};

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
         *  store the apply to all status og every batch uploads
         */
        applyToAll: [],

        /**
         *  total Upload byte of all unfinished uploads
         */
        totalUploadByte: 0,

        /**
         *  current Upload byte of all unfinished uploads
         */
        currentUploadByte: 0,

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
     * init channels
     *
     * initial all channels and events
     * resolve tasks in storage
     */
    async InitChannels() {
        this.mainChannel = this.queue.create(`Channel-Upload-main`);
        await this.resolveFailedUploadTasks();

        for (let i = 0; i < this.maxChannels; i++) {
            //create task
            const channel = this.queue.create(`Channel-Upload-${i}`);
            this.workerChannels.push(channel);
            channel.start().then(async () => {
                await channel.clear();
            });

            // add event listener when worker channel finish the task
            channel.on('active:after', async (result) => {
                this.unregisterUpload(result.args.uploadId);
                await this.activateDependentTasks();
                await this.addTaskToWorkerChannel();
            });

            channel.on("error", async (result) => {

            });
        }
    },
    
    /**
     * check batch upload complete
     *
     * update icon in main menu, if uploads with same batch id are complete
     */
    async checkBatchUploadComplete() {
        let tasks = await this.getAllFileUploadTasks();
        const incompleteTasks = _.filter(tasks, (task) => {
          return task.status === 'uploading' || task.status === 'pending';
        });
        
        if (incompleteTasks.length === 0) {
            const actions = Tine.Tinebase.MainScreen.getMainMenu().getActionByPos(55);
            actions[0]?.uploadIdle();
        }
    },
    
    /**
     * update task by upload id
     *
     * - keep task up to date in storage
     * - args must have uploadId
     */
    async updateTaskByArgs(args) {
        try {
            const uploadId = args.uploadId;
            const hasTask = await this.mainChannel.has(this.tasks[uploadId]._id);
            
            if (hasTask) {
                args.nodeData.last_upload_time = new Date().toJSON();
                let diff = {args: args};
                diff.status = diff.args.nodeData.status;
                
                await this.mainChannel.storage.update(this.tasks[uploadId]._id, diff);
                const task = await this.mainChannel.get(this.tasks[uploadId]._id);
                return task[0];
            }
        } catch (e) {
            return null;
        }
    },
    
    /**
     * queue uploads
     *
     */
    async queueUploads(/* any task type */ tasks) {
        _.each(tasks, (task) => {
            const uploadId = task.args?.uploadId;
            this.virtualNodes[uploadId] = task.args?.nodeData;
    
            if (false /* filesystem accessa api */) {
                // for security reason , we only can get files via file select dialog or D&D,
                // we can not get files via API directly
                // task.fsapi = someserialisedreferencetofile
            } else {
                const uploadFile = task.args?.fileObject;
                if (uploadFile) {
                    this.fileObjects[uploadId] = uploadFile; // old file handle
                }
            }

            _.set(task, 'tag', _.size(task.dependencies) > 0 ? 'inactive' : 'active');
        })

        const taskIds = await this.mainChannel.addBatch(tasks);
        
        await this.addTaskToWorkerChannel();

        const actions = Tine.Tinebase.MainScreen.getMainMenu().getActionByPos(55);
        actions[0]?.uploadActive();

        return taskIds;
    },

    /**
     * get virtual node by path
     *
     * Tine.Filemanager.nodeBackendMixin.searchRecord method needs all the pending nodeData,
     * so that user can switch folders while uploading
     */
    getVirtualNodesByPath(path) {
        return _.filter(this.virtualNodes, (node) => {
            if (node) {
                if (node.path.endsWith('/')) {
                    return node.path.replace(node.name, '') === `${path}/`;
                } else {
                    return node.path.replace(node.name, '') === `${path}`;
                }
            }
        });
    },
    
    /**
     * remove virtual node by path
     *
     */
    removeVirtualNode(uploadId) {
        delete this.virtualNodes[uploadId];
    },

    /**
     * return inactiveChannel or activeChannel has task with on of the task.depended ids
     *
     * @param task
     * @returns {Promise<boolean>}
     */
    async hasUnmetDependencies(/* any task type */ task) {
        let hasUnmetDependencies = false;
        let tasks = await this.mainChannel.getAllTasks();
        const taskIDs = _.map(_.filter(tasks, {tag: 'inactive'}), '_id');

        await _.reduce(this.workerChannels, (prev, channel) => {
            return prev.then(async () => {
                const task = await channel.getAllTasks();
                if (task.length) taskIDs.push(task._id);
                return Promise.resolve();
            })
        }, Promise.resolve());

         _.each(task.dependencies, (taskId) => {
             if (_.includes(taskIDs, taskId)) {
                 hasUnmetDependencies = true;
             }
         })
        
         return hasUnmetDependencies;
     },

    /**
     * after shift task from main channel to worker channel,
     * add new taskId as dependency to all inactive tasks
     * @param oldTaskId
     * @param newTaskId
     * @returns {Promise<void>}
     */
    async addWorkerChannelDependencies(oldTaskId, newTaskId) {
        let tasks = await this.mainChannel.getAllTasks();
        const promises = [];
        
        _.each(_.filter(tasks, {tag: 'inactive'}), (task) => {
            if (_.includes(task.dependencies, oldTaskId)) {
                task.dependencies.push(newTaskId);
                promises.push(this.mainChannel.storage.update(task._id, {dependencies: task.dependencies}));
            }
        });
        
        await Promise.allSettled(promises);
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
     * @returns (Ext.ux.file.Upload} Upload object
     */
    getUpload(uploadId) {
        const fileObject = this.fileObjects[uploadId];// || this.getItFromLoccalStorage();
        if(this.fileObjects[uploadId] && !this.uploads[uploadId]) {
            this.uploads[uploadId] = new Ext.ux.file.Upload({
                file: this.fileObjects[uploadId],
                id: uploadId,
                isFolder: false
            });
        }
        return this.uploads[uploadId] ?? null;
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
     * returns upload by uploadId
     *
     * @param uploadId {String} upload id
     * @returns (Ext.ux.file.Upload} Upload object
     */
    getFileObject(uploadId) {
        return this.fileObjects[uploadId];// || this.getItFromLoccalStorage();
    },

    /**
     * remove upload from the upload manager
     *
     * @param id
     */
    unregisterUpload(id) {
        delete this.uploads[id];
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
     *  - it scan all worker channels , collect empty channels and add tasks to them
     *  - add new generated taskId as dependency to inactive tasks
     *
     * @returns {Promise<void>}
     */
    async addTaskToWorkerChannel() {
        // worker channels would call this method parallel , but we want to execute this method in serialize way ,
        // so we use adding flag to deal with race condition
        if (this.adding === false) {
            this.adding = true;

            // every time we add task to worker channel , we need to get all the tasks with tag 'active'
            let allTasks = await this.mainChannel.getAllTasks();
            const emptyChannels = [];

            // collect empty channels
            await _.reduce(this.workerChannels, (prev, channel) => {
                return prev.then(async () => {
                    const count = await channel.count();
                    if (count === 0) {
                        emptyChannels.push(channel);
                    }
                    return Promise.resolve();
                })
            }, Promise.resolve());

            // collect active tasks
            let tasks = _.filter(allTasks, {tag: 'active'});

            // add tasks to worker channel , and  then add new generated taskId as dependency to inactive tasks
            await _.reduce(emptyChannels, (prev, channel) => {
                return prev.then(async () => {
                    const task = _.head(tasks);
                    tasks = _.drop(tasks);
                    if (task) {
                        const oldTaskId = task._id;
                        const newTaskId = await channel.add(task);
                        await this.mainChannel.storage.update(task._id, {tag: channel.name(), status: task.status});
                        await this.addWorkerChannelDependencies(oldTaskId, newTaskId);
                    }
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
        this.applyToAll[batchID] = 'replace';

        let allTasks = await this.mainChannel.getAllTasks();
        let tasks = _.filter(allTasks, (task) => {
            return task.args.batchID = batchID;
        });
        const promises = [];
        
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
        this.applyToAll[batchID] = 'stop';

        // clean up WorkerChannels
        await _.reduce(this.workerChannels, (prev, channel) => {
            return prev.then(async () => {
                await channel.forceStop();
                await channel.clear();
                await channel.start();
                return Promise.resolve();
            })
        }, Promise.resolve());
        
        // update task status in storage and ui
        let allTasks = await this.mainChannel.getAllTasks();
        const promises = [];
        
        _.each(allTasks, (task) => {
            if (task.args.batchID === batchID && task.status !== 'complete') {
                task.args.nodeData.status = 'cancelled';
        
                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Filemanager.Node.update',
                    data: task.args.nodeData
                });
    
                promises.push(this.mainChannel.storage.update(task._id, {args: task.args, status: 'failed'}));
                Tine.Tinebase.uploadManager.removeVirtualNode(task.args.uploadId);
            }
        });
    
        await Promise.allSettled(promises).then(async () => {
            await this.checkBatchUploadComplete();
            this.adding = false;
        });
    },
    
    /**
     * reset all upload channels
     *
     * reset all tasks in main channel and worker channels
     */
    async resetUploadChannels() {
        await this.mainChannel.stop();
        await this.mainChannel.clear();
        
        await _.reduce(this.workerChannels, (prev, channel) => {
            return prev.then(async () => {
                await channel.stop();
                await channel.clear();
                await channel.start();
                return Promise.resolve();
            })
        }, Promise.resolve());
        
        this.uploads = {};
        this.adding = false;
        await this.checkBatchUploadComplete();
    },
    
    /**
     * activate dependent tasks
     *
     * switch inactive task to active once all the dependencies are resolved
     */
    async activateDependentTasks() {
        let tasks = await this.mainChannel.getAllTasks();
        tasks = _.filter(tasks, {tag: 'inactive'});

        if (tasks.length > 0) {
            // move tasks to activeChannel if the dependencies task are completed
            await _.reduce(tasks, (prev, task) => {
                return prev.then(async () => {
                    const dep = await this.hasUnmetDependencies(task);
                    if (!dep) {
                        await this.mainChannel.storage.update(task._id, {tag: 'active'});
                    }
                    return Promise.resolve();
                })
            }, Promise.resolve());
        }
    },

    /**
     * resolve failed upload tasks
     *
     * update status of all failed and unfinished tasks
     *
     * @returns {Promise<void>}
     */
    async resolveFailedUploadTasks() {
        const tasks = await this.getAllFileUploadTasks();
        const promises = [];
    
        _.each(tasks, async (task) => {
            const status = task.args.nodeData.status;
            if (status !== 'complete') {
                promises.push(this.mainChannel.storage.update(task._id, {status: 'failed'}));
            }
        });
    
        await Promise.allSettled(promises);
    }
});
