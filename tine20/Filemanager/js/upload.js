/*
 * Tine 2.0
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * upload(files)
 *
 * uploadFiles does:
 * - sorts out which folders to create
 * - creates the uploadFolder and uploadFiles tasks with deps
 * - upload(Folder/File) tasks post messages with postal message bus
 * - gridPanel/treePanel listens to message bus and adds/update files and folders
 * - files have progress and info about transitional state (the (empty) file is not yet created)
 * - folders have transitional state only (not yet created, just in uploadManager)
 */
async function upload(targetFolderPath, files) {
    // TODO: in the future we might have yes too all button , it marks the batchID with forceToWrite flag
    const batchID = Ext.id();
    files.forEach((file) => {file.batchID = batchID});
    await createTasks(targetFolderPath, files);
}

/**
 * create tasks for folder creation and file uploads
 * 
 * - the priority of folder creation is based on deep level

 * @param targetFolderPath
 * @param files
 * @returns {Promise<[]>}
 */
async function createTasks(targetFolderPath, files) {
    // try to generate the id here which is used for grid update
    const taskIDs = [];
    let fileTasks = [];
    
    try {
        files = resolvePaths(targetFolderPath, files);
        const folders = getSortedFolders(files);
        const response = await Tine.Filemanager.searchNodes([
            {field: 'path', operator: 'equals', value: targetFolderPath}
        ]);
        
        const existFileList = response.results;
        
        await _.reduce(folders, (prev, folder) =>  {
            return prev.then(async (result) => {
                const pathArray = _.compact(folder.split('/'));
                
                if (pathArray.length > 0) {
                    const task = await createFolderTask(targetFolderPath, folder, taskIDs, existFileList);
                    taskIDs.push(task);
                }

                const filesUpload = getFilesToUpload(files, folder);
                const tasks = await createUploadFileTasks(filesUpload, taskIDs, existFileList);
                fileTasks = _.concat(fileTasks, tasks);
                
                return Promise.resolve();
            })
        }, Promise.resolve());
        
        await Tine.Tinebase.uploadManager.queueUploads(fileTasks);
        
        return taskIDs;
    } catch (e) {
        console.err(e.message);
    }
}

/**
 * creat folder task
 * 
 * - create node in NodeGridPanel
 * @param targetFolderPath
 * @param folder
 * @param taskIDs
 * @param existFileList
 * @returns {Promise<{path, taskId: *}>}
 */
async function createFolderTask(targetFolderPath, folder, taskIDs, existFileList) {
    folder = _.startsWith(folder, '/') ? folder.slice(1) : folder;
    const uploadId = `${targetFolderPath}${folder}/`;
    const folderName = _.last(_.compact(_.split(folder, '/')));
    const [existNode] = _.filter(existFileList, {path: `${uploadId}`});
    const nodeData = Tine.Filemanager.Model.Node.getDefaultData({
        name: folderName,
        type: 'folder',
        status: 'pending',
        path: `${uploadId}`,
        id: Tine.Tinebase.data.Record.generateUID()
    });
    
    if (!existNode) {
        window.postal.publish({
            channel: "recordchange",
            topic: 'Filemanager.Node.create',
            data: nodeData
        });
    }
    
    const task = {
        handler: "FilemanagerCreateFolderTask",
        tag: 'folder',
        label: uploadId,
        dependencies: getTaskDependencies(taskIDs, folder),
        status: nodeData.status,
        args: _.assign({
            uploadId,
            nodeData,
            existNode: existNode
        })
    };

    const taskId = await Tine.Tinebase.uploadManager.queueUploads([task]);
    return {path: folder, taskId: taskId};
}

/**
 * create upload file tasks
 * 
 * - create node in NodeGridPanel
 * @param filesToUpload
 * @param taskIDs
 * @param existFileList
 * @returns {Promise<void>}
 */
async function createUploadFileTasks(filesToUpload, taskIDs, existFileList) {
    const tasks = [];
    
    await _.reduce(filesToUpload, async (prev, file) => {
        const fileObject = file.fileObject;
        const uploadId = file.uploadId;
        const folder = file.fullPath.replace(file.name, '');
        const [existNode] = _.filter(existFileList, {path: uploadId});

        const nodeData = Tine.Filemanager.Model.Node.getDefaultData({
            name: _.get(file, 'name'),
            type: 'file',
            status: 'pending',
            path: `${uploadId}`,
            size: _.get(file, 'size'),
            progress: 0,
            contenttype: `vnd.adobe.partial-upload; final_type=${_.get(file, 'type')}`,
            id: Tine.Tinebase.data.Record.generateUID()
        });
    
        nodeData.last_upload_time = nodeData.creation_time;
        
        // monitor UI needs every file node , grid panel will filter itself
        window.postal.publish({
            channel: "recordchange",
            topic: 'Filemanager.Node.create',
            data: nodeData
        });
        

        const task = {
            handler: "FilemanagerUploadFileTask",
            tag: 'file',
            label: uploadId,
            dependencies: getTaskDependencies(taskIDs, folder),
            status: nodeData.status,
            args: _.assign({
                batchID: file.batchID,
                overwrite: false,
                uploadId, 
                fileObject,
                nodeData,
                fileSize: fileObject.size,
                existNodeId: existNode ? existNode.id : nodeData.id,
            })
        };
        
        tasks.push(task);
        return Promise.resolve();
    }, Promise.resolve());
    
    return tasks;
}

/**
 * resolve uploadId for file
 * 
 * @param targetFolderPath
 * @param files
 */
function resolvePaths(targetFolderPath, files) {
    return _.map(files, (fo) => {
        fo.fullPath.replace(/(\/ | \/)/, '/');
        fo.fullPath = ! _.startsWith(fo.fullPath, '/') ? `/${fo.fullPath}` : fo.fullPath;
        return _.set(fo, 'uploadId', `${targetFolderPath}${fo.fullPath.slice(1)}`);
    })
}

/**
 * create sorted folders from files 
 * 
 * @param files
 */
function getSortedFolders(files) {
    // get uniq folder tree
    let folders = _.uniq(_.map(files, (fo) => {
        return fo.fullPath.replace(/\/[^/]*$/, '');
    }));

    // fill potential gaps in folder list as we don't have mkdir -p
    folders = _.each(folders, (folder) => {
        _.reduce(_.compact(folder.split('/')), (prefix, part) => {
            const folder = `${prefix}/${part}`;
            if (_.indexOf(folders, folder) < 0) folders.push(folder)
            return folder;
        }, '')
    }).sort();

    // sort folder by deep level
    return _.sortBy(folders, (folder)=> {return _.split(folder, '/').length});
}

/**
 * get files to upload from specific folder path
 * 
 * @param files
 * @param folder
 */
function getFilesToUpload(files, folder) {
    return _.filter(files, (file) => {
        return _.get(file, 'fullPath').replace(_.get(file, 'name'), '') === `${folder}/`;
    });
}

/**
 * get task dependencies based on folder path
 * 
 * @param taskIds
 * @param folder
 * @returns {*}
 */
function getTaskDependencies(taskIds, folder) {
    let deps = _.filter(taskIds, (task) => {
        if (task) return folder.includes(`${task.path}/`)
    })
    
    return _.map(deps, 'taskId').flat();
}

export default upload
