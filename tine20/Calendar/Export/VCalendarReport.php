<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Calendar
 *
 * TODO create another abstract parent to extend for code sharing?
 */
class Calendar_Export_VCalendarReport extends Tinebase_Export_Abstract
{
    protected $_defaultExportname = 'cal_default_vcalendar_report';
    protected $_format = 'ics';
    protected $_exportClass = Calendar_Export_VCalendar::class;

    /**
     * @var Tinebase_Model_Tree_FileLocation
     */
    protected $_fileLocation = null;

    /**
     * get download content type
     *
     * @return string
     *
     * TODO is this needed? use 'zip' ?
     */
    public function getDownloadContentType()
    {
        return 'text/calendar';
    }

    public static function getPluginOptionsDefinition()
    {
        return [
            // Containers
            'sources' => [
                'label' => 'Containers to export', // _('Containers to export')
                'type' => 'containers',
                'config' => [
                    'recordClassName' => Calendar_Model_Event::class,
                    // TODO needed?
//                    'controllerClassName'           => Tinebase_Record_Path::class,
//                    'filterClassName'               => Tinebase_Model_PathFilter::class,
                ],
                // TODO add validation?
            ],
            // FileLocation
            'target' => [
                'label' => 'Export target', // _('Export target')
                'type' => 'filelocation',
            ]
        ];
    }

    public function generate()
    {
        $this->_checkOptions();

        // TODO define export result - is it an object? recordset of filelocations?
        $exportResult = [];
        foreach ($this->_config->sources->toArray() as $containerData) {
            if (is_string($containerData)) {
                $containerId = $containerData;
            } else if (isset($containerData['id'])) {
                $containerId = $containerData['id'];
            } else {
                // no container / id
                continue;
            }
            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
            $exportResult[] = [
                'filename' => $this->_exportContainer($container),
                'container' => $container,
            ];
        }

        $this->_saveExportFilesToFileLocation($exportResult);

        return $exportResult;
    }

    protected function _checkOptions()
    {
        if (! isset($this->_config->sources) || ! isset($this->_config->target)) {
            throw new Tinebase_Exception_InvalidArgument('sources and/or filelocation options missing / invalid');
        }

        $this->_fileLocation = new Tinebase_Model_Tree_FileLocation($this->_config->target->toArray());
    }

    /**
     * @param Tinebase_Model_Container $container
     * @return string export filename
     */
    protected function _exportContainer(Tinebase_Model_Container $container)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_config->model, [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
        ]);
        $export = new $this->_exportClass($filter, null, [
            'filename' => Tinebase_TempFile::getTempPath(),
        ]);
        return $export->generate();
    }

    /**
     * @param array $exportResult
     * @throws Tinebase_Exception_NotImplemented
     *
     *  TODO support all possible file locations
     */
    protected function _saveExportFilesToFileLocation($exportResult)
    {
        foreach ($exportResult as $generatedExport) {
            switch ($this->_fileLocation->type) {
                case Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE:
                    $filename = $this->_getExportFilename($generatedExport['container']);
                    $tempFile = Tinebase_TempFile::getInstance()->createTempFile($generatedExport['filename']);
                    Tinebase_FileSystem::getInstance()->copyTempfile($tempFile,
                         DIRECTORY_SEPARATOR .  'Filemanager'  . DIRECTORY_SEPARATOR . 'folders' . $this->_fileLocation->fm_path . DIRECTORY_SEPARATOR . $filename);
                    break;
                default:
                    throw new Tinebase_Exception_NotImplemented(
                        'FileLocation type ' . $this->_fileLocation->type . ' not implemented yet');
            }
        }
    }

    /**
     * @param Tinebase_Model_Container $container
     * @return string
     */
    protected function _getExportFilename($container)
    {
        return str_replace([' ', DIRECTORY_SEPARATOR], '', $container->name . '.' . $this->_format);
    }
}
