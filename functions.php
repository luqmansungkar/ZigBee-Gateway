<?php 
function kurl($url, $method, $json){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
		'Content-Length: ' . strlen($json),
		'Authorization: Basic ZGVsaWdodDpkZWxpZ2h0',
		));

	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	if ($method != 'GET') {
		curl_setopt($curl, CURLOPT_POSTFIELDS,$json);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($curl);
	if($result === false)
	{
	    echo 'Curl error: ' . curl_error($curl).'<br>';
	}
	curl_close($curl);
	return $result;
}

function getThingsToken(){
	include 'settings.php';
	$requestToken = file_get_contents($baseUrl."gentoken/".$productToken);
	$requestToken = str_replace('"', "", $requestToken);
	//echo "requestToken: ".$requestToken;
	return $requestToken;
}

function dbInsert($table_name, $form_data){
	//credit : http://www.evoluted.net/thinktank/web-development/time-saving-database-functions

	// retrieve the keys of the array (column titles)
    $fields = array_keys($form_data);

    // build the query
    $sql = "INSERT INTO ".$table_name."
    (`".implode('`,`', $fields)."`)
    VALUES('".implode("','", $form_data)."')";
    //echo "sql: ".$sql;

    return dbQuery($sql);
}

function dbSelect($table_name, $select_query, $where_query){
	
	$sql = "SELECT ".$select_query." FROM ".$table_name;
	if ($where_query != '') {
		$sql .= " WHERE ".$where_query;
	}
	//echo $sql;
	return dbQuery($sql);
	
}

function dbQuery($sql){
	include "database.php";

	$result = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $result;
}
?>