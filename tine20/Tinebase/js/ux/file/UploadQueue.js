import Queue from 'storage-based-queue';
import UploadChannel from "./UploadChannel";

class UploadQueue extends Queue {

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

}

export default UploadQueue