<?php declare(strict_types=1);

/**
 * Import courses and members from Divis
 *
 * @package     Courses
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Import courses and members from Divis
 *
 * @package     Courses
 * @subpackage  Import
 */
class Courses_Import_DivisCourses extends Tinebase_Import_Abstract
{
    public const COURSES_EXCLUDE_SYNC_UNTIL = 'courses-exclude-sync-until';
    public const DIVIS_UID_NUMBER = 'divis_uid_number';


    /**
     * possible configs with default values
     *
     * @var array
     */
    protected $_options = array(
        'divisFile'         => null,
        'teacherPwdFile'    => null,
    );

    protected ?Tinebase_Model_Note $note = null;
    protected ?Tinebase_Model_Tree_Node $fileNode = null;
    protected string $defaultDepartment = '';
    protected array $departments = [];
    protected array $resultMsg = [];
    protected array $rawTeachers = [];
    protected array $rawStudents = [];
    protected array $coursesToUid = [];
    protected array $coursesNames = [];
    protected array $coursesGroupId = [];
    protected Tinebase_User_Sql $userCtrl;
    protected Tinebase_Group_Abstract $groupCtrl;
    protected Tinebase_Record_RecordSet $users;
    protected array $usernames = [];
    protected array $uidnumbers = [];
    protected Tinebase_Model_ImportExportDefinition $accountImportDef;
    protected string $accountImportSeparator;
    protected ?Admin_Import_User_Csv $teacherImporter = null;
    protected array $studentImporters = [];

    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        $this->groupCtrl = Tinebase_Group::getInstance();
        /** @var Tinebase_User_Sql $uSqlCtrl */ // phpstan cast :-/
        $uSqlCtrl = Tinebase_User::getInstance();
        $this->userCtrl = $uSqlCtrl;
        $this->users = new Tinebase_Record_RecordSet(Tinebase_Model_FullUser::class, []);

        $definitionName = Courses_Config::getInstance()
            ->get(Courses_Config::STUDENTS_IMPORT_DEFINITION, 'admin_user_import_csv');

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Using import definition: ' . $definitionName);
        $this->accountImportDef = Tinebase_ImportExportDefinition::getInstance()->getByName($definitionName);
        if (is_array($this->accountImportDef->plugin_options)) {
            $options = $this->accountImportDef->plugin_options;
        } elseif (! empty($this->accountImportDef->plugin_options)) {
            $options = Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($this->accountImportDef)->toArray();
        } else {
            $options = ['delimiter' => ','];
        }

        $this->accountImportSeparator = $options['delimiter'] ?? ',';
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Using import csv separator: ' . $this->accountImportSeparator);
    }

    protected function _getStudentImporter(Courses_Model_Course $course): Admin_Import_User_Csv
    {
        if (!isset($this->studentImporters[$course->name])) {
            $this->studentImporters[$course->name] = Admin_Import_User_Csv::createFromDefinition($this->accountImportDef,
                Courses_Controller_Course::getInstance()->_getNewUserConfig($course));
        }
        return $this->studentImporters[$course->name];
    }

    protected function _getTeacherImporter(): Admin_Import_User_Csv
    {
        if (null === $this->teacherImporter) {
            $this->teacherImporter = Admin_Import_User_Csv::createFromDefinition($this->accountImportDef,
                $this->_getTeacherImportConf());
        }
        return $this->teacherImporter;
    }

    protected function _getTeacherImportConf(): array
    {
        $course = new Courses_Model_Course(['name' => 'teacher', 'type' => $this->defaultDepartment, 'group_id' => 'x']);
        $cfg = Courses_Controller_Course::getInstance()->_getNewUserConfig($course);

        $cfg['accountHomeDirectoryPrefix'] = '/storage/lehrer/';

        $cfg['samba']['profilePath'] = Courses_Config::getInstance()->samba->baseprofilepath;
        $cfg['samba']['logon'] = 'lehrer.cmd';

        return $cfg;
    }

    /**
     * import the data
     *
     * @param mixed $_resource (if $_filename is a stream)
     * @param array $_clientRecordData
     * @return array with import data (imported records, failures, duplicates and totalcount)
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $lock = Tinebase_Core::getMultiServerLock(__CLASS__);
        if (false === $lock->tryAcquire()) {
            return [];
        }

        /** @var Tinebase_Backend_Scheduler $backend */
        $backend = Tinebase_Scheduler::getInstance()->getBackend();
        $forUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($backend);
        /** @var Tinebase_Model_SchedulerTask $task */
        $task = $backend->getByProperty('Tinebase_User/Group::syncUsers/Groups');
        unset($forUpdateRaii);
        if ($task->lock_id) {
            return [];
        }
        $task->lock_id = __CLASS__;
        $backend->update($task);

        try {
            $this->_import();
        } catch (Throwable $t) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' import failed with exception ' . get_class($t) . ' and message: ' . $t->getMessage());
            Tinebase_Exception::log($t);
            throw $t; // rethrow is import in order for scheduler to retry the import, for example if a db connection broke during import etc.
        } finally {
            if ($lock->isLocked()) {
                $lock->release();
            }
        }

        $forUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($backend);
        /** @var Tinebase_Model_SchedulerTask $task */
        $task = $backend->getByProperty('Tinebase_User/Group::syncUsers/Groups');
        unset($forUpdateRaii);
        $task->lock_id = null;
        $backend->update($task);

        return [];
    }

    /**
     * inspect file to import and decide if there is something to import or not
     *
     * @return bool
     */
    protected function _inspectImportFile(): bool
    {
        $fm = Filemanager_Controller_Node::getInstance();
        $this->fileNode = $fm->getFileNode(
            Tinebase_Model_Tree_Node_Path::createFromStatPath($fm->addBasePath($this->_options['divisFile'])));

        $this->note = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $this->fileNode->getId())
            ->find(function(Tinebase_Model_Note $note) {
                return preg_match('/^last imported revision: \d+$/m', $note->note);
            }, null);

        if (null === $this->note) {
            $note = new Tinebase_Model_Note([
                'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                'note' => 'last imported revision: 0',
                'record_id' => $this->fileNode->getId(),
                'record_model' => Tinebase_Model_Tree_Node::class,
            ]);
            $this->note = Tinebase_Notes::getInstance()->addNote($note);
        } elseif (preg_match('/^last imported revision: (\d+)$/m', $this->note->note, $m) && (int)$m[1] === (int)$this->fileNode->revision) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' no new file revision found, nothing to import.');
            $this->note = null;
            return false;
        }
        return true;
    }

    protected function _getDepartments(): bool
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_Department::class, [
            ['field' => 'name', 'operator' => 'equals', 'value' => ''],
        ]);
        $this->defaultDepartment = '';
        foreach (Courses_Config::getInstance()->{Courses_Config::COURSE_DEPARTMENT_MAPPING} as $dep => $map) {
            $filter->getFilter('name')->setValue($dep);
            $department = Tinebase_Department::getInstance()->search($filter)->getFirstRecord();
            if (null === $dep) {
                $msg = 'department ' . $dep . ' not found';
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;
                return false;
            }
            if (null === $map) {
                $this->defaultDepartment = $department->getId();
            } else {
                foreach ($map as $course) {
                    $this->departments[$course] = $department->getId();
                }
            }
        }
        if ('' === $this->defaultDepartment) {
            $msg = 'default department not configured';
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' ' . $msg);
            $this->resultMsg[] = $msg;
            return false;
        }
        return true;
    }

    /**
     * read raw data from file and prepare for processing
     */
    protected function _readRawData(): bool
    {
        $fh = fopen($this->fileNode->getFilesystemPath(), 'r');
        $headLine = trim(fgets($fh));
        if ($headLine !== 'Benutzername;Nachname;Vorname;Primäre E-Mail-Adresse;Weitere E-Mail-Adressen;Rolle;Schulzugehörigkeit (Stammschule);Quelle;Klassen;Eindeutige ID;Eineindeutige ID;Kontoablaufdatum;Geplantes Löschdatum;Geburtsdatum') {
            $msg = 'unknown headline, will not import file:' . PHP_EOL . $headLine;
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' ' . $msg);
            $this->resultMsg[] = $msg;
            return false;
        }
        while ($line = fgetcsv($fh, null, ';'/*, '"', '\\'*/)) {
            $uid = (int)$line[9];
            if ($uid < 600000) {
                $msg = 'uid < 600000, skipping line: ' . PHP_EOL . join(';', $line);
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;
                continue;
            }
            switch ($line[5] /*Rolle*/) {
                case 'Lehrer':
                    $this->rawTeachers[$uid] = $line;
                    break;
                case 'Schüler':
                    if (!preg_match('/^[a-z]{2,3}\d\d$/', $line[8])) {
                        $msg = 'schüler klasse bad format: ' . $line[8] . PHP_EOL . join(';', $line);
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' ' . $msg);
                        $this->resultMsg[] = $msg;
                        continue 2;
                    }
                    if (strpos($line[8], '0') === strlen($line[8]) - 1) {
                        $msg = 'schüler klasse ends on 0, ignoring: ' . $line[8] . PHP_EOL . join(';', $line);
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' ' . $msg);
                        $this->resultMsg[] = $msg;
                        continue 2;
                    }
                    $line[2] = str_replace('+', '', $line[2]);
                    $this->rawStudents[$uid] = $line;
                    if (!isset($this->coursesToUid[$line[8]])) {
                        $this->coursesToUid[$line[8]] = [];
                    }
                    $this->coursesToUid[$line[8]][] = $uid;
                    break;
                default:
                    continue 2;
            }
        }
        return true;
    }

    /**
     * get courses, create missing courses
     */
    protected function _processCourses(): void
    {
        $rawCoursesNames = array_keys($this->coursesToUid);
        $courseCtrl = Courses_Controller_Course::getInstance();
        $courses = $courseCtrl->getAll();
        $this->coursesGroupId = [];
        $this->coursesNames = [];
        foreach ($courses as $course) {
            $this->coursesNames[$course->name] = $course;
            $this->coursesGroupId[$course->group_id] = $course;
        }
        if (empty($coursesToCreate = array_diff($rawCoursesNames, $courses->name))) {
            $msg = 'no new courses to create';
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' ' . $msg);
            $this->resultMsg[] = $msg;
        }
        foreach ($coursesToCreate as $toCreate) {
            if (!preg_match('/^([a-z]{2,3})\d\d$/', $toCreate, $m)) {
                $m = [1 => '']; // shouldn't happen, we filtered that in self::_readRawData() already...
            }
            if (!isset($this->departments[$m[1]])) {
                $department = $this->defaultDepartment;
            } else {
                $department = $this->departments[$m[1]];
            }
            $course = $courseCtrl->saveCourseAndGroup(new Courses_Model_Course([
                'name' => $toCreate,
                'type' => $department,
            ], true), new Tinebase_Model_Group([], true));
            $courses->addRecord($course);
            $this->coursesNames[$course->name] = $course;
            $this->coursesGroupId[$course->group_id] = $course;
            $msg = 'created course: ' . $course->name;
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' ' . $msg);
            $this->resultMsg[] = $msg;
        }
    }

    protected function _processTeachers(): void
    {
        $lock = Tinebase_Core::getMultiServerLock(__CLASS__);
        assert($lock->isLocked());

        $createdAccounts = [];
        $createdPwds = [];
        foreach ($this->rawTeachers as $uid => $raw) {
            $lock->keepAlive();

            $accountExpires = null;
            try {
                $accountExpires = new Tinebase_DateTime($raw[11]);
            } catch (Throwable $t) {}

            $username = $this->_getLehrerUserName($raw);
            if (isset($this->uidnumbers[$uid])) {
                $account = $this->uidnumbers[$uid];
                unset($this->uidnumbers[$uid]);
            } elseif (isset($this->usernames[$username])) {
                $account = $this->usernames[$username];
            } else {

                $fh = fopen('php://memory', 'w+');
                fputcsv($fh, [$raw[2], $raw[1]], $this->accountImportSeparator);
                rewind($fh);
                $csv = stream_get_contents($fh);
                fclose($fh);
                $this->_getTeacherImporter()->importData('firstname' . $this->accountImportSeparator . 'lastname' . PHP_EOL . $csv);
                $accounts = $this->_getTeacherImporter()->getCreatedAccounts();
                if (count($accounts) > 1) {
                    $msg = 'created more than one teacher account, should not happen! ' . PHP_EOL . join(';', $raw);
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                    foreach ($accounts as $account) {
                        if ($account instanceof Tinebase_Model_User) {
                            $msg = 'problematic teacher account created: ' . $account->accountLoginName;
                            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                                . ' ' . $msg);
                            $this->resultMsg[] = $msg;
                        }
                    }
                } elseif (0 === count($accounts)) {
                    $msg = 'failed to create teacher account ' . PHP_EOL . join(';', $raw);
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                } else {
                    /** @var Tinebase_Model_FullUser $account */
                    $createdAccounts[] = $account = current($accounts);
                    $createdPwds = array_merge($createdPwds, $this->_getTeacherImporter()->getCreatedPasswords());
                    $account->xprops()[self::DIVIS_UID_NUMBER] = $uid;
                    $account->accountExpires = $accountExpires;
                    $account->password_must_change = true;
                    $this->userCtrl->updateUser($account);
                    
                    foreach (Courses_Config::getInstance()->{Courses_Config::TEACHER_GROUPS} ?: [] as $gid) {
                        try {
                            $gid = Tinebase_Group::getInstance()->getGroupByName($gid)->getId();
                        } catch (Tinebase_Exception_Record_NotDefined $e) {
                            try {
                                $gid = Tinebase_Group::getInstance()->getGroupById($gid)->getId();
                            } catch (Tinebase_Exception_Record_NotDefined $e) {
                                continue;
                            }
                        }
                        Tinebase_Group::getInstance()->addGroupMember($gid, $account->getId());
                    }

                    $msg = 'create teacher account: ' . current($accounts)->accountLoginName;
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                }

                continue;
            }

            /** @var Tinebase_Model_FullUser $account */
            if ($this->_skipAccount($account)) {
                continue;
            }

            // check for / do update
            if ($account->accountLoginName !== $username || $account->accountFirstName !== $raw[2] ||
                $account->accountLastName !== $raw[1]) {
                $msg = 'teacher account data mismatch, NOT updating: ' . PHP_EOL . join(';', $raw);
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;
            }
            if (!isset($account->xprops()[self::DIVIS_UID_NUMBER]) || $uid !== $account->xprops()[self::DIVIS_UID_NUMBER] ||
                (($account->accountExpires && (!$accountExpires || !$accountExpires->equals($account->accountExpires))) ||
                    (!$account->accountExpires && $accountExpires))) {
                $account->xprops()[self::DIVIS_UID_NUMBER] = $uid;
                $account->accountExpires = $accountExpires;
                $this->userCtrl->updateUser($account);

                $msg = 'setting teachers uid: ' . $account->accountLoginName . ' -> ' . $uid;
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;
            }
        }

        if (!empty($createdAccounts)) {
            $lock->keepAlive();
            // feed data into export and store it in configured FM location attachment
            $export = new Courses_Export_PwdPrintableDoc(new Tinebase_Record_RecordSet(Tinebase_Model_FullUser::class,
                $createdAccounts), $createdPwds);
            $export->generate();
            $fm = Filemanager_Controller_Node::getInstance();
            $export->save('tine20://' . $fm->addBasePath($this->_options['teacherPwdFile']));
        }
    }

    protected function _processStudents(): void
    {
        $lock = Tinebase_Core::getMultiServerLock(__CLASS__);
        assert($lock->isLocked());

        //$studentsGroup = Courses_Config::getInstance()->{Courses_Config::STUDENTS_GROUP};
        foreach ($this->rawStudents as $uid => $raw) {
            $lock->keepAlive();

            if (!isset($this->coursesNames[$raw[8]])) {
                // must not happen!
                $msg = 'course ' . $raw[8] . ' not found, student not processed: ' . PHP_EOL . join(';', $raw);
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;
                continue;
            }

            $accountExpires = null;
            try {
                $accountExpires = new Tinebase_DateTime($raw[11]);
            } catch (Throwable $t) {}

            /** @var Courses_Model_Course $course */
            $course = $this->coursesNames[$raw[8]];
            // we need the context another time further down the loop, make sure not to break this
            Tinebase_Model_User::setTwigContext(['course' => $course]);
            $tmpUser = new Tinebase_Model_FullUser([
                'accountFirstName' => $raw[2],
                'accountLastName' => $raw[1],
            ], true);
            $tmpUser->applyTwigTemplates();
            $username = $tmpUser->accountLoginName;

            if (isset($this->uidnumbers[$uid])) {
                $account = $this->uidnumbers[$uid];
                unset($this->uidnumbers[$uid]);
            } elseif (isset($this->usernames[$username])) {
                $account = $this->usernames[$username];
            } else {
                $fh = fopen('php://memory', 'w+');
                fputcsv($fh, [$raw[2], $raw[1]], $this->accountImportSeparator);
                rewind($fh);
                $csv = stream_get_contents($fh);
                fclose($fh);
                ($stdImporter = $this->_getStudentImporter($course))->importData('firstname' . $this->accountImportSeparator . 'lastname' . PHP_EOL . $csv);

                $accounts = $stdImporter->getCreatedAccounts();
                if (count($accounts) > 1) {
                    $msg = 'created more than one student account, should not happen! ' . PHP_EOL . join(';', $raw);
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                    foreach ($accounts as $account) {
                        if ($account instanceof Tinebase_Model_User) {
                            $msg = 'problematic student account created: ' . $account->accountLoginName;
                            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                                . ' ' . $msg);
                            $this->resultMsg[] = $msg;
                        }
                    }
                } elseif (0 === count($accounts)) {
                    $msg = 'failed to create student account ' . PHP_EOL . join(';', $raw);
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                } else {
                    /** @var Tinebase_Model_FullUser $account */
                    $account = current($accounts);
                    $account->xprops()[self::DIVIS_UID_NUMBER] = $uid;
                    $account->accountExpires = $accountExpires;
                    $account->password_must_change = true;
                    $this->userCtrl->updateUser($account);

                    $memberIds = [$account->getId()];
                    Courses_Controller_Course::getInstance()->_addToCourseGroup($memberIds, $course);
                    Courses_Controller_Course::getInstance()->_manageAccessGroups($memberIds, $course);
                    Courses_Controller_Course::getInstance()->_addToStudentGroup($memberIds);

                    $msg = 'create student account: ' . $account->accountLoginName;
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                }

                continue;
            }

            /** @var Tinebase_Model_FullUser $account */
            if ($this->_skipAccount($account)) {
                continue;
            }

            $applyTwig = false;
            $updateAccount = false;

            if (isset($account->xprops()[self::DIVIS_UID_NUMBER])) {
                if ((string)$account->xprops()[self::DIVIS_UID_NUMBER] !== (string)$uid) {
                    $msg = 'uidnumber changed for student: ' . $account->accountLoginName . ' from: ' .
                        $account->xprops()[self::DIVIS_UID_NUMBER] . ' to: ' . $uid;
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' ' . $msg);
                    $this->resultMsg[] = $msg;

                    $account->xprops()[self::DIVIS_UID_NUMBER] = $uid;
                    $updateAccount = true;
                }
            } else {
                $updateAccount = true;
                $account->xprops()[self::DIVIS_UID_NUMBER] = (string)$uid;
            }

            $groupMemberships = $this->groupCtrl->getGroupMemberships($account->getId());
            if (!in_array($course->group_id, $groupMemberships, true)) {
                foreach ($groupMemberships as $gid) {
                    if (isset($this->coursesGroupId[$gid])) {
                        $tmpCourse = $this->coursesGroupId[$gid];
                        $this->groupCtrl->removeGroupMember($tmpCourse->group_id, $account->getId());
                        // touch importer to regenerate pwd export
                        $this->_getStudentImporter($tmpCourse);
                        $msg = 'remove student: ' . $account->accountLoginName . ' from course: ' . $tmpCourse->name;
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' ' . $msg);
                        $this->resultMsg[] = $msg;
                    }
                }
                $memberIds = [$account->getId()];
                Courses_Controller_Course::getInstance()->_addToCourseGroup($memberIds, $course);
                Courses_Controller_Course::getInstance()->_manageAccessGroups($memberIds, $course);
                Courses_Controller_Course::getInstance()->_addToStudentGroup($memberIds);

                $sambaCfg = Courses_Controller_Course::getInstance()->_getNewUserConfig($course)['samba'];
                $sambaSAM = $account->sambaSAM;
                $sambaSAM['homePath'] = stripslashes($sambaCfg['homePath'] ?: '');
                $sambaSAM['homeDrive'] = stripslashes($sambaCfg['homeDrive'] ?: '');
                $sambaSAM['logonScript'] = $sambaCfg['logonScript'] ?: '';
                $sambaSAM['profilePath'] = stripslashes($sambaCfg['profilePath'] ?: '');
                $account->sambaSAM = $sambaSAM;

                $msg = 'add student: ' . $account->accountLoginName . ' to course: ' . $course->name;
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;

                $applyTwig = true;
            }

            if ($account->accountFirstName !== $raw[2] || $account->accountLastName !== $raw[1]) {
                $msg = 'rename student ' . $account->accountFirstName . ' ' . $account->accountLastName . ' to '
                    . $raw[2] . ' ' . $raw[1];
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' ' . $msg);
                $this->resultMsg[] = $msg;

                $account->accountFirstName = $raw[2];
                $account->accountLastName = $raw[1];
                unset($account->accountDisplayName);
                unset($account->accountFullName);
                unset($account->accountLoginName);
                unset($account->accountEmailAddress);
                $applyTwig = true;
            }

            if ($applyTwig) {
                // we did set the course context further up the loop, make sure not to break that
                $account->applyTwigTemplates();
                $updateAccount = true;
            }

            if ((($account->accountExpires && (!$accountExpires || !$accountExpires->equals($account->accountExpires))) ||
                    (!$account->accountExpires && $accountExpires))) {
                $account->accountExpires = $accountExpires;
                $updateAccount = true;
            }

            if ($updateAccount) {
                $count = 1;
                $shortUsername = $account->shortenUsername(2);
                while ($count < 100) {
                    try {
                        $tmp = Tinebase_User::getInstance()->getUserByLoginName($account->accountLoginName);
                        if ($tmp->getId() !== $account->getId()) {
                            $account->accountLoginName = $shortUsername . sprintf('%02d', $count++);
                        } else {
                            break;
                        }
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        break;
                    }
                }
                if ($count > 1) {
                    $account->accountEmailAddress = null;
                    $account->applyTwigTemplates();
                }

                $count = 1;
                $accountFullName = $account->accountFullName;
                while ($count < 100) {
                    try {
                        $tmp = Tinebase_User::getInstance()->getUserByProperty('accountFullName', $account->accountFullName);
                        if ($tmp->getId() !== $account->getId()) {
                            $account->accountFullName = $accountFullName . sprintf('%02d', $count++);
                        } else {
                            break;
                        }
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        break;
                    }
                }

                $this->userCtrl->updateUser($account);
                // touch importer to regenerate pwd export
                $this->_getStudentImporter($course);
            }
        }
        $lock->keepAlive();

        /** @var Tinebase_Model_FullUser $account */
        foreach ($this->uidnumbers as $uid => $account) {
            if ($this->_skipAccount($account)) {
                continue;
            }

            $lock->keepAlive();
            $account->accountExpires = Tinebase_DateTime::now()->subDay(1);
            $account->accountStatus = Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED;
            $this->userCtrl->updateUser($account);

            $msg = 'expiring student ' . $uid . ' ' . $account->accountFirstName . ' ' . $account->accountLastName;
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' ' . $msg);
            $this->resultMsg[] = $msg;
        }

        foreach (array_keys($this->studentImporters) as $courseName) {
            $course = $this->coursesNames[$courseName];
            $createdAccounts = [];
            $createdPwds = [];
            foreach ($this->groupCtrl->getGroupMembers($course->group_id, false) as $uid) {
                $createdAccounts[] = $account = $this->userCtrl->getFullUserById($uid);
                $createdPwds[$account->getId()] = $account->xprops()['autoGenPwd'];
            }

            $lock->keepAlive();
            Courses_Controller_Course::getInstance()->createCoursePwdAttachement($createdAccounts, $createdPwds, $course);
        }
    }

    protected function _import()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting Divis import...');

        $lock = Tinebase_Core::getMultiServerLock(__CLASS__);
        assert($lock->isLocked());

        if (!$this->_inspectImportFile() ||
                !$this->_getDepartments() ||
                !$this->_readRawData()) {
            if ($this->note) {
                $m = [];
                preg_match('/^last imported revision: \d+$/m', $this->note->note, $m);
                if (!isset($m[0])) {
                    $m[0] = 'last imported revision: 0';
                }
                $this->note->note = $m[0] . PHP_EOL . join(PHP_EOL, $this->resultMsg);
                Tinebase_Notes::getInstance()->update($this->note);
            }
            return [];
        }

        $lock->keepAlive();

        $this->_processCourses();

        // get all users
        foreach ($this->userCtrl->getAllUserIdsFromSqlBackend() as $id) {
            $user = $this->userCtrl->getFullUserById($id);
            $this->users->addRecord($user);
            $this->usernames[$user->accountLoginName] = $user;
            if (isset($user->xprops()[self::DIVIS_UID_NUMBER]) && (int)$user->xprops()[self::DIVIS_UID_NUMBER] > 600000) {
                $this->uidnumbers[$user->xprops()[self::DIVIS_UID_NUMBER]] = $user;
            }
        }

        $lock->keepAlive();

        $this->_processTeachers();

        $this->_processStudents();

        $resultMsg = 'import succeeded' . PHP_EOL . join(PHP_EOL, $this->resultMsg);
        $this->note->note = 'last imported revision: ' . $this->fileNode->revision . PHP_EOL .
            (Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getInstanceTimeZone())->toString()) . PHP_EOL .
            mb_substr($resultMsg, 0, 16000);
        Tinebase_Notes::getInstance()->update($this->note);
        if (mb_strlen($resultMsg) > 16000) {
            do {
                $resultMsg = mb_substr($resultMsg, 16000);
                $note = new Tinebase_Model_Note([
                    'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                    'note' => mb_substr($resultMsg, 0, 16000),
                    'record_id' => $this->fileNode->getId(),
                    'record_model' => Tinebase_Model_Tree_Node::class,
                ]);
                Tinebase_Notes::getInstance()->addNote($note);
            } while (mb_strlen($resultMsg) > 16000);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' finished Divis import');

        return [];
    }

    protected function _skipAccount(Tinebase_Model_FullUser $account): bool
    {
        if (isset($account->xprops()[self::COURSES_EXCLUDE_SYNC_UNTIL])) {
            try {
                if ((new Tinebase_DateTime($account->xprops()[self::COURSES_EXCLUDE_SYNC_UNTIL], 'Europe/Berlin'))
                        ->isEarlier(Tinebase_DateTime::now())) {
                    $msg = 'skipping sync of account: ' . $account->accountLoginName . ' until: ' .
                        $account->xprops()[self::COURSES_EXCLUDE_SYNC_UNTIL];
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                        __LINE__ . ' ' . $msg);
                    $this->resultMsg[] = $msg;
                    return true;
                }
            } catch (Throwable $t) {} // bad self::COURSES_EXCLUDE_SYNC_UNTIL
        }
        return false;
    }

    protected function _getLehrerUserName(array $raw): string
    {
        return strtolower(substr($raw[2], 0, 1) . '.' . $raw[1]);
    }

    protected function _getRawData(&$_resource)
    {
        // because of abstract parent
        return null;
    }
}