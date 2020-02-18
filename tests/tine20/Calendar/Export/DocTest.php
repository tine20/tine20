<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */


/**
 * Calendar Doc generation class tests
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Calendar_Export_DocTest extends Calendar_TestCase
{
    public function testExportSimpleDocSheet()
    {
        // @TODO have some demodata to export here
        $filter = new Calendar_Model_EventFilter(array(
//            array('field' => 'period', 'operator' => 'within', 'value' => array(
//                'from' => '',
//                'until' => ''
//            ))
        ));
        $doc = new Calendar_Export_Doc($filter);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }

    public function testExportCalendarResourceBrokenRelation()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_fn' => 'a'
        ]));

        $resourceTest = new Calendar_Controller_ResourceTest();
        $resourceTest->setUp();
        $resource = $resourceTest->testCreateResource();
        $resource->relations = [
            new Tinebase_Model_Relation([
                'related_degree' => Tinebase_Model_Relation::DEGREE_CHILD,
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id' => $contact->getId(),
                'type' => 'STANDORT'
            ], true),
            new Tinebase_Model_Relation([
                'related_degree' => Tinebase_Model_Relation::DEGREE_CHILD,
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id' => $this->_personas['sclever']->contact_id,
                'type' => 'STANDORT'
            ], true)
        ];
        Calendar_Controller_Resource::getInstance()->update($resource);

        Addressbook_Controller_Contact::getInstance()->getBackend()->delete($contact->getId());

        $filter = new Calendar_Model_ResourceFilter();
        $export = new Calendar_Export_Resource_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                        'model' => Calendar_Model_Resource::class,
                        'name' => 'cal_resource_doc'
                    ]))->getFirstRecord()->getId()
            ]);
        $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(
            Tinebase_Record_Abstract::generateUID()));

        $tempfile = Tinebase_TempFile::getTempPath() . '.docx';
        $export->generate();
        $export->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }

    public function testExportCalendarResource()
    {
        $resourceTest = new Calendar_Controller_ResourceTest();
        $resourceTest->setUp();
        $resource = $resourceTest->testCreateResource();
        $resource->relations = [
            new Tinebase_Model_Relation([
                'related_degree' => Tinebase_Model_Relation::DEGREE_CHILD,
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id' => $this->_personas['sclever']->contact_id,
                'type' => 'STANDORT'
            ], true)
        ];
        Calendar_Controller_Resource::getInstance()->update($resource);

        $filter = new Calendar_Model_ResourceFilter();
        $export = new Calendar_Export_Resource_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => Calendar_Model_Resource::class,
                    'name' => 'cal_resource_doc'
                ]))->getFirstRecord()->getId()
            ]);

        $tempfile = Tinebase_TempFile::getTempPath() . '.docx';
        $export->generate();
        $export->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
        unlink($tempfile);
    }

    public function testEventFromFEExport()
    {
        // {"recordData":{"creation_time":"2018-03-22 18:54:11","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:56:17","last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"3","container_id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","id":"3bf67bfb3ec80968c010dd67f9ed94e2f6644166","dtend":"2018-03-22 12:30:00","transp":"OPAQUE","class":"PUBLIC","description":"","geo":null,"location":"","organizer":{"creation_time":"2018-03-22 18:41:45","created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","id":"e265d7b1f7e19151dcb79860a9b40de94681be17","tid":"","private":"","cat_id":"","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","bday":"","org_name":"","org_unit":null,"salutation":null,"title":null,"role":null,"assistent":null,"room":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_locality":null,"adr_one_region":null,"adr_one_postalcode":null,"adr_one_countryname":null,"adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":null,"adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":null,"tel_cell":null,"tel_fax":null,"tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":null,"tel_fax_home":null,"tel_cell_private":null,"tel_other":null,"tel_prefer":null,"email":"vagrant@example.org","email_home":null,"url":null,"url_home":null,"freebusy_uri":null,"calendar_uri":null,"note":null,"tz":null,"pubkey":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","tags":"","notes":"","relations":"","customfields":"","attachments":"","paths":"","type":"user","memberroles":"","industry":null,"groups":""},"priority":null,"status":"CONFIRMED","summary":"test","url":null,"uid":"015acb6711f67e49693c4366920c2e05ee809731","attendee":[{"id":"47c02841a720e0e9eea005a8681e74b6c2f3606c","cal_event_id":"3bf67bfb3ec80968c010dd67f9ed94e2f6644166","user_id":{"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","preferred_address":"0","adr_one_countryname":null,"adr_one_locality":null,"adr_one_postalcode":null,"adr_one_region":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_lon":null,"adr_one_lat":null,"adr_two_countryname":null,"adr_two_locality":null,"adr_two_postalcode":null,"adr_two_region":null,"adr_two_street":null,"adr_two_street2":null,"adr_two_lon":null,"adr_two_lat":null,"assistent":null,"bday":null,"calendar_uri":null,"email":"vagrant@example.org","email_home":null,"freebusy_uri":null,"geo":null,"note":null,"container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","pubkey":null,"role":null,"room":null,"salutation":null,"title":null,"tz":null,"url":null,"url_home":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"org_name":"","org_unit":null,"tel_assistent_normalized":null,"tel_assistent":null,"tel_car_normalized":null,"tel_car":null,"tel_cell_normalized":null,"tel_cell":null,"tel_cell_private_normalized":null,"tel_cell_private":null,"tel_fax_normalized":null,"tel_fax":null,"tel_fax_home_normalized":null,"tel_fax_home":null,"tel_home_normalized":null,"tel_home":null,"tel_other_normalized":null,"tel_other":null,"tel_pager_normalized":null,"tel_pager":null,"tel_prefer_normalized":null,"tel_prefer":null,"tel_work_normalized":null,"tel_work":null,"industry":null,"created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","creation_time":"2018-03-22 18:41:45","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","type":"user","syncBackendIds":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14"},"user_type":"user","role":"REQ","quantity":"1","status":"ACCEPTED","status_authkey":"df1bf89fccd555a79197b011133510f074c4ef2a","displaycontainer_id":{"id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","name":"Tine 2.0 Admin Account's personal calendar","type":"personal","owner_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","color":"#FF6600","order":"0","backend":"Sql","application_id":"08eaeea17f4d7412b7412967c9f164b773337f73","content_seq":"3","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 18:53:53","last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:53:53","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Calendar_Model_Event","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":true,"editGrant":true,"deleteGrant":true,"privateGrant":true,"exportGrant":true,"syncGrant":true,"adminGrant":true,"freebusyGrant":true,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/personal/2eb228ee28ba578744c82bb79ac8be1b0e0a9a14/58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7"},"transp":"OPAQUE","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 18:54:11","last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:56:17","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"3","fbInfo":"<div class=\"cal-fbinfo-state cal-fbinfo-state-free\" tine:calendar-event-id=\"3bf67bfb3ec80968c010dd67f9ed94e2f6644166\"  tine:calendar-freebusy-state-id=\"0\"  ></div>"}],"alarms":[],"tags":[],"dtstart":"2018-03-22 11:30:00","recurid":null,"base_event_id":null,"exdate":null,"rrule":null,"poll_id":null,"mute":"","is_all_day_event":false,"rrule_until":"","rrule_constraints":null,"originator_tz":"Europe/Berlin","readGrant":true,"editGrant":true,"deleteGrant":true,"exportGrant":true,"freebusyGrant":true,"privateGrant":true,"syncGrant":true,"customfields":{"testCF":"myTestValue"},"relations":[{"id":"63718ae54c71c8858a27c42f22ca4778d15076bb","own_model":"Calendar_Model_Event","own_backend":"Sql","own_id":"3bf67bfb3ec80968c010dd67f9ed94e2f6644166","related_degree":"sibling","related_model":"Addressbook_Model_Contact","related_backend":"Sql","related_id":"e265d7b1f7e19151dcb79860a9b40de94681be17","type":"","remark":null,"created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 18:54:24","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"0","related_record":{"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","preferred_address":"0","adr_one_countryname":null,"adr_one_locality":null,"adr_one_postalcode":null,"adr_one_region":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_lon":null,"adr_one_lat":null,"adr_two_countryname":null,"adr_two_locality":null,"adr_two_postalcode":null,"adr_two_region":null,"adr_two_street":null,"adr_two_street2":null,"adr_two_lon":null,"adr_two_lat":null,"assistent":null,"bday":null,"calendar_uri":null,"email":"vagrant@example.org","email_home":null,"freebusy_uri":null,"geo":null,"note":null,"container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","pubkey":null,"role":null,"room":null,"salutation":null,"title":null,"tz":null,"url":null,"url_home":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"org_name":"","org_unit":null,"tel_assistent_normalized":null,"tel_assistent":null,"tel_car_normalized":null,"tel_car":null,"tel_cell_normalized":null,"tel_cell":null,"tel_cell_private_normalized":null,"tel_cell_private":null,"tel_fax_normalized":null,"tel_fax":null,"tel_fax_home_normalized":null,"tel_fax_home":null,"tel_home_normalized":null,"tel_home":null,"tel_other_normalized":null,"tel_other":null,"tel_pager_normalized":null,"tel_pager":null,"tel_prefer_normalized":null,"tel_prefer":null,"tel_work_normalized":null,"tel_work":null,"industry":null,"created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","creation_time":"2018-03-22 18:41:45","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","type":"user","syncBackendIds":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14"}}],"attachments":[],"notes":[]},"format":"","definitionId":"ee9521b09937187eda6cb6f1667ed7ece19f26a7"}
        // {"recordData":{"creation_time":"2018-03-22 19:03:19","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","id":"853c34ffcc82b194f54003ea0008835e5a0c3938","dtend":"2018-03-22 14:00:00","transp":"OPAQUE","class":"PUBLIC","description":"","geo":null,"location":"","organizer":{"creation_time":"2018-03-22 18:41:45","created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","id":"e265d7b1f7e19151dcb79860a9b40de94681be17","tid":"","private":"","cat_id":"","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","bday":"","org_name":"","org_unit":null,"salutation":null,"title":null,"role":null,"assistent":null,"room":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_locality":null,"adr_one_region":null,"adr_one_postalcode":null,"adr_one_countryname":null,"adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":null,"adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":null,"tel_cell":null,"tel_fax":null,"tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":null,"tel_fax_home":null,"tel_cell_private":null,"tel_other":null,"tel_prefer":null,"email":"vagrant@example.org","email_home":null,"url":null,"url_home":null,"freebusy_uri":null,"calendar_uri":null,"note":null,"tz":null,"pubkey":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","tags":"","notes":"","relations":"","customfields":"","attachments":"","paths":"","type":"user","memberroles":"","industry":null,"groups":""},"priority":null,"status":"CONFIRMED","summary":"test2","url":null,"uid":"9f398de7b3a754a6a5d348f34b5f85e1a6876931","attendee":[{"id":"be8b904a579367f3bad89a40b47fdb4beaa17e09","cal_event_id":"853c34ffcc82b194f54003ea0008835e5a0c3938","user_id":{"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","preferred_address":"0","adr_one_countryname":null,"adr_one_locality":null,"adr_one_postalcode":null,"adr_one_region":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_lon":null,"adr_one_lat":null,"adr_two_countryname":null,"adr_two_locality":null,"adr_two_postalcode":null,"adr_two_region":null,"adr_two_street":null,"adr_two_street2":null,"adr_two_lon":null,"adr_two_lat":null,"assistent":null,"bday":null,"calendar_uri":null,"email":"vagrant@example.org","email_home":null,"freebusy_uri":null,"geo":null,"note":null,"container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","pubkey":null,"role":null,"room":null,"salutation":null,"title":null,"tz":null,"url":null,"url_home":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"org_name":"","org_unit":null,"tel_assistent_normalized":null,"tel_assistent":null,"tel_car_normalized":null,"tel_car":null,"tel_cell_normalized":null,"tel_cell":null,"tel_cell_private_normalized":null,"tel_cell_private":null,"tel_fax_normalized":null,"tel_fax":null,"tel_fax_home_normalized":null,"tel_fax_home":null,"tel_home_normalized":null,"tel_home":null,"tel_other_normalized":null,"tel_other":null,"tel_pager_normalized":null,"tel_pager":null,"tel_prefer_normalized":null,"tel_prefer":null,"tel_work_normalized":null,"tel_work":null,"industry":null,"created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","creation_time":"2018-03-22 18:41:45","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","type":"user","syncBackendIds":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14"},"user_type":"user","role":"REQ","quantity":"1","status":"ACCEPTED","status_authkey":"dfdd18558303126eac3ecfa8c759d9c7e4c86ca5","displaycontainer_id":{"id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","name":"Tine 2.0 Admin Account's personal calendar","type":"personal","owner_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","color":"#FF6600","order":"0","backend":"Sql","application_id":"08eaeea17f4d7412b7412967c9f164b773337f73","content_seq":"4","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 18:53:53","last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:53:53","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Calendar_Model_Event","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":true,"editGrant":true,"deleteGrant":true,"privateGrant":true,"exportGrant":true,"syncGrant":true,"adminGrant":true,"freebusyGrant":true,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/personal/2eb228ee28ba578744c82bb79ac8be1b0e0a9a14/58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7"},"transp":"OPAQUE","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 19:03:19","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","fbInfo":"<div class=\"cal-fbinfo-state cal-fbinfo-state-free\" tine:calendar-event-id=\"853c34ffcc82b194f54003ea0008835e5a0c3938\"  tine:calendar-freebusy-state-id=\"0\"  ></div>"}],"alarms":[],"tags":[],"dtstart":"2018-03-22 13:00:00","recurid":null,"base_event_id":null,"exdate":null,"rrule":null,"poll_id":null,"mute":"","is_all_day_event":false,"rrule_until":"","rrule_constraints":null,"originator_tz":"Europe/Berlin","readGrant":true,"editGrant":true,"deleteGrant":true,"exportGrant":true,"freebusyGrant":true,"privateGrant":true,"syncGrant":true,"customfields":{"testCF":"es ist kalt, mir ist so kalt"},"relations":[{"own_backend":"Sql","related_backend":"Sql","own_id":"853c34ffcc82b194f54003ea0008835e5a0c3938","own_model":"Calendar_Model_Event","related_record":{"creation_time":"2018-03-22 18:41:45","created_by":{"accountId":"cf02de10026fa844c08960bf4448a125d5ac6c28","accountLoginName":"setupuser","accountLastLogin":null,"accountLastLoginfrom":null,"accountLastPasswordChange":null,"accountStatus":"disabled","accountExpires":null,"accountPrimaryGroup":"766b0949dac7ec197d9223725c044644d5e8e2e3","accountHomeDirectory":null,"accountLoginShell":null,"accountDisplayName":"setupuser","accountFullName":"setupuser","accountFirstName":null,"accountLastName":"setupuser","accountEmailAddress":null,"lastLoginFailure":null,"loginFailures":"0","contact_id":"0f2f78932d1c7f63d2c51d9f8718055f93cd6abc","openid":null,"visibility":"hidden","created_by":null,"creation_time":null,"last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_time":null,"deleted_by":null,"seq":"0","xprops":null,"container_id":{"id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"bf87d1d7e198c0d8e429b472e63d2ca3443ed145","content_seq":"1","created_by":null,"creation_time":null,"last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:41:46","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/shared/abcebae2eeb55fb460dc6c4c459da094336f80f8"}},"last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":{"id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"bf87d1d7e198c0d8e429b472e63d2ca3443ed145","content_seq":"1","created_by":null,"creation_time":null,"last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:41:46","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/shared/abcebae2eeb55fb460dc6c4c459da094336f80f8"},"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","tid":"","private":"","cat_id":"","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","bday":"","org_name":"","org_unit":null,"salutation":null,"title":null,"role":null,"assistent":null,"room":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_locality":null,"adr_one_region":null,"adr_one_postalcode":null,"adr_one_countryname":null,"adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":null,"adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":null,"tel_cell":null,"tel_fax":null,"tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":null,"tel_fax_home":null,"tel_cell_private":null,"tel_other":null,"tel_prefer":null,"email":"vagrant@example.org","email_home":null,"url":null,"url_home":null,"freebusy_uri":null,"calendar_uri":null,"note":null,"tz":null,"pubkey":null,"jpegphoto":"images/empty_photo_blank.png","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","tags":[],"notes":"","customfields":"","attachments":"","paths":[],"type":"user","memberroles":"","industry":null,"groups":""},"related_id":"e265d7b1f7e19151dcb79860a9b40de94681be17","related_model":"Addressbook_Model_Contact","type":"","related_degree":"sibling"}],"attachments":[],"notes":[{"note_type_id":1,"note":"asdf note"}]},"format":"","definitionId":"ee9521b09937187eda6cb6f1667ed7ece19f26a7"}

        $jsonFEData = json_decode('{"recordData":{"creation_time":"2018-03-22 19:03:19","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","id":"853c34ffcc82b194f54003ea0008835e5a0c3938","dtend":"2018-03-22 14:00:00","transp":"OPAQUE","class":"PUBLIC","description":"","geo":null,"location":"","organizer":{"creation_time":"2018-03-22 18:41:45","created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","id":"e265d7b1f7e19151dcb79860a9b40de94681be17","tid":"","private":"","cat_id":"","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","bday":"","org_name":"","org_unit":null,"salutation":null,"title":null,"role":null,"assistent":null,"room":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_locality":null,"adr_one_region":null,"adr_one_postalcode":null,"adr_one_countryname":null,"adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":null,"adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":null,"tel_cell":null,"tel_fax":null,"tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":null,"tel_fax_home":null,"tel_cell_private":null,"tel_other":null,"tel_prefer":null,"email":"vagrant@example.org","email_home":null,"url":null,"url_home":null,"freebusy_uri":null,"calendar_uri":null,"note":null,"tz":null,"pubkey":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","tags":"","notes":"","relations":"","customfields":"","attachments":"","paths":"","type":"user","memberroles":"","industry":null,"groups":""},"priority":null,"status":"CONFIRMED","summary":"test2","url":null,"uid":"9f398de7b3a754a6a5d348f34b5f85e1a6876931","attendee":[{"id":"be8b904a579367f3bad89a40b47fdb4beaa17e09","cal_event_id":"853c34ffcc82b194f54003ea0008835e5a0c3938","user_id":{"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","preferred_address":"0","adr_one_countryname":null,"adr_one_locality":null,"adr_one_postalcode":null,"adr_one_region":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_lon":null,"adr_one_lat":null,"adr_two_countryname":null,"adr_two_locality":null,"adr_two_postalcode":null,"adr_two_region":null,"adr_two_street":null,"adr_two_street2":null,"adr_two_lon":null,"adr_two_lat":null,"assistent":null,"bday":null,"calendar_uri":null,"email":"vagrant@example.org","email_home":null,"freebusy_uri":null,"geo":null,"note":null,"container_id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","pubkey":null,"role":null,"room":null,"salutation":null,"title":null,"tz":null,"url":null,"url_home":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"org_name":"","org_unit":null,"tel_assistent_normalized":null,"tel_assistent":null,"tel_car_normalized":null,"tel_car":null,"tel_cell_normalized":null,"tel_cell":null,"tel_cell_private_normalized":null,"tel_cell_private":null,"tel_fax_normalized":null,"tel_fax":null,"tel_fax_home_normalized":null,"tel_fax_home":null,"tel_home_normalized":null,"tel_home":null,"tel_other_normalized":null,"tel_other":null,"tel_pager_normalized":null,"tel_pager":null,"tel_prefer_normalized":null,"tel_prefer":null,"tel_work_normalized":null,"tel_work":null,"industry":null,"created_by":"cf02de10026fa844c08960bf4448a125d5ac6c28","creation_time":"2018-03-22 18:41:45","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","type":"user","syncBackendIds":null,"jpegphoto":"0","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14"},"user_type":"user","role":"REQ","quantity":"1","status":"ACCEPTED","status_authkey":"dfdd18558303126eac3ecfa8c759d9c7e4c86ca5","displaycontainer_id":{"id":"58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7","name":"Tine 2.0 Admin Account\'s personal calendar","type":"personal","owner_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","color":"#FF6600","order":"0","backend":"Sql","application_id":"08eaeea17f4d7412b7412967c9f164b773337f73","content_seq":"4","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 18:53:53","last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:53:53","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Calendar_Model_Event","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":true,"editGrant":true,"deleteGrant":true,"privateGrant":true,"exportGrant":true,"syncGrant":true,"adminGrant":true,"freebusyGrant":true,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/personal/2eb228ee28ba578744c82bb79ac8be1b0e0a9a14/58a2bd56e7849c0dcf69a2d4a999aa0c9e6855f7"},"transp":"OPAQUE","created_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","creation_time":"2018-03-22 19:03:19","last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"1","fbInfo":"<div class=\"cal-fbinfo-state cal-fbinfo-state-free\" tine:calendar-event-id=\"853c34ffcc82b194f54003ea0008835e5a0c3938\"  tine:calendar-freebusy-state-id=\"0\"  ></div>"}],"alarms":[],"tags":[],"dtstart":"2018-03-22 13:00:00","recurid":null,"base_event_id":null,"exdate":null,"rrule":null,"poll_id":null,"mute":"","is_all_day_event":false,"rrule_until":"","rrule_constraints":null,"originator_tz":"Europe/Berlin","readGrant":true,"editGrant":true,"deleteGrant":true,"exportGrant":true,"freebusyGrant":true,"privateGrant":true,"syncGrant":true,"customfields":{"testCF":"es ist kalt, mir ist so kalt"},"relations":[{"own_backend":"Sql","related_backend":"Sql","own_id":"853c34ffcc82b194f54003ea0008835e5a0c3938","own_model":"Calendar_Model_Event","related_record":{"creation_time":"2018-03-22 18:41:45","created_by":{"accountId":"cf02de10026fa844c08960bf4448a125d5ac6c28","accountLoginName":"setupuser","accountLastLogin":null,"accountLastLoginfrom":null,"accountLastPasswordChange":null,"accountStatus":"disabled","accountExpires":null,"accountPrimaryGroup":"766b0949dac7ec197d9223725c044644d5e8e2e3","accountHomeDirectory":null,"accountLoginShell":null,"accountDisplayName":"setupuser","accountFullName":"setupuser","accountFirstName":null,"accountLastName":"setupuser","accountEmailAddress":null,"lastLoginFailure":null,"loginFailures":"0","contact_id":"0f2f78932d1c7f63d2c51d9f8718055f93cd6abc","openid":null,"visibility":"hidden","created_by":null,"creation_time":null,"last_modified_by":null,"last_modified_time":null,"is_deleted":"0","deleted_time":null,"deleted_by":null,"seq":"0","xprops":null,"container_id":{"id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"bf87d1d7e198c0d8e429b472e63d2ca3443ed145","content_seq":"1","created_by":null,"creation_time":null,"last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:41:46","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/shared/abcebae2eeb55fb460dc6c4c459da094336f80f8"}},"last_modified_time":"","last_modified_by":null,"is_deleted":"0","deleted_time":"","deleted_by":null,"seq":"1","container_id":{"id":"abcebae2eeb55fb460dc6c4c459da094336f80f8","name":"Internal Contacts","type":"shared","owner_id":null,"color":null,"order":"0","backend":"sql","application_id":"bf87d1d7e198c0d8e429b472e63d2ca3443ed145","content_seq":"1","created_by":null,"creation_time":null,"last_modified_by":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","last_modified_time":"2018-03-22 18:41:46","is_deleted":"0","deleted_by":null,"deleted_time":null,"seq":"2","model":"Addressbook_Model_Contact","uuid":null,"xprops":null,"account_grants":{"readGrant":true,"addGrant":false,"editGrant":true,"deleteGrant":false,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":true,"freebusyGrant":false,"downloadGrant":false,"publishGrant":false,"account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","account_type":"user"},"path":"/shared/abcebae2eeb55fb460dc6c4c459da094336f80f8"},"id":"e265d7b1f7e19151dcb79860a9b40de94681be17","tid":"","private":"","cat_id":"","n_family":"Admin Account","n_given":"Tine 2.0","n_middle":null,"n_prefix":null,"n_suffix":null,"n_fn":"Tine 2.0 Admin Account","n_fileas":"Admin Account, Tine 2.0","bday":"","org_name":"","org_unit":null,"salutation":null,"title":null,"role":null,"assistent":null,"room":null,"adr_one_street":null,"adr_one_street2":null,"adr_one_locality":null,"adr_one_region":null,"adr_one_postalcode":null,"adr_one_countryname":null,"adr_one_lon":null,"adr_one_lat":null,"label":"","adr_two_street":null,"adr_two_street2":null,"adr_two_locality":null,"adr_two_region":null,"adr_two_postalcode":null,"adr_two_countryname":null,"adr_two_lon":null,"adr_two_lat":null,"preferred_address":"0","tel_work":null,"tel_cell":null,"tel_fax":null,"tel_assistent":null,"tel_car":null,"tel_pager":null,"tel_home":null,"tel_fax_home":null,"tel_cell_private":null,"tel_other":null,"tel_prefer":null,"email":"vagrant@example.org","email_home":null,"url":null,"url_home":null,"freebusy_uri":null,"calendar_uri":null,"note":null,"tz":null,"pubkey":null,"jpegphoto":"images/empty_photo_blank.png","account_id":"2eb228ee28ba578744c82bb79ac8be1b0e0a9a14","tags":[],"notes":"","customfields":"","attachments":"","paths":[],"type":"user","memberroles":"","industry":null,"groups":""},"related_id":"e265d7b1f7e19151dcb79860a9b40de94681be17","related_model":"Addressbook_Model_Contact","type":"","related_degree":"sibling"}],"attachments":[],"notes":[{"note_type_id":1,"note":"asdf note"}]},"format":"","definitionId":"ee9521b09937187eda6cb6f1667ed7ece19f26a7"}', true);
        $cfc = Tinebase_CustomFieldTest::getCustomField([
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
            'name'              => 'testCF',
            'definition'        => [
                'label'             => 'unittestCF'
            ]
        ]);
        $cfc = Tinebase_CustomField::getInstance()->addCustomField($cfc);

        $recordData = $jsonFEData['recordData'];
        $event = new Calendar_Model_Event($recordData, true);

        $export = new Calendar_Export_Doc(new Calendar_Model_EventFilter(), null,
            [
                'definitionId'  => Tinebase_ImportExportDefinition::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                        'model' => 'Calendar_Model_Event',
                        'name' => 'cal_default_doc_single'
                    ]))->getFirstRecord()->getId(),
                'recordData'    => $recordData
            ]);

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);

        static::assertContains($event->summary, $plain);
        static::assertContains($event->customfields['testCF'], $plain);
        static::assertContains($cfc->definition->label, $plain);
        $relatedRecord = new Addressbook_Model_Contact($event->relations[0]['related_record'], true);
        static::assertContains($relatedRecord->getTitle(), $plain);
    }

    public function testFileNameTemplate()
    {
        /** @var Tinebase_Model_ImportExportDefinition $definition */
        $definition = Tinebase_ImportExportDefinition::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                'model' => 'Calendar_Model_Event',
                'name' => 'cal_default_doc_single'
            ]))->getFirstRecord();

        $definition->plugin_options = substr($definition->plugin_options, 0, strlen($definition->plugin_options) - 10)
            . '<exportFilename>testName_{{ export.timestamp|raw }}.docx</exportFilename></config>';

        Tinebase_ImportExportDefinition::getInstance()->update($definition);

        $export = new Calendar_Export_Doc(new Calendar_Model_EventFilter(), null,
            [
                'definitionId'  => $definition->getId(),
                'recordData'    => $this->_getEvent(true)->toArray()
            ]);
        $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(
            Tinebase_Record_Abstract::generateUID()));

        $export->generate();
        $filename = $export->getDownloadFilename('a', 'b');
        $prop = new ReflectionProperty(Tinebase_Export_Abstract::class, '_exportTimeStamp');
        $prop->setAccessible(true);

        static::assertSame('testName_' . $prop->getValue($export) . '.docx', $filename);
    }
}