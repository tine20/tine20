import Queue from 'storage-based-queue';
import UploadChannel from "./UploadChannel";
import FilemanagerCreateFolderTask from 'Filemanager/js/CreateFolderTask';
import FilemanagerUploadFileTask from 'Filemanager/js/UploadFileTask';

class UploadQueue extends Queue {
    constructor(config) {
        super(config);
        this.initWorkers();
    }

    /**
     * Create a new channel
     *
     * @return {Queue} channel
     *
     * @api public
     * @param channel
     */
    create(channel) {
        if (!this.container.has(channel)) {
            this.container.bind(channel, new UploadChannel(channel, this.config));
        }
        return this.container.get(channel);
    };
    
    /**
     * Get channel instance by channel name
     *
     * @param  {String} name
     * @return {Queue}
     *
     * @api public
     */
    channel(name) {
        return this.container.has(name) ? this.container.get(name) : null;
    }
    
    initWorkers() {
        UploadQueue.workers({FilemanagerCreateFolderTask});
        UploadQueue.workers({FilemanagerUploadFileTask});
    }

}

export default UploadQueue
