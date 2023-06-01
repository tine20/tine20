<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class HumanResources_Import_OpenHolidaysApi extends Tinebase_Import_Abstract
{
    public const OPT_LANGUAGE = 'language';
    public const OPT_COUNTRY = 'country';
    public const OPT_SUBDIVISION = 'subdivision';
    public const OPT_FROM = 'from';
    public const OPT_TO = 'to';
    public const OPT_CALENDAR_NAME = 'calendarName';
    public const OPT_CALENDAR_ID = 'calendarId';

    /**
     * additional config options (to be added by child classes)
     *
     * @var array
     */
    protected $_additionalOptions = [
        'model' => Tinebase_Model_BankHoliday::class,
        self::OPT_LANGUAGE => 'DE',
        self::OPT_COUNTRY => 'DE',
        self::OPT_SUBDIVISION => '',
        self::OPT_FROM => '',
        self::OPT_TO => '',
        self::OPT_CALENDAR_NAME => '',
        self::OPT_CALENDAR_ID => '',
    ];

    protected $_data = [];
    protected $_calId = null;

    public function __construct(array $_options = array())
    {
        unset($_options['model']);
        if (empty($_options[self::OPT_FROM] ?? null)) {
            $_options[self::OPT_FROM] = date('Y-01-01');
        }
        if (empty($_options[self::OPT_TO] ?? null)) {
            $_options[self::OPT_TO] = (date('Y') + 2) . '-12-31';
        }
        parent::__construct($_options);
        $this->_controller = Tinebase_Controller_BankHoliday::getInstance();
    }

    /**
     * do something before the import
     *
     * @param mixed $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        //https://openholidaysapi.org/PublicHolidays?countryIsoCode=DE&languageIsoCode=DE&validFrom=2023-01-01&validTo=2023-12-31&subdivisionCode=DE-BY
        $apiData = file_get_contents(
            'https://openholidaysapi.org/PublicHolidays?countryIsoCode=' . urlencode($this->_options[self::OPT_COUNTRY]) .
            '&languageIsoCode=' . urlencode($this->_options[self::OPT_COUNTRY]) . '&validFrom=' .
            urlencode($this->_options[self::OPT_FROM]) . '&validTo=' .urlencode($this->_options[self::OPT_TO]) .
            (!empty($this->_options[self::OPT_SUBDIVISION]) ? '&subdivisionCode=' . $this->_options[self::OPT_SUBDIVISION] : '')
        );
        $this->_data = json_decode($apiData, true);

        if (!is_array($this->_data) || empty($this->_data) || !isset($this->_data[0]['id']) ||
                !isset($this->_data[0]['startDate']) || !isset($this->_data[0]['endDate']) ||
                !isset($this->_data[0]['type']) || !isset($this->_data[0]['name'][0]['text'])) {
            throw new Tinebase_Exception_Backend('could not load data from openholidaysapi');
        }

        if (empty($this->_options[self::OPT_CALENDAR_ID])) {
            if (empty($this->_options[self::OPT_CALENDAR_NAME])) {
                $this->_options[self::OPT_CALENDAR_NAME] = 'Public Holidays ' . $this->_options[self::OPT_COUNTRY] .
                    (empty($this->_options[self::OPT_SUBDIVISION]) ? '' : ' ' . $this->_options[self::OPT_SUBDIVISION]);
            }

            if (null === ($calId = Tinebase_Controller_BankHolidayCalendar::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_BankHolidayCalendar::class, [
                        [TMFA::FIELD => Tinebase_Model_BankHolidayCalendar::FLD_NAME, TMFA::OPERATOR => 'equals', TMFA::VALUE => $this->_options[self::OPT_CALENDAR_NAME]],
                    ])
                )->getFirstRecord())) {
                $calId = Tinebase_Controller_BankHolidayCalendar::getInstance()->create(
                    new Tinebase_Model_BankHolidayCalendar([
                        Tinebase_Model_BankHolidayCalendar::FLD_NAME => $this->_options[self::OPT_CALENDAR_NAME],
                    ])
                )->getId();
            } else {
                $calId = $calId->getId();
            }
        } else {
            $calId = $this->_options[self::OPT_CALENDAR_ID];
        }

        $this->_calId = $calId;
    }

    protected function _getRawData(&$_resource)
    {
        while (true) {
            if (false === ($holiday = current($this->_data))) {
                return false;
            }

            next($this->_data);

            if (($holiday['type'] ?? null) !== 'Public' || !isset($holiday['id']) ||
                    !isset($holiday['startDate']) || !isset($holiday['endDate']) ||
                    !isset($holiday['name'][0]['text'])) {
                continue;
            }
            try {
                return [
                    // this is quite important, same holiday for different subdivisions has the same id
                    'id' => md5($this->_calId . $holiday['id']),
                    Tinebase_Model_BankHoliday::FLD_NAME => $this->getStringFromArray($holiday['name'], 'language',
                        $this->_options[self::OPT_LANGUAGE], 'text'),
                    Tinebase_Model_BankHoliday::FLD_DATE => $holiday['startDate'],
                    Tinebase_Model_BankHoliday::FLD_CALENDAR_ID => $this->_calId,
                ];
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_Exception::log($tenf);
            }
        }
    }

    protected function getStringFromArray(array $data, string $searchKey, string $searchValue, string $returnKey): string
    {
        $defaultValue = null;
        foreach ($data as $entry) {
            if (($entry[$searchKey] ?? null) === $searchValue && is_string($entry[$returnKey] ?? null)) {
                return $entry[$returnKey];
            }
            if (is_string($entry[$returnKey] ?? null)) {
                $defaultValue = $entry[$returnKey];
            }
        }
        if (is_string($defaultValue)) {
            return $defaultValue;
        }

        throw new Tinebase_Exception_NotFound($searchKey . ' => ' . $searchValue . ' for ' . $returnKey . ' in ' . print_r($data, true) . ' not found');
    }
}