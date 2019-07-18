<?php
/**
 * Tine 2.0
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Filemanager
 *
 * This class handles cli requests for the Filemanager
 *
 * @package     Filemanager
 */
class Filemanager_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Filemanager';

    protected $_defaultDemoDataDefinition = [
        'Filemanager_Model_Node' => 'filemanager_struktur_import_csv'
    ];

    public function csvExportFolder($opt)
    {
        $data = $this->csvExportFolderHelper($opt);
        print_r($data);

        return 0;

    }

    /**
     * give all folder from the root directory(default /shared)
     *
     * @param $opts
     * @param string $parentNodels
     * @param array $paths
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    public function csvExportFolderHelper($opts, $parentNode = '/shared', $paths = array())
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Filemanager_Model_NodeFilter', [
            ['field' => 'path', 'operator' => 'equals', 'value' => $parentNode],
            ['field' => 'type', 'operator' => 'equals', 'value' => 'folder']
        ]);

        $filter->isRecursiveFilter(true);
        $nodes = Filemanager_Controller_Node::getInstance()->search($filter);

        foreach ($nodes as $node) {
            $nodePath = Tinebase_FileSystem::getInstance()->getPathOfNode($node, true);
            $nodePath = array_pop(explode('/shared/', $nodePath));
            $paths[] = $nodePath;

            $childNodes = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($node['id']);

            foreach ($childNodes as $childNode) {
                $childPath = Tinebase_FileSystem::getInstance()->getPathOfNode($childNode, true);
                $childPath = array_pop(explode('/shared/', $childPath));
                $paths[] = $childPath;

                $paths = array_merge($paths, $this->csvExportFolder($opts, '/shared/' . $childPath));
            }
        }

        return $paths;
    }
}
