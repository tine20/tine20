<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Courses_Import_...
 */
class Courses_ImportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        // clean up after other tests :-/
        Courses_Config::getInstance()->internet_group = null;
        Courses_Config::getInstance()->internet_group_filtered = null;
        Courses_Controller_Course::destroyInstance();
    }

    protected function _setupDepartments()
    {
        $arr = [
            'bfs' => ['kas', 'mwp', 'sc'],
            'bs' => ['av', 'dp', 'fo', 'kd', 'meg', 'mf', 'mk', 'mkh'],
            'bv' => ['avm', 'avu', 'va'],
            'os' => ['bos', 'fos'],
            'sonst' => null,
            'test' => ['tst']
        ];
        Courses_Config::getInstance()->{Courses_Config::COURSE_DEPARTMENT_MAPPING} = $arr;
        foreach ($arr as $dep => $conf) {
            Tinebase_Department::getInstance()->create(new Tinebase_Model_Department(['name' => $dep]));
        }
    }

    public function testDivisImport()
    {
        $this->_setupDepartments();

        $fileManager = Filemanager_Controller_Node::getInstance();
        $node = $fileManager->createNodes(['/shared/unittest'], [Tinebase_Model_Tree_FileObject::TYPE_FOLDER])->getFirstRecord();
        file_put_contents('tine20://' . ($path = Tinebase_FileSystem::getInstance()->getPathOfNode($node->getId(), true))
            . '/import.csv',
<<<CSV
Benutzername;Nachname;Vorname;Primäre E-Mail-Adresse;Weitere E-Mail-Adressen;Rolle;Schulzugehörigkeit (Stammschule);Quelle;Klassen;Eindeutige ID;Eineindeutige ID;Kontoablaufdatum;Geplantes Löschdatum;Geburtsdatum
zablsand;Zablowsky;Sandra;;;Lehrer;5928;idi;ohne Zuordnung;698278;e7f8bed2-ad94-103c-92a4-35ab3c0c1620;01.01.2037;;01.12.1972
dreyharm;Dreyer;Harm;;;Lehrer;5928;idi;mwp11;698336;09f6b660-ad95-103c-9595-35ab3c0c1620;01.01.2037;;20.10.1968
scheanna4;Schemschura;Anna;;;Lehrer;5928;idi;va21;752468;11bcd568-c6f5-103c-9855-bf4c10cc00cf;01.01.2037;01.04.2037;01.03.1981
moerjoha;Mörke;Johanna;;;Lehrer;5928;idi;meg22, meg12, meg02;752500;f9e387ea-c6fc-103c-99f9-bf4c10cc00cf;01.01.2037;01.04.2037;20.04.1990
5928biss;Bissinger-Admin;Thomas;;;Schuladministrator;5928;;ohne Zuordnung;699036;15985208-b25e-103c-8e34-bf4c10cc00cf;;;
5928rjab;Rjabenko-Admin;Maxim;;;Schuladministrator;5928;;ohne Zuordnung;699063;1c210e26-b25e-103c-8f28-bf4c10cc00cf;;;
kornmaja;Korndörfer;Maja;;;Schüler;5928;idi;sc11;626900;26b77cf0-abef-103c-8852-35ab3c0c1620;01.08.2023;;27.10.2004
sprejona;Spreemann;Jonas;;;Schüler;5928;idi;av02;626967;534f1ae8-abef-103c-8bb4-35ab3c0c1620;01.08.2023;;18.12.2001
owusstep;Owusu-Sekyere;Stephen;;;Schüler;5928;idi;mwp12;626970;54ff098e-abef-103c-8bd7-35ab3c0c1620;01.08.2023;;05.09.1997
habtmilk;Habtom Kiflay;Milkyas;;;Schüler;5928;idi;avm22;627038;849a3092-abef-103c-8f83-35ab3c0c1620;01.08.2024;;21.06.2004
goervale;Görtzen;Valerie Lynn;;;Schüler;5928;idi;meg22;759755;12377a26-3643-103d-9911-59701686b2d9;01.08.2025;30.10.2025;18.06.2004
magnmadi;Magnus;Madita;;;Schüler;5928;idi;mk31;759857;d117d882-3706-103d-8929-59701686b2d9;01.08.2025;30.10.2025;11.08.1998
asma;Asmaa Khalf Ibrahim Noureldin;+;;;Schüler;5928;idi;bos21;675297;0cb2ed3c-ac5b-103c-9f1d-35ab3c0c1620;01.08.2024;;26.11.2000
kobzdian;Kobzak;Diana;;;Schüler;5928;idi;mk31;760362;ae1243ae-3c88-103d-9ac7-59701686b2d9;01.02.2025;02.05.2025;02.08.2003
wintpia;Winterhalter;Pia;;;Schüler;5928;idi;mk31;760608;4acb17b4-4073-103d-90bd-59701686b2d9;01.02.2026;02.05.2026;14.12.1995
labaibra;Lababidi;Ibrahim;;;Schüler;5928;idi;avm21;760741;c4d79dec-413c-103d-89ce-59701686b2d9;01.08.2023;30.10.2023;01.01.2006
brauhann2;Braun;Hannah;;;Schüler;5928;idi;av22;760919;917f8774-42d2-103d-9271-59701686b2d9;01.08.2025;30.10.2025;31.01.2000
CSV
);
        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');

        $oldValue = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN};
        $raii = new Tinebase_RAII(function() use($oldValue) {
            $oldValue ? Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN} = $oldValue :
                Tinebase_Config::getInstance()->delete(Tinebase_Config::ACCOUNT_TWIG_LOGIN);
            Setup_Controller::getInstance()->clearCacheDir();
        });
        Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG_LOGIN} = '{{ account.accountFirstName|transliterate|removeSpace|trim[0:1]|lower }}{{ account.accountLastName|transliterate|removeSpace|lower }}';
        Setup_Controller::getInstance()->clearCacheDir();

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(2, $notes->count());
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'created course: sc11' . PHP_EOL .
            'created course: av02' . PHP_EOL .
            'created course: mwp12' . PHP_EOL .
            'created course: avm22' . PHP_EOL .
            'created course: meg22' . PHP_EOL .
            'created course: mk31' . PHP_EOL .
            'created course: bos21' . PHP_EOL .
            'created course: avm21' . PHP_EOL .
            'created course: av22' . PHP_EOL .
            'create teacher account: szablowsky' . PHP_EOL .
            'create teacher account: hdreyer' . PHP_EOL .
            'create teacher account: aschemschura' . PHP_EOL .
            'create teacher account: jmoerke' . PHP_EOL .
            'create student account: mkorndoerfer' . PHP_EOL .
            'create student account: jspreemann' . PHP_EOL .
            'create student account: sowusu-sekyere' . PHP_EOL .
            'create student account: mhabtomkiflay' . PHP_EOL .
            'create student account: vgoertzen' . PHP_EOL .
            'create student account: mmagnus' . PHP_EOL .
            'create student account: asmaakhalfibrahimnoureldin' . PHP_EOL .
            'create student account: dkobzak' . PHP_EOL .
            'create student account: pwinterhalter' . PHP_EOL .
            'create student account: ilababidi' . PHP_EOL .
            'create student account: hbraun'
            , $note->note);

        $teacherPwdNod = Tinebase_FileSystem::getInstance()->stat($path . '/teacherPwdExport.docx');
        $teacherPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($teacherPwdNod->hash));
        $this->assertStringContainsString('szablowsky', $teacherPwdExport);
        $sz = Tinebase_User::getInstance()->getUserByLoginName('szablowsky', Tinebase_Model_FullUser::class);
        $this->assertStringContainsString($sz->xprops()['autoGenPwd'], $teacherPwdExport);
        $this->assertStringContainsString('hdreyer', $teacherPwdExport);
        $this->assertStringContainsString('aschemschura', $teacherPwdExport);
        $this->assertStringContainsString('jmoerke', $teacherPwdExport);

        $av22 = Courses_Controller_Course::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Courses_Model_Course::class, [
                ['field' => 'name', 'operator' => 'equals', 'value' => 'av22'],
            ]))->getFirstRecord();
        $this->assertNotNull($av22);
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($av22);
        $this->assertSame(1, $attachments->count());
        $studentPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($attachments->getFirstRecord()->hash));
        $this->assertSame(1, preg_match('/HannahBraunBenutzer: hbraunPasswort: (.*)E-Mail:hbraun/', $studentPwdExport, $m));
        $hbraunPwd = $m[1];

        $student = Tinebase_User::getInstance()->getUserByLoginName('hbraun');
        file_put_contents('tine20://' . $path . '/import.csv',
            <<<CSV
Benutzername;Nachname;Vorname;Primäre E-Mail-Adresse;Weitere E-Mail-Adressen;Rolle;Schulzugehörigkeit (Stammschule);Quelle;Klassen;Eindeutige ID;Eineindeutige ID;Kontoablaufdatum;Geplantes Löschdatum;Geburtsdatum
brauhann2;Brun;Hannah;;;Schüler;5928;idi;av22;760919;917f8774-42d2-103d-9271-59701686b2d9;01.08.2025;30.10.2025;31.01.2000
CSV
        );

        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');
        $this->assertSame((int)$updatedNode->revision + 1, (int)$node->revision);

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(3, $notes->count());
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'old: last imported revision: ' . ($node->revision - 1)) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        /** @var Tinebase_Model_Note $note */
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));

        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'no new courses to create' . PHP_EOL .
            'rename student Hannah Braun to Hannah Brun' . PHP_EOL .
            'expiring student ', $note->note);
        $updatedStudent = Tinebase_User::getInstance()->getUserByLoginName('hbrun');
        $this->assertSame($student->getId(), $updatedStudent->getId());

        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($av22);
        $this->assertSame(2, $attachments->count());
        $attachment = $attachments->filter(function(Tinebase_Model_Tree_Node $node) {
            return strpos($node->name, '(') !== false;
        })->getFirstRecord();
        $this->assertNotNull($attachment);
        $studentPwdExport = $this->getPlainTextFromDocx(Tinebase_FileSystem::getInstance()->getRealPathForHash($attachment->hash));
        $this->assertSame(1, preg_match('/HannahBrunBenutzer: hbrunPasswort: (.*)E-Mail:hbrun/', $studentPwdExport, $m));
        $this->assertSame($hbraunPwd, $m[1]);


        file_put_contents('tine20://' . $path . '/import.csv',
            <<<CSV
Benutzername;Nachname;Vorname;Primäre E-Mail-Adresse;Weitere E-Mail-Adressen;Rolle;Schulzugehörigkeit (Stammschule);Quelle;Klassen;Eindeutige ID;Eineindeutige ID;Kontoablaufdatum;Geplantes Löschdatum;Geburtsdatum
brauhann2;Brun;Hannah;;;Schüler;5928;idi;avm22;760919;917f8774-42d2-103d-9271-59701686b2d9;01.08.2025;30.10.2025;31.01.2000
CSV
        );

        $node = Tinebase_FileSystem::getInstance()->stat($path . '/import.csv');
        $this->assertSame((int)$updatedNode->revision + 1, (int)$node->revision);

        $importer = new Courses_Import_DivisCourses([
            'divisFile' => '/shared/unittest/import.csv',
            'teacherPwdFile' => '/shared/unittest/teacherPwdExport.docx',
        ]);
        $importer->import();

        $updatedNode = Tinebase_FileSystem::getInstance()->get($node->getId());
        $this->assertSame($node->revision, $updatedNode->revision);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $node->getId());
        $this->assertSame(4, $notes->count());
        /** @var Tinebase_Model_Note $note */
        $note = $notes->find(fn(Tinebase_Model_Note $note) => strpos($note->note, 'last imported revision: ' . $node->revision) === 0, null);
        $this->assertNotNull($note, print_r($notes->toArray(), true));
        $this->assertStringContainsString(
            'import succeeded' . PHP_EOL .
            'no new courses to create' . PHP_EOL .
            'remove student: hbrun from course: av22' . PHP_EOL .
            'add student: hbrun to course: avm22', $note->note);

        unset($raii);
    }
}