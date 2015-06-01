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
		$message = $hasilDecode->success;
		$state = "";
		foreach ($message as $key => $value) {
			$state = $value;
		}
		$state = ($state == true ? "true" : "false");
		//print_r($state);
		echo "sukses:".$state;
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
	$bri = $_POST['bri'];
	$hue = $_POST['hue'];
	$sat = $_POST['sat'];
	$thingsID = "";
	/*
		{
			name: String, 
			access: {state: Boolean, func: Function (optional)}, //boleh atau nggak nya
			control: {state:Boolean, func: Function (optional)}, //boleh atau nggak nya
			valueType: String, //"STR"or"INT"or"DBL"or"BOOL"or"ARR"
			description: String,
			min: Mixed (vary according to valueType),
			max: Mixed (vary according to valueType)
		}
	*/
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
		echo "things ID: ".$thingsID."<br>";
		$control = "";
		$access = "";
		
		$urlAtribut = $baseUrl."user/".$userID."/thing/".$thingsID."/property/register";
		//echo $thingsID;
		if (!empty($bri)) {
			$konten = '{
			"name": "bri", 
			"access": '.(in_array('acc', $bri) ? 'true' : 'false').',
			"control": '.(in_array('ctrl', $bri) ? 'true' : 'false').',
			"valueType": "BOOL",
			"description": "Tingkat kecerahan Lampu",
			"min": 0,
			"max": 255
			}';
			$hasil = kurl($urlAtribut, "POST",$konten)."<br>";
			echo $hasil;
			if (strpos($hasil,'added:') !== false) {
			    in_array('acc', $bri) ? $access .= "bri," : '';
				in_array('ctrl', $bri) ? $control .= "bri," : '';
			}
		}

		if (!empty($hue)) {
			$konten = '{
			"name": "hue", 
			"access": '.(in_array('acc', $hue) ? 'true' : 'false').',
			"control": '.(in_array('ctrl', $hue) ? 'true' : 'false').',
			"valueType": "INT",
			"description": "Warna lampu",
			"min": 0,
			"max": 65535
			}';
			$hasil = kurl($urlAtribut, "POST",$konten)."<br>";
			echo $hasil;
			if (strpos($hasil,'added:') !== false) {
			   in_array('acc', $hue) ? $access .= "hue," : '';
			   in_array('ctrl', $hue) ? $control .= "hue," : '';
			}
		}

		if (!empty($hue)) {
			$konten = '{
			"name": "sat", 
			"access": '.(in_array('acc', $sat) ? 'true' : 'false').',
			"control": '.(in_array('ctrl', $sat) ? 'true' : 'false').',
			"valueType": "INT",
			"description": "Tingkat kecerahan warna lampu",
			"min": 0,
			"max": 255
			}';
			$hasil = kurl($urlAtribut, "POST",$konten)."<br>";
			echo $hasil;
			if (strpos($hasil,'added:') !== false) {
			    in_array('acc', $sat) ? $access .= "sat," : '';
			    in_array('ctrl', $sat) ? $control .= "sat," : '';
			}
		}
		$access = rtrim($access, ",");
		$control = rtrim($control, ",");

		dbInsert('things',array(
			'id'=>$thingsID,
			'type'=>'Lampu',
			'nama'=>$nama,
			'local_id'=>$local_id,
			'control'=>$control,
			'access'=>$access,
			));
	}

	header("Location: index.php");
	die();

}

?>