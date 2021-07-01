import UploadQueue from './UploadQueue';
import FilemanagerCreateFolderTask from 'Filemanager/js/CreateFolderTask';
import FilemanagerUploadFileTask from 'Filemanager/js/UploadFileTask';

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
        limit: 50,
        principle: 'fifo',
    });
    
    this.uploads = {};
    this.workerChannels = [];
    this.fileObjects = [];
    this.mainChannel = null;
    this.virtualNodes = {};
    
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

        onInitFs: function (fs)
        {
            fs.root.getFile('log.txt', {create: true, exclusive: true}, function(fileEntry) {

                fileEntry.isFile === true;
                fileEntry.name === 'log.txt';
                fileEntry.fullPath === '/log.txt';

            }, this.errorHandler);

        },
    
        errorHandler: function (e)
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

        InitChannels: async function () {
            this.mainChannel = this.queue.create(`Channel-Upload-main`);
            
            for (let i = 0; i < this.maxChannels; i++) {
                //create task
                const channel = this.queue.create(`Channel-Upload-${i}`);
                this.workerChannels.push(channel);
                channel.start().then(status => console.log(`channel ${i} created!`));

                // add event listener when worker channel finish the task
                channel.on('active:after', async (result) => {
                    let tasks = await this.mainChannel.getAllTasks();
                    tasks = _.filter(tasks, {tag: 'inactive'});

                    if (tasks) {
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

                        await this.addTaskToWorkerChannel();
                    }
                });
            }
        },

        queueUploads: async function(/* any task type */ tasks) {
            _.each(tasks, (task) => {
                const uploadId = _.get(task, 'args.uploadId');
                this.virtualNodes[uploadId] = _.get(task, 'args.nodeData');
                
                if (false /* filesystem accessa api */) {
                    // for security reason , we only can get files via file select dialog or D&D, 
                    // we can not get files via API directly
                    // task.fsapi = someserialisedreferencetofile
                } else {
                    const uploadFile = _.get(task, 'args.fileObject');
                    if (uploadFile) {
                        this.fileObjects[uploadId] = uploadFile; // old file handle
                    }
                }
                
                _.set(task, 'tag', _.size(task.dependencies) > 0 ? 'inactive' : 'active');
            })
            
            const taskIds = await this.mainChannel.addBatch(tasks);
            await this.addTaskToWorkerChannel();
    
            return taskIds;
        },

        getVirtualNodesByPath: function(path) {
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

        removeVirtualNode: function (uploadId) {
            delete this.virtualNodes[uploadId];
        },

        /**
         * return inactiveChannel or activeChannel has task with on of the task.depended ids
         * 
         * @param task
         * @returns {Promise<boolean>}
         */
        hasUnmetDependencies: async function(/* any task type */ task) {
            let hasUnmetDependencies = false;
            let tasks = await this.mainChannel.getAllTasks();
            const taskIDs = _.map(tasks, '_id');

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
        addWorkerChannelDependencies: async function(oldTaskId, newTaskId) {
            let tasks = await this.mainChannel.getAllTasks();
            
            await _.reduce(_.filter(tasks, {tag: 'inactive'}), (prev, task) => {
                return prev.then(async () => {
                    if (_.includes(task.dependencies, oldTaskId)) {
                        task.dependencies.push(newTaskId);
                        await this.mainChannel.storage.update(task._id, {dependencies : task.dependencies});
                    }
                    return Promise.resolve();
                })
            }, Promise.resolve());
        },

        /**
         * every upload in the upload manager gets queued initially
         *
         * @returns {String} upload id
         */
        generateUploadId: function() {
            return this.uploadIdPrefix + (1000 + this.uploadCount++).toString();
        },

        /**
         * returns upload by uploadId
         * 
         * @param uploadId {String} upload id
         * @returns (Ext.ux.file.Upload} Upload object
         */
        getUpload: function(uploadId) {
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
    
        updateLocalStorage: async function(args) {
            await this.mainChannel.storage.update(args._id, {args: args});
            this.uploads[args.uploadId]['task'] = await this.mainChannel.get(args._id);
        },

        /**
         * returns upload by uploadId
         *
         * @param uploadId {String} upload id
         * @returns (Ext.ux.file.Upload} Upload object
         */
        getFileObject: function(uploadId) {
            return this.fileObjects[uploadId];// || this.getItFromLoccalStorage();
        },

        /**
         * remove upload from the upload manager
         *
         * @param id
         */
        unregisterUpload: function(id) {
            delete this.uploads[id];
        },
    
        /**
         * on upload complete handler
         */
        onUploadComplete: function() {
            Tine.Tinebase.uploadManager.runningUploads 
                = Math.max(0, Tine.Tinebase.uploadManager.runningUploads - 1);
        }, 
        
        /**
         * on upload start handler
         */
        onUploadStart: function() {
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
        addTaskToWorkerChannel: async function () {
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
                            await this.mainChannel.storage.update(task._id, {tag: 'shifted'});
                            await this.mainChannel.clearByTag('shifted');
                            await this.addWorkerChannelDependencies(oldTaskId, newTaskId);
                        }
                        return Promise.resolve();
                    })
                }, Promise.resolve());
                
                this.adding = false;
            }
        }
});
