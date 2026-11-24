<?php

require_once("position.php");

class distance {
	private $database;
	private $identifier;
	private $distance;
	private $start;
	private $end;
	
	function __construct($database) {
		$this->database = $database;
	}
	
	function create_table() {
		$table = "CREATE TABLE IF NOT EXISTS `distance` (" .
				"id INT NOT NULL AUTO_INCREMENT PRIMARY KEY," .
				"distance INT NOT NULL," .
				"start INT NOT NULL," .
				"end INT NOT NULL);";
		$statement = $this->database->prepare($table);
		$result = $statement->execute();
		return $result->success();
	}
	
	function set_identifier($identifier) { 
		$this->identifier = $identifier;
	}

	function set_distance($distance) {
		$this->distance = $distance;
	}

	function set_start($start) {
		$this->start = $start;
	}

	function set_end($end) {
		$this->end = $end;
	}

	function get_identifier() {
		return $this->identifier;
	}
	
	function get_distance() {
		return $this->distance;
	}
	
	function get_start() {
		return $this->start;
	}

	function get_end() {
		return $this->end;
	}
	
	function find($distance, $position) {
		$statement = $this->database->prepare("SELECT * FROM `distance` WHERE `distance` <= ? AND (`start` = ? OR `end` = ?);");
		$statement->bind("i", $this->distance);
		$statement->bind("i", $this->position);
		$statement->bind("i", $this->position);
		$result = $statement->execute();
		if($result->success() == true) {
			if($result->rows() > 0) {
				$data = array();
				for($i = 0; $i < $result->rows(); $i++) {
					array_push($data, $result->fetch_array(RASSOC));
				}
				return $data;
			} else {
				throw new Exception("Did not find any positions.");
			}
		} else {
			throw new Exception("Database query failed!");
		}
		return false;
	}

	
	function select() {
		if(!empty($this->identifier)) {
			$statement = $this->database->prepare("SELECT * FROM `distance` WHERE `id` = ?;");
			$statement->bind("i", $this->distance);
		} else if(!empty($this->start) && !empty($this->end)) {
			$statement = $this->database->prepare("SELECT * FROM `distance` WHERE (`start` = ? AND `end` = ?) OR (`start` = ? AND `end` = ?);");
			$statement->bind("i", $this->start);
			$statement->bind("i", $this->end);
			$statement->bind("i", $this->end);
			$statement->bind("i", $this->start);
		} else {
			throw new Exception("No identifying data specified. Give distance identifier or position identifiers. identifier: " . $this->identifier . " start: " . $this->start . " end: " . $this->end); 
		}
		$result = $statement->execute();
		if($result->success() == true) {
			if($result->rows() >= 1) {
				$data = $result->fetch_object();
				if(empty($data)) throw new Exception("Failed to fetch object!");
				$this->identifier = $data->id;
				$this->distance = $data->distance;
				$this->start = $data->start;
				$this->end = $data->end;
				return true;
			} else {
				return false;
			}
		} else {
			throw new Exception("Database query failed!");
		}
		return false;
	}
	
	function insert() {
		if(!empty($this->identifier)) {
			throw new Exception("Identifier already set.");
			return false;
		}
		if($this->distance === null || $this->distance === "") {
			throw new Exception("Distance is not set.");
			return false;
		}
		if(empty($this->start)) {
			throw new Exception("Start position is not set.");
			return false;
		}
		if(empty($this->end)) {
			throw new Exception("End position is not set.");
			return false;
		}
		if($this->select() == false) {
			$statement = $this->database->prepare("INSERT INTO `distance` (`distance`, `start`, `end`) VALUES (?, ?, ?);");
			$statement->bind("i", $this->distance);
			$statement->bind("i", $this->start);
			$statement->bind("i", $this->end);
			$result = $statement->execute();
			if($result->success() == true) {
				return $this->select();
			} else {
				throw new Exception("Database query failed!");
			}
		} else {
			throw new Exception("Distance already exists for these positions");
		}
		return false;
	}
	
	// Returns the next pair of positions that has no calculated distance yet,
	// as array("start" => position row, "end" => position row), or false.
	function get_uncalculated() {
		$statement = $this->database->prepare(
			"SELECT a.id AS start_id, a.latitude AS start_latitude, a.longitude AS start_longitude, " .
			"b.id AS end_id, b.latitude AS end_latitude, b.longitude AS end_longitude " .
			"FROM `position` a INNER JOIN `position` b ON a.id < b.id " .
			"WHERE NOT EXISTS (SELECT 1 FROM `distance` d WHERE " .
			"(d.start = a.id AND d.end = b.id) OR (d.start = b.id AND d.end = a.id)) " .
			"ORDER BY a.id, b.id LIMIT 0, 1;");
		$result = $statement->execute();
		if($result->success() == true && $result->rows() == 1) {
			$row = $result->fetch_array(RASSOC);
			$start = array("id" => $row["start_id"], "identifier" => $row["start_id"],
				"latitude" => $row["start_latitude"], "longitude" => $row["start_longitude"]);
			$end = array("id" => $row["end_id"], "identifier" => $row["end_id"],
				"latitude" => $row["end_latitude"], "longitude" => $row["end_longitude"]);
			return array("start" => $start, "end" => $end);
		}
		return false;
	}
}

?>
