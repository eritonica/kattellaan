<?php
/*
 * Minimal database wrapper used by the kattellaan PHP classes.
 *
 * The original dbwrapper was an external git submodule that was removed
 * (commit 5905cb9) and is no longer available. This is a reimplementation
 * of the interface the code relies on:
 *
 *   $db = new db("mysqli");
 *   $db->connect($host, $user, $pass, $name);   // bool
 *   $st = $db->prepare("SELECT ... WHERE a = ? AND b = ?;");
 *   $st->bind("s", $a); $st->bind("i", $b);      // s = string, i = int, d = double
 *   $st->get();                                  // final SQL string (for error messages)
 *   $res = $st->execute();
 *   $res->success(); $res->rows();
 *   $res->fetch_array(RASSOC); $res->fetch_object();
 *   $db->escape($value);
 *
 * Bound values are escaped and substituted into the query text (emulated
 * prepared statements). The existing code depends on this: e.g. session.php
 * uses a quoted placeholder ("`id` = '?'") which native prepared statements
 * would treat as a literal.
 */

if (!defined('RASSOC')) define('RASSOC', MYSQLI_ASSOC);
if (!defined('RNUM'))   define('RNUM', MYSQLI_NUM);
if (!defined('RBOTH'))  define('RBOTH', MYSQLI_BOTH);

class db {
	private $driver;
	private $link = null;

	public function __construct($driver = "mysqli") {
		if ($driver !== "mysqli") {
			throw new Exception("Unsupported database driver: " . $driver);
		}
		$this->driver = $driver;
	}

	public function connect($host, $user, $password, $database) {
		mysqli_report(MYSQLI_REPORT_OFF);
		$link = @new mysqli($host, $user, $password, $database);
		if ($link->connect_errno) {
			return false;
		}
		$link->set_charset("utf8mb4");
		$this->link = $link;
		return true;
	}

	public function link() {
		return $this->link;
	}

	public function prepare($query) {
		return new db_statement($this, $query);
	}

	public function escape($value) {
		if ($value === null) return "";
		return $this->link->real_escape_string((string)$value);
	}

	public function query($sql) {
		$result = $this->link->query($sql);
		return new db_result($this->link, $result);
	}

	public function insert_id() {
		return $this->link->insert_id;
	}

	public function error() {
		return $this->link->error;
	}

	public function close() {
		if ($this->link) {
			$this->link->close();
			$this->link = null;
		}
	}
}

class db_statement {
	private $db;
	private $query;
	private $values = array();

	public function __construct($db, $query) {
		$this->db = $db;
		$this->query = $query;
	}

	public function bind($type, $value) {
		switch ($type) {
			case 'i':
				$this->values[] = (string)(int)$value;
				break;
			case 'd':
				$this->values[] = (string)(float)$value;
				break;
			case 's':
			default:
				$this->values[] = "'" . $this->db->escape($value) . "'";
				break;
		}
		return $this;
	}

	// Returns the query with bound values substituted.
	public function get() {
		$sql = "";
		$index = 0;
		$length = strlen($this->query);
		for ($i = 0; $i < $length; $i++) {
			$char = $this->query[$i];
			if ($char === '?' && $index < count($this->values)) {
				$sql .= $this->values[$index++];
			} else {
				$sql .= $char;
			}
		}
		return $sql;
	}

	public function execute() {
		return $this->db->query($this->get());
	}
}

class db_result {
	private $result;
	private $success;
	private $rows = 0;
	private $error;

	public function __construct($link, $result) {
		$this->result = $result;
		$this->success = ($result !== false);
		$this->error = $link->error;
		if ($result instanceof mysqli_result) {
			$this->rows = $result->num_rows;
		} else if ($result === true) {
			$this->rows = $link->affected_rows;
		}
	}

	public function success() {
		return $this->success;
	}

	public function rows() {
		return $this->rows;
	}

	public function error() {
		return $this->error;
	}

	public function fetch_array($mode = MYSQLI_BOTH) {
		if (!($this->result instanceof mysqli_result)) return null;
		return $this->result->fetch_array($mode);
	}

	public function fetch_object() {
		if (!($this->result instanceof mysqli_result)) return null;
		return $this->result->fetch_object();
	}
}

?>
