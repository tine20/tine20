<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class Sales_Export_Document
 */
class Sales_Export_DocumentPdf extends Sales_Export_Document
{
    use Tinebase_Export_DocumentPdfTrait;

    // we need to set locale etc before loading twig, so we overwrite _loadTwig
    protected function _loadTwig()
    {
        if (class_exists('OnlyOfficeIntegrator_Config') &&
            Tinebase_Application::getInstance()->isInstalled(OnlyOfficeIntegrator_Config::APP_NAME)) {
            $this->_useOO = true;
        }

        parent::_loadTwig();
    }

    protected function _getOldFormat()
    {
        return 'docx';
    }
}
