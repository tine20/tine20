<?php
/**
 * I am considering using sqlite as a sort of intermediary storage device for 
 * the library. That way queries and stuff would be much faster and easier. I am
 * on the fence about having that requirement though. I figured I would at least
 * play around with it though.
 * 
 * I ran this test on my ubuntu server and it says "Fatal error: Class 'SQLiteDatabase'
 * not found in /home/luke/htdocs/qcal/tests/UnitTestCase/Database.php on line 23"
 */
class UnitTestCase_Database extends UnitTestCase {

	protected $testpath;
	protected $db;
	/**
	 * Create some test files to play with
	 */
    public function setUp() {
    
		/*
        $this->testpath = TESTFILE_PATH . '/db';
		if (!file_exists($this->testpath)) mkdir($this->testpath, 0777);
		$db = $this->testpath . DIRECTORY_SEPARATOR . 'sqlitedb';
		*/
		// $this->db = new SQLiteDatabase($this->testpath . DIRECTORY_SEPARATOR . 'sqlitedb');
		$this->db = new SQLiteDatabase(":memory:"); // in-memory database might be the perfect solution :)
		$createtable = <<<QUERY
CREATE TABLE foo (
	id INTEGER PRIMARY KEY, -- This seems to cause auto-increment, but the docs say to put AUTOINCREMENT for it to do that... ?
	bar VARCHAR(255),
	baz INTEGER
)
QUERY;
		$this->db->queryExec($createtable);
    
    }
    /**
     * Delete test files
	 */
    public function tearDown() {
    	
		$this->db->queryExec("DROP TABLE foo");
		/*
		$dir = dir($this->testpath);
		while (false !== ($entry = $dir->read())) {
			if ($entry != "." && $entry != "..") unlink($this->testpath . DIRECTORY_SEPARATOR . $entry);
		}
        rmdir($this->testpath);
		*/
    
    }
	public function testInitializeDatabase() {
	
		$this->db->queryExec("INSERT INTO foo (id, bar, baz) VALUES (null, 'baz', 1)");
		$this->db->queryExec("INSERT INTO foo (id, bar, baz) VALUES (null, 'boo', 25)");
		$this->db->queryExec("INSERT INTO foo (id, bar, baz) VALUES (null, 'billowbop', 50)");
		$result = $this->db->query("SELECT * FROM foo");
		$this->assertEqual(count($result->fetchAll(SQLITE_ASSOC)), 3);
	
	}

}