<?php

class DBase
{
    var $db_id = false;
    var $query_num = 0;
    var $query_list = array();
    var $mysql_error = '';
    var $mysql_error_num = 0;
    var $MySQL_time_taken = 0;
    var $query_id = false;
    var $queries_list = "";


    function connect($db_user, $db_pass, $db_name, $show_error=1)
        {
            $this->db_id = @mysqli_connect("localhost", $db_user, $db_pass, $db_name);

            if( !$this->db_id )
                {
                    if($show_error == 1)
                        $this->display_error(mysqli_connect_error(), '1');
                    else
                        return FALSE;
                }

            define ("COLLATE", "utf8");
            mysqli_query($this->db_id, "SET NAMES '" . COLLATE . "'");

            $this->mysql_version = mysqli_get_server_info($this->db_id);

            return TRUE;
        }

    function query($query, $show_error = TRUE)
        {
            $time_before = $this->get_real_time();

            if( !$this->db_id )
                $this->connect(DBUSER, DBPASS, DBNAME);

            if(!($this->query_id = mysqli_query($this->db_id, $query) ))
                {
                    $this->mysql_error = mysqli_error($this->db_id);
                    $this->mysql_error_num = mysqli_errno($this->db_id);

                    if($show_error)
                        $this->display_error($this->mysql_error, $this->mysql_error_num, $query);
                }

            $this->MySQL_time_taken += $this->get_real_time() - $time_before;

            $this->query_num ++;
            $this->queries_list .= $query."<br />";

            return $this->query_id;
        }

    public function safesql( $source )
    {
        if(!$this->db_id) $this->connect(DBUSER, DBPASS, DBNAME);

        if ($this->db_id) return mysqli_real_escape_string ($this->db_id, $source);
        else return addslashes($source);
    }

    function get_row($query_id = '')
        {
            if ($query_id == '')
                $query_id = $this->query_id;

            return mysqli_fetch_assoc($query_id);
        }

    function get_affected_rows()
        {
            return mysqli_affected_rows($this->db_id);
        }

    function fetch2array($query_id = '')
        {
            if ($query_id == '')
                $query_id = $this->query_id;

            return mysqli_fetch_array($query_id);
        }

    public function fetch2arrayall($sql)
    {
        $query = $this->query($sql);

        while ($return_array[] = mysqli_fetch_assoc($query));


        return $return_array;
    }

    function num_rows($query_id = '')
        {
            if ($query_id == '')
                $query_id = $this->query_id;

            return mysqli_num_rows($query_id);
        }

    function insert_id()
        {
            return mysqli_insert_id( $this->db_id );
        }

    function free( $query_id = '' )
        {
            if ($query_id == '')
                $query_id = $this->query_id;

            @mysqli_free_result($query_id);
        }

    function close()
        {
            @mysqli_close($this->db_id);
        }

    function get_real_time()
        {
            list($seconds, $microSeconds) = explode(' ', microtime());
            return ((float)$seconds + (float)$microSeconds);
        }

    public function get_queries_num()
        {
            return $this->query_num;
        }

    public function get_queries_list()
        {
            return $this->queries_list;
        }

    public function get_mysql_time()
        {
          return $this->MySQL_time_taken;
        }

    public function insert($table, $input)
        {
            $query = "INSERT INTO ".$table."(";

            $array_count = count($input);
            $counter = 0;
            foreach ($input as $key => $value)
            {
                $query .= "`".$key."`";
                $counter++;
                if ($counter < $array_count)
                    $query .= " , ";
            }

            $query .= ") VALUES (";

            $counter = 0;

            foreach ($input as $key => $value)
            {
                if ($value == "NULL")
                    $query .= "NULL";
                else
                    $query .= "'".$this->safesql($value)."'";

                $counter++;
                if ($counter < $array_count)
                    $query .= " , ";

            }

            $query .= ");";

            $this->query($query);

            return $this->insert_id();
        }

    public function update($table, $input, $field_name, $id)
        {
            $query = "UPDATE ".$table." SET ";

            $array_count = count($input);
            $counter = 0;
            foreach ($input as $key => $value)
            {
                $query .= "`".$key."` = '".$value."'";
                $counter++;
                if ($counter < $array_count)
                    $query .= " , ";
            }

            $query .= " WHERE ".$field_name." = '".$id."'";

            $this->query($query);

            return true;
        }

    function display_error($error, $error_num, $query = '')
    {
        if($query) {
            // Safify query
            $query = preg_replace("/([0-9a-f]){32}/", "********************************", $query); // Hides all hashes
            $query_str = "$query";
        }

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<title>MySQL Fatal Error</title>
		<style type="text/css">
		<!--
		body {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-style: normal;
			color: #000000;
		}
		-->
		</style>
		</head>
		<body>
			<font size="4">MySQL Error!</font> 
			<br />------------------------<br />
			<br />
			
			<u>The Error returned was:</u> 
			<br />
				<strong>'.$error.'</strong>

			<br /><br />
			</strong><u>Error Number:</u> 
			<br />
				<strong>'.$error_num.'</strong>
			<br />
				<br />
			
			<textarea name="" rows="10" cols="52" wrap="virtual">'.$query_str.'</textarea><br />

		</body>
		</html>';

        exit();
    }

}
