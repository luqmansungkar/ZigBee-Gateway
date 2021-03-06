<?php
include 'functions.php';
include 'settings.php';
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	switch ($action) {
		case '1':
			setAttribute();
			break;
		case '2':
			registerThings();
			break;
		case '3':
			login();
			break;	
		case '4':
			createGroup();
			break;	
		default:
			# code...
			break;
	}
}

function createGroup(){
	global $gatewayBaseUrl,$apiKey;
	$nama = $_POST['nama'];
	$lampus = $_POST['group'];
	if (!empty($lampus)) {
		$konten = '{"name":"'.$nama.'"}';
		$hasil = kurl($gatewayBaseUrl.$apiKey."/groups","POST",$konten);
		$hasilDecode = json_decode(substr($hasil, 1,-1));
		if (array_key_exists('success', $hasilDecode)){
			$id = $hasilDecode->success->id;
			$konten = '{"lights":[';
			foreach ($lampus as $lampu) {
				$konten .= '"'.$lampu.'",';
			}
			$konten = rtrim($konten,",");
			$konten.=']}';
			$url = $gatewayBaseUrl.$apiKey."/groups/".$id;
			//echo $url;
			echo kurl($url,"PUT",$konten);
		}
	}
	header("Location: index.php");
	die();
}

function login(){
	global $baseUrl;
	$email = $_POST['email'];
	$url = $baseUrl."user/check/".$email;
	$hasil = kurl($url,"GET","");
	$hasilDecode = json_decode($hasil);
	if (array_key_exists('user', $hasilDecode)) {
		//echo $hasilDecode->user;
		echo dbInsert("setting",array(
			"kunci"=>"user_id",
			"value"=>$hasilDecode->user));
	}else{
		echo "gagal";
	}

}

function setAttribute(){
	global $gatewayBaseUrl, $apiKey;
	$id = $_POST['id'];
	$value = explode("::", $_POST['value']);
	$attr = explode("::", $_POST['attr']);
	$mode = $_POST['mode'];
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
	$url = "";
	if ($mode == 1) {
		$url = $gatewayBaseUrl.$apiKey."/lights/".$id."/state";
	}else{
		$url = $gatewayBaseUrl.$apiKey."/groups/".$id."/action";
	}
	
	//echo $url;
	$result = kurl($url,"PUT",$data_json);
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
		echo $url;
		//echo $data_json;
	}
	/*header("Location: index.php");
	die();*/
}

$thingsID = "";

function registerThings(){
	global $thingsID, $baseUrl;
	$userID = getUserId();
	$requestToken = getThingsToken();
	$nama = $_POST['nama'];
	$tipe = $_POST['tipe'];
	$local_id = $_POST['id'];
	$attr[0] = $_POST['bri'];
	$attr[1] = $_POST['hue'];
	$attr[2] = $_POST['sat'];
	$attr[3] = $_POST['on'];
	//$thingsID = "";
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

		for ($i=0; $i < count($attr); $i++) { 
			if (!empty($attr[$i])) {
				$hasil = "";
				$attrName = "";
				switch ($i) {
					case 0:
						$hasil = attrRegister($attr[$i],"bri","INT","Tingkat kecerahan Lampu",0,255);
						$attrName = "bri";
						break;
					case 1:
						$hasil = attrRegister($attr[$i], "hue","INT","Warna lampu", 0,65535);
						$attrName = "hue";
						break;
					case 2:
						$hasil = attrRegister($attr[$i],"sat","INT","Tingkat kejenuhan warna lampu",0,255);
						$attrName = "sat";
						break;		
					case 3:
						$hasil = attrRegister($attr[$i],"on","BOOL","Status nyala/mati lampu","false","true");
						$attrName = "on";
						break;	
					default:
						# code...
						break;
				}

				if (strpos($hasil,'added:') !== false) {
				    in_array('acc', $attr[$i]) ? $access .= $attrName."," : '';
					in_array('ctrl', $attr[$i]) ? $control .= $attrName."," : '';
				}
			}
		}
		
		$access = rtrim($access, ",");
		$control = rtrim($control, ",");

		dbInsert('things',array(
			'id'=>$thingsID,
			'type'=> ($tipe == 1) ?'Lampu' : 'Group',
			'nama'=>$nama,
			'local_id'=>$local_id,
			'control'=>$control,
			'access'=>$access,
			));
	}

	header("Location: index.php");
	die();

}

function attrRegister($array, $nama, $tipe, $description, $min, $max){
	global $thingsID, $baseUrl;
	$userID = getUserId();
		$urlAtribut = $baseUrl."user/".$userID."/thing/".$thingsID."/property/register";
		$konten = '{
		"name": "'.$nama.'", 
		"access": {"state":'.(in_array('acc', $array) ? 'true' : 'false').'},
		"control": {"state":'.(in_array('ctrl', $array) ? 'true' : 'false').'},
		"valueType": "'.$tipe.'",
		"description": "'.$description.'",
		"min": '.$min.',
		"max": '.$max.'
		}';
		echo $konten."<br>";
		$hasil = kurl($urlAtribut, "POST",$konten)."<br>";
		echo $hasil;
		return $hasil;
}

?>