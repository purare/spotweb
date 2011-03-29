<?php
require_once "lib/dbstruct/SpotStruct_abs.php";

class SpotStruct_mysql extends SpotStruct_abs {

	function createDatabase() {
		$q = $this->_dbcon->arrayQuery("SHOW TABLES");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE spots(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128),
										spotid INTEGER,
										category INTEGER, 
										subcat INTEGER,
										poster VARCHAR(128),
										groupname VARCHAR(128),
										subcata VARCHAR(64),
										subcatb VARCHAR(64),
										subcatc VARCHAR(64),
										subcatd VARCHAR(64),
										title VARCHAR(128),
										tag VARCHAR(128),
										stamp INTEGER,
										filesize BIGINT DEFAULT 0,
										moderated BOOLEAN DEFAULT FALSE);");
			$this->_dbcon->rawExec("CREATE TABLE nntp(server varchar(128) PRIMARY KEY,
										   maxarticleid INTEGER UNIQUE,
										   nowrunning INTEGER DEFAULT 0,
										   lastrun INTEGER DEFAULT 0);");

			# create indices
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_title ON spots(title)");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_tag ON spots(tag)");
			$this->_dbcon->rawExec("CREATE FULLTEXT INDEX idx_poster ON spots(poster)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_1 ON spots(id, category, subcata, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_2 ON spots(id, category, subcatd, stamp DESC)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spots_3 ON spots(messageid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(stamp);");
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_5 ON spots(poster);");
		} # if

		# Controleer of de 'spots' tabel wel recent is, de oude versie had geen unieke messageid
		$q = $this->_dbcon->arrayQuery("SHOW INDEX FROM spots WHERE Key_name = 'idx_spots_3' AND Non_unique = 1;");
		if (count($q) == 1) {
			$this->_dbcon->rawExec("ALTER IGNORE TABLE spots DROP INDEX idx_spots_3, ADD UNIQUE idx_spots_3 (messageid);");
		} # if

		# Controleer of de 'spots' tabel wel recent is, de oude versie had geen unieke messageid
		$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM spots");
		if (count($q) == 14) {
			$this->_dbcon->rawExec("ALTER TABLE spots ADD COLUMN(filesize BIGINT DEFAULT 0,
										moderated BOOLEAN DEFAULT FALSE)");
		} # if
		

		# Controleer of de 'commentsxover' tabel wel recent is, de oude versie had 3 kolommen, daarvan droppen wij er 1
		try {
			$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM commentsxover;");
			if (count($q) == 4) {
				$this->_dbcon->rawExec("DROP TABLE commentsxover");
			} # if
		} catch(Exception $x) {
		 ;
		}

		$q = $this->_dbcon->arrayQuery("SHOW TABLES LIKE 'commentsxover'");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE commentsxover(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128),
										   nntpref VARCHAR(128));");
			$this->_dbcon->rawExec("CREATE INDEX idx_commentsxover_1 ON commentsxover(nntpref, messageid)");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_commentsxover_2 ON commentsxover(messageid)");
		} # if
		
		# Controleer of de 'nntp' tabel wel recent is, de oude versie had 2 kolommen (server,maxarticleid)
		$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM nntp;");
		if (count($q) == 2) {
			$this->_dbcon->rawExec("ALTER TABLE nntp ADD COLUMN(nowrunning INTEGER DEFAULT 0);");
		} # if

		# Controleer of er wel een index zit op 'spots' tabel 
		$q = $this->_dbcon->arrayQuery("SHOW INDEXES FROM spots WHERE key_name = 'idx_spots_4'");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE INDEX idx_spots_4 ON spots(stamp);");
		} # if

		$q = $this->_dbcon->arrayQuery("SHOW TABLES LIKE 'downloadlist'");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE downloadlist(id INTEGER PRIMARY KEY AUTO_INCREMENT,
										   messageid VARCHAR(128),
										   stamp INTEGER);");
			$this->_dbcon->rawExec("CREATE INDEX idx_downloadlist_1 ON downloadlist(messageid)");
		} # if

		# Controleer of de 'nntp' tabel wel recent is, de oude versie had 3 kolommen (server,maxarticleid,nowrunning)
		$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM nntp;");
		if (count($q) == 3) {
			$this->_dbcon->rawExec("ALTER TABLE nntp ADD COLUMN(lastrun INTEGER DEFAULT 0);");
		} # if
		
		$q = $this->_dbcon->arrayQuery("SHOW TABLES LIKE 'spotsfull'");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE spotsfull(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
										messageid varchar(128),
										userid varchar(32),
										verified BOOLEAN,
										usersignature TEXT,
										userkey TEXT,
										xmlsignature TEXT,
										fullxml TEXT,
										filesize BIGINT);");										

			# create indices
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_spotsfull_1 ON spotsfull(messageid, userid)");
			$this->_dbcon->rawExec("CREATE INDEX idx_spotsfull_2 ON spotsfull(userid);");
		} # if

		# Verander de grootte van de filesize column in spotsfull 
		$q = $this->_dbcon->arrayQuery("SHOW COLUMNS FROM spotsfull LIKE 'filesize'");
		if (count($q) == 1) {
			if ($q[0]['Type'] == 'int(11)') {
				$this->_dbcon->rawExec("ALTER TABLE spots MODIFY filesize BIGINT DEFAULT 0;");
				$this->_dbcon->rawExec("ALTER TABLE spotsfull MODIFY filesize BIGINT DEFAULT 0;");
			} # if
		} # if
		
		# Controleer of de 'spotsfull' tabel wel recent is, de oude versie had geen unieke messageid
		$q = $this->_dbcon->arrayQuery("SHOW INDEX FROM spotsfull WHERE Key_name = 'idx_spotsfull_1' AND Non_unique = 1;");
		if (count($q) == 2) {
			$this->_dbcon->rawExec("ALTER IGNORE TABLE spotsfull DROP INDEX idx_spotsfull_1, ADD UNIQUE idx_spotsfull_1 (messageid, userid);");
		} # if

		# Controleer of de 'spotsfull' tabel wel recent is, de oude versie had geen index op de userid
		$q = $this->_dbcon->arrayQuery("SHOW INDEX FROM spotsfull WHERE Key_name = 'idx_spotsfull_2';");
		if (count($q) == 2) {
			$this->_dbcon->rawExec("CREATE INDEX idx_spotsfull_2 ON spotsfull(userid);");
		} # if

		$q = $this->_dbcon->arrayQuery("SHOW TABLES LIKE 'watchlist'");
		if (empty($q)) {
			$this->_dbcon->rawExec("CREATE TABLE watchlist(id INTEGER PRIMARY KEY AUTO_INCREMENT, 
												   messageid VARCHAR(128),
												   dateadded INTEGER,
												   comment TEXT);");
			$this->_dbcon->rawExec("CREATE UNIQUE INDEX idx_watchlist_1 ON watchlist(messageid)");
		} # if
	} # Createdatabase
	
} # class