<?php
/** class.db.php
 * 
 * This class acts as a abstraction layer between the code and any SQL database
 * It is designed to allow drop-in replacement for other SQL variant databases
 */
 
class db
{
	var $link = 0;
	var $connected = false;
	var $lastQuery = 0;
	var $mode = 'mysql';
	var $debug = false;
	
	function __construct($dbURL='', $dbUserName='', $dbPassword='', $dbDatabase='')
	{
		$this->connect($dbURL, $dbUserName, $dbPassword, $dbDatabase);
	}
	
	/** db::connect
	 * Connects to a MySQL server, then selects the database.
	 * This function will halt execution if it fails.
	 * @param Server URL
	 * @param SQL User name
	 * @param SQL Password
	 * @param Database to select from
	 */
	function connect($dbURL, $dbUserName, $dbPassword, $dbDatabase)
	{
		// Don't connect if we don't specify a URL
		if(!$dbURL)
			return false;
			
		// Attempt connection to SQl database
		if($this->mode == 'mysql')
			$this->link = mysql_connect($dbURL, $dbUserName, $dbPassword);
		if($this->mode == 'mssql')
			$this->link = mssql_connect($dbURL, $dbUserName, $dbPassword);

		// Check for link failure, Stop execution if connection fails		
		if(!$this->link)
		{
			die("Could not connect to database at " . $dbURL . ".<br>Reason: " . mysql_error());
		}
		
		// Try to select the database
		if($this->mode == 'mysql')
			if(!mysql_select_db($dbDatabase, $this->link))
				die("Failed to select database '" . $dbDatabase . "'<br>Reason: " . mysql_error());

		if($this->mode == 'mssql')
			if(!mssql_select_db($dbDatabase, $this->link))
				die("Failed to select database \'" . $dbDatabase . "\'<br>Reason: " . mysql_error());

		
		$this->connected = true;	
	}
	function select_db($dbDatabase)
	{
		return mysql_select_db($dbDatabase, $this->link);
	}
	function set_link($l)
	{
		$this->link = $l;
		$this->connected=true;
	}

	/** db::insert_id
	 * Returns the id of the last item inserted
	 * @return integer id of the last inserted row
	 * @ref http://us2.php.net/mysql_insert_id
	 */
	function insert_id()
	{
		return mysql_insert_id();	
	}

	/** db::query
	 * Executes an SQL style query on the connected MySQL database
	 * @param SQL Query to execute
	 * @return false on error, SQL resource on success.
	 * @ref http://us2.php.net/manual/en/function.mysql-query.php
	 */
	function query($sql, $args=false)
	{
		// Do not attempt to query the database if we haven't connected to it yet.
		if(!$this->connected)
			return false;
		if(is_array($args))
		{
			// Clean and build query
			foreach($args as $arg)
				$clean_args[] = $this->escape_arg($arg);
			$sql = vsprintf($sql,$clean_args);
		}
		if($this->debug)
			echo $sql."<br>";
		// Execute query
		$this->lastQuery = mysql_query($sql, $this->link);

			
		return $this->lastQuery;
	}
	/** db::query_first
	 * Runs a query with "LIMIT 0,1" attached, and returns the result in an array
	 * @param SQL query (cannot contain a limit)
	 */
	function query_first($sql, $args=false)
	{
		// Do not attempt to query the database if we haven't connected to it yet.
		if(!$this->connected)
			return false;
		
		$this->query($sql, $args);
			
		return $this->fetch_array();
	}
	
	/** db::num_rows
	 * Returns the number of affected rows by an SQL query. if no resource is given,
	 * the last query made by this class instance will be used instead.
	 * @param OPTIONAL MySQL resource ID.
	 * @return false on error, number of rows SELECTed on success
	 * @ref http://us2.php.net/manual/en/function.mysql-num-rows.php
	 */
	function num_rows($resource = false)
	{
		// Do not attempt to query the database if we haven't connected to it yet.
		if(!$this->connected)
			return false;

		// Update last query if one is passed.
		if($resource)
			$this->lastQuery = $resource;
		

		return mysql_num_rows($this->lastQuery);
	}
	function affected_rows($resource=false)
	{
		// Do not attempt to query the database if we haven't connected to it yet.
		if(!$this->connected)
			return false;

		// Update last query if one is passed.
		if($resource)
			$this->lastQuery = $resource;
			
		return mysql_affected_rows();
	}
	
	/** db::fetch_array
	 * Returns an array for each element queried via an SQl SELECT statement.
	 * Array is both associative and indexed.
	 * @param OPTIONAL MySQL resource ID. (Assumes last if none is supplied)
	 */
	function fetch_array($resource = false)
	{
		// Do not attempt to query the database if we haven't connected to it yet.
		if(!$this->connected)
			return false;

		// Update last query if one is passed.
		if($resource != false)
			$this->lastQuery = $resource;
		
		
		return mysql_fetch_array($this->lastQuery, MYSQL_ASSOC); // Default type is MYSQL_BOTH
	}
	/** db::escape_arg */
	function escape_arg($sql) 
	{
		return "'".mysql_real_escape_string($sql)."'";
	
	}
}
?>
