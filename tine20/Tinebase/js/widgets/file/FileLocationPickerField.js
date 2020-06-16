/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

/*
 ARG: Tinebase_Model_Tree_FileLocation
  - it makes no sense to support upload/download url there, it's a tree class!
  
  what a pity we can't express all tine20 types as url's ;-(
  -> generic problem e.g. for attachments of new records?
  
  
  
 */
Tine.Tinebase.widgets.form.FileLocationPickerField = Ext.extend(Ext.form.TriggerField, {
    /**
     * @cfg {String} mode one of source, target
     */
    mode: 'target',
    
    /**
     * @cfg {String} types
     * coma separated list of enabled types
     * 
     * in src mode:
     *  fm_node, upload
     *  (system_link, url (supported schemas?), record_attachment, email, attachment of a filed email)
     *  
     * in target mode:
     *  fm_node, download, record_attachment
     *   ((new) mail (attachment, system_link, download_link))
     */
    types: 'fm_node,local',
    
    allow_multiple: false, // src mode only
    constraints: 'file/folder/extensions, ...', // src + target mode
    create_new: false, // target only
    
    // download: 'sofort, vs zum download vormerken', // immer vormerken
    // upload: '', // wird sofort hochgeladen
    
    /**
     * @property {String} fileName
     */
    fileName: '', // tgt only, needed for new files, downloads
    
    // att least we end up with a fileLocation object:
    /*
        mode:
        type: ''
        name, mimeType, size
        id,
        record_id, appName, modelName, tempFileId
        file|folder?
        
     */
    
    // FileLocation specifiers
    
    // SRC MODE
    // fm_node (file src): fm_path|node_id
    // fm_node (dir src): fm_path|node_id
    // recordAttachment (src):  appName, modelName, recordId
    // upload (src): tempFileId
    // --
    // fs_node ???
    // system_link (src): url
    // url (src): url
    
    // TGT MODE
    // fm_node (file tgt): fm_path, (dir_(fm_path|node_id)), fileName)
    // fm_node (dir tgt): fm_path|node_id
    // recordAttachment (tgt): appName, modelName, recordId, fileName
    // download (tgt): fileName, session? (who is responsible for "fetching" the download? - should be blocking interface?)
    // --
    // fs_node ???
    // url (tgt): url
    // email (tgt): ... open email dlg with attach in some temp location?
    // email (tgt): fileName, dstEmailAddress, attachmentType(systemLink not appropriate)
    

    // URl's again (object to string):
    // productname+type://
    // tine20+filemanager://some/filemanager/path
    // tine20+attachment://appName/modelName/recordId/fileName // not bijective from fm_path
    // tine20+upload://sessionId/tempFileId // client has no sessionID!!!
    // tine20+download://sessionId/fileName
    // --
    // tine20+email://
    
    
    
    
    
    // type & type options so was wie dynamic record?
    // type & type_options vs. properties eventually used
    /*
    {
        "type": "fm_node",
        "type_options": {
            "path": "/path/to/node"
            "id": ""
        }
    }
    
    {
        "type": "record_attachment",
        "type_options" : {
            "Application" : "Calendar" or empty if enclosed in Model
            "Model" : "Event" | "Calendar_Model_Event" | "Calendar.Event"
            "record_id"
            
        }
    }
     */
});
