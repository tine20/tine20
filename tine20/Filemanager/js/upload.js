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
    let tasks = [];

    try {
        files = resolvePaths(targetFolderPath, files);
        const folders = getSortedFolders(files);
        const response = await Tine.Filemanager.searchNodes([
            {field: 'path', operator: 'equals', value: targetFolderPath}
        ]);
        
        const existNodeList = response.results;
        tasks = _.concat(tasks, createFolderTasks(targetFolderPath, folders, existNodeList));
    
        _.each(folders, (folder) => {
            const filesToUpload = getFilesToUpload(files, folder);
            tasks = _.concat(tasks, createUploadFileTasks(targetFolderPath, filesToUpload, existNodeList));
        });

        await Tine.Tinebase.uploadManager.queueUploads(tasks);
    } catch (e) {
        const app = Tine.Tinebase.appMgr.get('Filemanager');
        Ext.MessageBox.alert(
            i18n._('Upload Failed'),
            app.i18n._(e.message)
        ).setIcon(Ext.MessageBox.ERROR);
    }
}

/**
 * creat folder task
 *
 * - create node in NodeGridPanel
 * @param targetFolderPath
 * @param folders
 * @param existNodeList
 * @returns {Promise<{path, taskId: *}>}
 */
function createFolderTasks(targetFolderPath, folders, existNodeList) {
    const folderTasks = [];
    
    _.each(folders, (folder)=> {
        const pathArray = _.compact(folder.split('/'));
    
        if (pathArray.length === 0) {
            return;
        }
        
        folder = _.startsWith(folder, '/') ? folder.slice(1) : folder;
        const uploadId = `${targetFolderPath}${folder}/`;
        const folderName = _.last(_.compact(_.split(folder, '/')));
        const [existNode] = _.filter(existNodeList, {path: `${uploadId}`});
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
            tag: 'queue',
            label: uploadId,
            folderDependencies: getFolderDependencyPaths(targetFolderPath, folder),
            status: nodeData.status,
            args: _.assign({
                uploadId,
                nodeData,
                targetFolderPath,
            })
        };
        folderTasks.push(task);
    });
    
    return folderTasks;
}

/**
 * create upload file tasks
 *
 * - create node in NodeGridPanel
 * @param targetFolderPath
 * @param filesToUpload
 * @param existNodeList
 * @returns {Promise<void>}
 */
function createUploadFileTasks(targetFolderPath, filesToUpload, existNodeList) {
    const fileTasks = [];
    
    _.each(filesToUpload, (file)=> {
        const fileObject = file.fileObject;
        const uploadId = file.uploadId;
        const [existNode] = _.filter(existNodeList, {path: uploadId});
        const parentFolderPath = uploadId.replace(file.name, '');

        const nodeData = existNode ?? Tine.Filemanager.Model.Node.getDefaultData({
            name: _.get(file, 'name'),
            type: 'file',
            path: `${uploadId}`,
            id: Tine.Tinebase.data.Record.generateUID()
        });
    
        nodeData.last_upload_time = nodeData.creation_time;
        nodeData.status = 'pending';
        nodeData.size = _.get(file, 'size');
        nodeData.progress = -1;
        nodeData.contenttype = `vnd.adobe.partial-upload; final_type=${_.get(file, 'type')}; progress=${nodeData.progress}`;
        
        // monitor UI needs every file node , grid panel will filter itself
        window.postal.publish({
            channel: "recordchange",
            topic: 'Filemanager.Node.create',
            data: nodeData
        });
        
        const task = {
            handler: "FilemanagerUploadFileTask",
            tag: 'queue',
            label: uploadId,
            folderDependencies: getFolderDependencyPaths(targetFolderPath, file.fullPath),
            status: nodeData.status,
            args: _.assign({
                batchID: file.batchID,
                overwrite: false,
                uploadId, 
                fileObject,
                nodeData,
                fileSize: file.size,
                existNode: existNode ? existNode : null,
                targetFolderPath: parentFolderPath
            })
        };
    
        fileTasks.push(task);
    });
    
    return fileTasks;
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
    let parentPathArray = _.compact(folder.split('/'));
    return _.filter(files, (file) => {
        const filePathArray = _.compact(file.fullPath.split('/'));
        if (filePathArray.length - parentPathArray.length === 1) {
            return file.fullPath.includes(`${folder}/`);
        }
    });
}

/**
 * get folder dependency paths
 *
 * @returns {*}
 * @param targetFolderPath
 * @param path
 */
function getFolderDependencyPaths(targetFolderPath, path) {
    let pathArray = _.compact(path.split('/'));
    const depPaths = [];
    
    if (pathArray.length > 1) {
        pathArray.pop();
        let depBasePath = targetFolderPath;
        _.each(pathArray, (subFolder) => {
            depBasePath = `${depBasePath}${subFolder}/`;
            depPaths.push(depBasePath);
        });
    }

    return depPaths;
}

export default upload
