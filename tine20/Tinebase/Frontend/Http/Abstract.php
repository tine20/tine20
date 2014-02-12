<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http interface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Http_Abstract extends Tinebase_Frontend_Abstract
{
    /**
     * generic export function
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_options format/definition id
     * @param Tinebase_Controller_Record_Abstract $_controller
     * @return void
     * 
     * @todo support single ids as filter?
     * @todo use stream here instead of temp file?
     */
    protected function _export(Tinebase_Model_Filter_FilterGroup $_filter, $_options, Tinebase_Controller_Record_Abstract $_controller = NULL)
    {
        // extend execution time to 30 minutes
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(1800);
        
        // get export object
        $export = Tinebase_Export::factory($_filter, $_options, $_controller);
        $format = $export->getFormat();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exporting ' . $_filter->getModelName() . ' in format ' . $format);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_options, TRUE));

        switch ($format) {
            case 'pdf':
                $ids = $_controller->search($_filter, NULL, FALSE, TRUE, 'export');
                
                // loop records
                foreach ($ids as $id) {
                    if (! empty($id)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating pdf for ' . $_filter->getModelName() . '  id ' . $id);
                        $record = $_controller->get($id);
                        $export->generate($record);
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $_filter->getModelName() . ' id empty!');
                    }
                }
                    
                // render pdf
                try {
                    $pdfOutput = $export->render();
                } catch (Zend_Pdf_Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
                    exit;
                }
                
                break;
                
            case 'ods':
                $result = $export->generate();
                break;
            case 'csv':
            case 'xls':
            case 'doc':
            case 'docx':
                $result = $export->generate($_filter);
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Format ' . $format . ' not supported.');
        }

        // write headers
        $contentType = $export->getDownloadContentType();
        $filename = $export->getDownloadFilename($_filter->getApplicationName(), $format);
        header("Pragma: public");
        header("Cache-Control: max-age=0");
        header("Content-Disposition: " . (($format == 'pdf') ? 'inline' : 'attachment') . '; filename=' . $filename);
        header("Content-Description: $format File");
        header("Content-type: $contentType");
        
        // output export file
        switch ($format) {
            case 'pdf':
                echo $pdfOutput;
                break;
            case 'xls':
            case 'doc':
            case 'docx':
                // redirect output to client browser
                $export->write($result);
                break;
            default:
                readfile($result);
                unlink($result);
        }
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
    }

    /**
     * download (fpassthru) file node
     * 
     * @param Tinebase_Model_Tree_Node $node
     * @param string $filesystemPath
     */
    protected function _downloadFileNode($node, $filesystemPath)
    {
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Download file node ' . print_r($node->toArray(), TRUE));
        
        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        header('Content-Disposition: attachment; filename="' . $node->name . '"');
        header("Content-Type: " . $node->contenttype);
        
        $handle = fopen($filesystemPath, 'r');
        fpassthru($handle);
        fclose($handle);

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
    }
}
