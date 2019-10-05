<?php
	//CODER by Javi Agenjo (@tamat) 2018

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
	if(!isset($_REQUEST["action"]) || $_REQUEST["action"] != "load")
		header('Content-Type: application/javascript');

	$root_path = "./";
	$allow_edit_php = true;
	$use_md5 = true; //set to false if your wide_config.json uses keys without offuscating them in md5
	$md5_salt = ""; //change this string if you have salted the keys with md5(salt + key)

	//load config.json
	$config_path = "/home/wide_config.json"; //CHANGE THIS TO ADD YOUR OWN CONFIG FOLDER
	if( !file_exists($config_path) )
		die('{"status":-1, "msg":"coder wide_config.json not found. edit server.php"}');
	$content = file_get_contents($config_path);
	$config = json_decode($content,true);
   	if( !isset($config["projects"]) )
		die('{"status":-1, "msg":"config.json doesnt have projects"}');
	$projects = $config["projects"];

    //use keys to access project
   	if( !isset($_REQUEST["key"]) )
		die('{"status":-1, "msg":"key missing"}');
    $key = $_REQUEST["key"];
	if($use_md5)  
	    $key = md5( $md5_salt . $key ); //use an md5 so keys are not visible. not salted though...
   	if( !isset( $projects[ $key ]) )
		die('{"status":-1, "msg":"wrong key"}');
    $project = $projects[ $key ];
   	if( !isset( $project["folder"] ) )
		die('{"status":-1, "msg":"folder missing from project config"}');
	$root_path = $project["folder"];

    //process actions
	if(!isset($_REQUEST["action"]))
		die('{"status":-1, "msg":"action missing"}');

	$debug = Array();

	$action = $_REQUEST["action"];

	if( $action == "load" )
	{
		$filename = $_REQUEST["filename"];
		if( !$allow_edit_php && strpos($filename,".php") != FALSE )
		{
			header('HTTP/1.0 403 Forbidden');
			exit;
		}
		$fullpath = $root_path. "/" . $filename;

		if( !file_exists($fullpath) )
		{
			header('HTTP/1.0 404 Not found');
			exit;
		}
		$fp = fopen($fullpath, 'rb');
		header("Content-Type: " . mime_content_type($fullpath) );
		header("Content-Length: " . filesize($fullpath));
		fpassthru($fp);
		exit;
	}
	else if( $action == "save" )
	{
		if(!isset($_REQUEST["filename"]) || !isset($_REQUEST["content"]))
			die('{"status":-1,"msg":"params missing"}');

		$filename = $_REQUEST["filename"];
		$content = $_REQUEST["content"];

		if( !$allow_edit_php && strpos($filename,".php") != FALSE )
			die('{"status":-1, "msg":"cannot save serverside files"}');

		if( strpos( $filename, ".." ) != FALSE )
			die('{"status":-1,"msg":"invalid filename"}');

		$fullpath = $root_path. "/" . $filename;

		if (file_put_contents($fullpath,$content) == FALSE )
			die('{"status":-1,"msg":"cannot save file, not allowed. check privileges."}');

		$result = array();
		$result["status"] = 1;
		$result["msg"] = "file saved";
		$result["filename"] = $filename;
		die( json_encode($result) );
	}
	else if( $action == "project" )
	{
		$result = array();
		$result["status"] = 1;
		$result["msg"] = "project info";
		$result["data"] = $project;
		die( json_encode($result) );
	}
	else if( $action == "mkdir" )
	{
		if(!isset($_REQUEST["folder"]))
			die('{"status":-1,"msg":"params missing"}');
		$folder = $_REQUEST["filename"];

		if( strpos( $folder, ".." ) != FALSE )
			die('{"status":-1,"msg":"invalid folder name"}');

		$fullpath = $root_path. "/" . $folder;

		if (mkdir($fullpath,770) == FALSE )
			die('{"status":-1,"msg":"cannot create folder, not allowed","debug":"'.$fullpath.'"}');

		$result = array();
		$result["status"] = 1;
		$result["msg"] = "folder created";
		die( json_encode($result) );
	}
	else if( $action == "move" )
	{
		if(!isset($_REQUEST["filename"]) || !isset($_REQUEST["new_filename"]))
			die('{"status":-1,"msg":"params missing"}');
		$filename = $_REQUEST["filename"];
		$new_filename = $_REQUEST["new_filename"];

		if( strpos( $filename, ".." ) != FALSE || strpos( $new_filename, ".." ) != FALSE)
			die('{"status":-1,"msg":"invalid filename"}');

		if( !$allow_edit_php && ( strpos($filename,".php") != FALSE || strpos($new_filename,".php")) )
			die('{"status":-1, "msg":"cannot move this extensions"}');

		$fullpath = $root_path. "/" . $filename;
		$new_fullpath = $root_path. "/" . $filename;

		if (rename($fullpath,$new_fullpath) == FALSE )
			die('{"status":-1,"msg":"cannot move file, not allowed","debug":"'.$fullpath.'"}');

		$result = array();
		$result["status"] = 1;
		$result["msg"] = "file moved";
		$result["filename"] = $new_filename;
		die( json_encode($result) );
	}
	else if( $action == "delete" )
	{
		if( !isset($_REQUEST["filename"]) )
			die('{"status":-1,"msg":"params missing"}');
		$filename = $_REQUEST["filename"];

		if( strpos( $filename, ".." ) != FALSE )
			die('{"status":-1,"msg":"invalid filename"}');

		if( !$allow_edit_php && ( strpos($filename,".php") != FALSE || strpos($new_filename,".php")) )
			die('{"status":-1, "msg":"cannot delete serverside files"}');

		$fullpath = $root_path. "/" . $filename;
		if (unlink($fullpath) == FALSE )
			die('{"status":-1,"msg":"cannot delete file, not allowed","debug":"'.$fullpath.'"}');

		$result = array();
		$result["status"] = 1;
		$result["msg"] = "file deleted";
		die( json_encode($result) );
	}
	else if( $action == "autocomplete" )
	{
		if( !isset($_REQUEST["filename"]) )
			die('{"status":-1,"msg":"params missing"}');
        $filename = $_REQUEST["filename"];
		if( strpos( $filename, ".." ) != FALSE )
			die('{"status":-1,"msg":"invalid filename"}');
        $autocompleted = autocomplete( $filename, $root_path );
		$result = array();
		$result["status"] = 1;
		$result["msg"] = "file autocompleted";
        $result["data"] = $autocompleted;
		die( json_encode($result) );            
    }
	else if( $action == "list" )
	{
		if( !isset($_REQUEST["folder"]) )
			die('{"status":-1,"msg":"params missing"}');
		$folder = $_REQUEST["folder"];

		if( strpos( $folder, ".." ) != FALSE )
			die('{"status":-1,"msg":"invalid folder"}');

		$fullpath = $root_path. "/" . $folder . "/";
        if( !is_dir($fullpath) )
			die('{"status":-1,"msg":"folder does not exist"}');

		$files = glob( $fullpath . "*" );
		$files_final = Array();

		foreach ($files as &$filename) {
			$data = Array();
			$data["name"] = basename( $filename );
			$data["is_dir"] = is_dir( $filename );
			$data["mime_type"] = mime_content_type( $filename );
			$data["size"] = filesize( $filename );
			$files_final[] = $data;
		}

		$result = array();
		$result["status"] = 1;
		$result["msg"] = "file list";
		if(isset($project["name"]))
			$result["project"] = $project["name"];
		$result["folder"] = $folder;
		$result["files"] = $files_final;
		die( json_encode($result) );
	}
	else
		die('{"status":-1,"msg","unknown command"}');

    function autocomplete( $filename, $root_path )
    {
		global $debug;
        $tokens = explode("/",$filename);
        $num = count($tokens);
        $folder = implode( "/", array_slice( $tokens, 0, $num - 1 ) );
        $start = $tokens[ $num - 1 ];
        $files = scandir( $root_path . "/" . $folder );
        $valid = Array();
        foreach ($files as $file) {
            if( strpos($file,$start) === 0 )
                $valid[] = $file;
        }
        return $valid;
    }







?>
