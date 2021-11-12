import Channel from 'storage-based-queue/lib/channel';
import {
    db,
    canMultiple,
    saveTask,
    logProxy,
    createTimeout,
    stopQueue,
    getTasksWithoutFreezed
} from 'storage-based-queue/lib/helpers';

class UploadChannel extends Channel {

    constructor(name, config){
        super(name, config);
        this.islocked = false;
    }
    
    /**
     * add batch of Tasks
     * NOTE: saves a copy of all Tasks in local storage to be used for progress computations (adds a cleanup tasks as last job)
     * all tasks need to have a common progress interface: (bytesTotal / bytesUploaded fields)
     * all tasks need to have common fields: appName, label (e.g. Filemanager /path/to/file)
     */
    async addBatch(tasks) {
        const taskIDs = [];
        // add task and save copy
        await  _.reduce(tasks, (prev, task) => {
            return prev.then(async () => {
                const taskId = await this.add(task);
                const uploadId = task.args.uploadId;
                Tine.Tinebase.uploadManager.tasks[uploadId] = [];
                Tine.Tinebase.uploadManager.tasks[uploadId] = task;
                Tine.Tinebase.uploadManager.tasks[uploadId]._id = taskId;
                return taskIDs.push(taskId);
            });
        }, Promise.resolve());

        return taskIDs;
    }
    
    /**
     * get all direct child nodes of given path which are not yet processed
     * NOTE: have progress info on each returned node
     */
    getAllTasks() {
        var _this6 = this;
        return _asyncToGenerator(function*() {
            return (yield getTasksWithoutFreezed.call(_this6));
        })();
    }

    /**
     * Check a task whether exists by job id
     *
     * @return {Boolean}
     *
     * @api public
     * @param id
     */
    get(id) {
        var _this9 = this;
        return _asyncToGenerator(function*() {
            return (
                (yield getTasksWithoutFreezed.call(_this9)).filter(function(t) {
                    return t._id === id;
                })
            );
        })();
    }
}

function _asyncToGenerator(fn) {
    return function() {
        var gen = fn.apply(this, arguments);
        return new Promise(function(resolve, reject) {
            function step(key, arg) {
                try {
                    var info = gen[key](arg);
                    var value = info.value;
                } catch (error) {
                    reject(error);
                    return;
                }
                if (info.done) {
                    resolve(value);
                } else {
                    return Promise.resolve(value).then(
                        function(value) {
                            step('next', value);
                        },
                        function(err) {
                            step('throw', err);
                        }
                    );
                }
            }
            return step('next');
        });
    };
}

export default UploadChannel
