/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const buffered = {};
const throttled = {};
const openTransactions = [];

const constrainedRand = (min, max) => {
    return Math.max(max, Math.round(Math.random() * max) + min);
};

const wait = async(timeout) => {
    return new Promise((resolve) => {
        window.setTimeout(resolve, timeout);
    });
}

const randWait = async (min = 0, max = 2000) => {
    return wait(constrainedRand(min, max));
};

let running = false;

const instanceId = Tine.Tinebase.data.Record.generateUID(5);
const bc = new BroadcastChannel('Tine.Tinebase.breadcastClient');
bc.onmessage = (event) => {
    if (event.data.cmd === 'isRunning' && running) {
        bc.postMessage({ instanceId: instanceId, status: 'running' });
    }
}

const getRunning = async () => {
    const bc = new BroadcastChannel('Tine.Tinebase.breadcastClient');
    const running = [];

    bc.onmessage = (event) => {
        if (event.data.instanceId && event.data.status === 'running') {
            running.push(event.data.instanceId);
        }
    }
    bc.postMessage({cmd: 'isRunning'});
    await wait(200);
    bc.close();

    return running;
};

const init = async () => {
    await randWait(); // give browser time to breathe

    // make sure one instance is running only
    if (getRunning().length) {
        await randWait(30000, 60000);
        return init();
    }

    const auth = await Tine.Tinebase.getAuthToken(['broadcasthub'], 100);
    const wsUrl = Tine.Tinebase.configManager.get('broadcasthub').url;
    const socket = new WebSocket(wsUrl);
    let authResponse = null;

    socket.onopen = async (e) => {
        running = true;
        socket.send({ token: auth.auth_token, jsonApiUrl: Tine.Tinebase.common.getUrl() });
    };

    socket.onmessage = async (event) => {
        if (!authResponse) {
            authResponse = event.data;
            if (authResponse !== 'AUTHORIZED') {
                console.error(`[broadcastClient] not authorised: code=${event.code} ${event.data}`);
                running = false;
            }
            return;
        }

        try {
            const data = JSON.parse(event.data);
            const topicPrefix = String(data.model).replace('_Model_', '.');
            const topics = [`${topicPrefix}.*`, `${topicPrefix}.${data.recordId}`];

            if (topics.filter(value => postal.subscriptions.recordchange.hasOwnProperty(value)).length) {
                if (! await isOwnEvent(data)) {
                    handleEvent(data);
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
        running = false;
        await randWait(5000, 10000);
        init();
    };

    socket.onerror = async (error) => {
        console.error(`[broadcastClient] error:`, error);
    };
};

const isOwnEvent = async (data) => {
    if(openTransactions.find((t) => { return t.verb === data.verb && t.model === data.model && t.recordId === data.recordId })) {
        // own delete, update or create with fixed id
        return true;
    }

    if (data.verb === 'create') {
        const records = await Promise.all(openTransactions.filter((t) => { return t.verb === data.verb && t.model === data.model }).map((t) => { return t.commitPromise }));
        if (records.map((r) => { return r.getId() }).indexOf(data.recordId) >= 0) {
            // own create with new id matching event id
            return true;
        }
    }
};

const handleEvent = async (data) => {
    if (!buffered[data.model]) {
        buffered[data.model] = [];
    }
    buffered[data.model].push(data);

    if (!throttled[data.model]) {
        throttled[data.model] = _.throttle(_.bind(_.partial(handleEvents, data.model, buffered[data.model])), constrainedRand(1500, 5000));
    }
    throttled[data.model]();
};

const handleEvents = async (model, buffer) => {
    buffer = buffer.splice(0, buffer.length);

    // console.error(`[broadcastClient] processing buffer for ${model}: `, buffer);
    window.postal.publish({
        channel: "recordchange",
        topic: `${model.replace('_Model_', '.')}.bulk`,
        data: buffer
    });
}

const startTransaction = (record, action, timeout) => {
    const transaction = { verb: action, model: record.constructor.getPhpClassName(), recordId: record.getId() };
    openTransactions.push(transaction);

    let commitFn
    transaction.commitPromise = Promise.any([
        new Promise((resolve) => {commitFn = resolve}),
        wait(timeout || 2000).then(() => { return record; })
    ]);

    transaction.commitPromise.finally((record) => {
        const idx = openTransactions.indexOf(transaction);
        if (idx >= 0) {
            openTransactions.splice(idx, 1);
        }
    })

    return commitFn;
};

export { init, startTransaction };
