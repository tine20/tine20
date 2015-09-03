<?php

/**
 * Tine 2.0
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

/**
 * Expressodriver Http frontend
 *
 * This class handles all Http requests for the Expressodriver application
 *
 * @package     Expressodriver
 */
class Expressodriver_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Expressodriver';

    /**
     * download file
     *
     * @param string $path
     * @param string $id
     *
     * @todo allow to download a folder as ZIP file
     */
    public function downloadFile($path, $id)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        if ($path) {
            $node = $nodeController->getFileNode($path);
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
        } else {
            Tinebase_Exception_InvalidArgument('Either a path or id is needed to download a file.');
        }

        $streamwrapperpath = 'external://' . (empty($path) ? $node->path : $path);
        $this->_downloadFileNode($node, $streamwrapperpath);
        exit;
    }
}
