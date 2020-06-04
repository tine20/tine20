<?php
/**
 * Tine 2.0
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Addressbook
 */
class Addressbook_Export_VCardReport extends Tinebase_Export_Report_Abstract
{
    protected $_defaultExportname = 'adb_default_vcard_report';
    protected $_format = 'vcf';
    protected $_exportClass = Addressbook_Export_VCard::class;

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/directory';
    }

    /**
     * @return array
     */
    public static function getPluginOptionsDefinition()
    {
        return [
            // Containers
            'sources' => [
                'label' => 'Containers to export', // _('Containers to export')
                'type' => 'containers',
                'config' => [
                    'recordClassName' => Addressbook_Model_Contact::class,
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
}
