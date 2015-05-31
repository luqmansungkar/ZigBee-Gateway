<?php
include 'functions.php';
include 'settings.php';
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	switch ($action) {
		case '1':
			onOff();
			break;
		case '2':
			registerThings();
			break;
		
		default:
			# code...
			break;
	}
}

function onOff(){
	global $gatewayBaseUrl, $apiKey;
	$id = $_POST['id'];
	$value = explode("::", $_POST['value']);
	$attr = explode("::", $_POST['attr']);
	//echo "masuk: ".$id.", ".$value."<br>";
	//$ch2 = curl_init();
	$data_json = '{';
	for ($i=0; $i < count($attr); $i++) { 
		$data_json.= '"'.$attr[$i].'":'.$value[$i];
		if ($i < count($attr)-1) {
			$data_json .= ',';
		}
	}
	$data_json .= '}';

	$result = kurl($gatewayBaseUrl.$apiKey."/lights/".$id."/state","PUT",$data_json);
	$hasilDecode = json_decode(substr($result, 1,-1));
	if (array_key_exists('success', $hasilDecode)) {
		$message = json_decode($hasilDecode->success);
		$state = "";
		foreach ($message as $key => $value) {
			$state = $value;
		}
		echo "sukses:".$value;
		//echo $result;
	}else{
		echo $data_json;
	}
	/*header("Location: index.php");
	die();*/
}
	
function registerThings(){
	global $baseUrl, $userID;
	$requestToken = getThingsToken();
	$nama = $_POST['nama'];
	$local_id = $_POST['id'];
	$thingsID = "";
	$kontenRequest = '{"name":"'.$nama.'","location":"","token":"'.$requestToken.'"}';
	//echo $kontenRequest;
	$thingsToken = kurl($baseUrl."user/".$userID."/thing/register","POST",$kontenRequest);
	//echo $thingsToken;
	$json_obj = json_decode($thingsToken);
	if (strpos($json_obj->message,'failed') !== false) {
		echo "gagal";
		#gagal
	}else{
		$thingsID = $json_obj->id;
		//echo $thingsID;
		dbInsert('things',array(
			'id'=>$thingsID,
			'type'=>'Lampu',
			'nama'=>$nama,
			'local_id'=>$local_id,
			));
	}

	header("Location: index.php");
	die();

}

?>