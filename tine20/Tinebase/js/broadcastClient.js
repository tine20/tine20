/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const randWait = async (min = 0, max = 2000) => {
    return new Promise((resolve) => {
        window.setTimeout(resolve, Math.round(Math.random()*max) + min)
    });
}

const init = async () => {
    await randWait(); // give browser time to breathe
    const auth = await Tine.Tinebase.getAuthToken(['broadcasthub'], 100);
    const wsUrl = Tine.Tinebase.configManager.get('broadcasthub').url;
    const socket = new WebSocket(wsUrl);
    let authResponse = null;

    socket.onopen = async (e) => {
        socket.send(auth.auth_token);
    };

    socket.onmessage = async (event) => {
        if (!authResponse) {
            authResponse = event.data;
            if (authResponse !== 'AUTHORIZED') {
                console.error(`[broadcastClient] not authorised: code=${event.code} ${event.data}`);
            }
            return;
        }
        try {
            const data = JSON.parse(event.data);
            const topicPrefix = String(data.model).split('_Model_').join('.');
            const topics = [`${topicPrefix}.*`, `${topicPrefix}.${data.recordId}`];

            //@TODO: can we filter out our own actions?
            if (topics.filter(value => postal.subscriptions.recordchange.hasOwnProperty(value)).length) {
                // we have a subscription, so let's try to load the record (NOTE: loading a record does a postal publishing)
                const recordClass = Tine.Tinebase.data.RecordMgr.get(data.model);
                const proxy = recordClass ? recordClass.getProxy() : null;

                if (proxy) {
                    if (data.verb === 'delete') {
                        proxy.postMessage(data.verb, {[recordClass.getMeta('idProperty')]: data.recordId});
                    } else {
                        try {
                            await proxy.promiseLoadRecord(data.recordId);
                        } catch (/* serverError */ e) {
                            if (e.code !== 403) {
                                console.error(`[broadcastClient] can't load record ${data.model} ${data.recordId}`, e);
                            }
                        }
                    }
                }
            }

        } catch (e) {
            console.error(`[broadcastClient] error processing event: `,event, e);
        }

    };

    socket.onclose = async (event) => {
        if (event.wasClean) {
            console.error(`[close] Connection closed cleanly, code=${event.code} reason=${event.reason}`);
        } else {
            // e.g. server process killed or network down
            // event.code is usually 1006 in this case
            console.error('[broadcastClient] Connection died');
        }
        await randWait(5000, 10000);
        init();
    };

    socket.onerror = async (error) => {
        console.error(`[broadcastClient] error:`, error);
    };
};

export default init;
