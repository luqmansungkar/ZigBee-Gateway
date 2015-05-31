<?php
include 'functions.php';
include 'settings.php';
	/*$bash = "sudo /var/www/ta/gateway/tes ".$userID." 1";
	$output = shell_exec($bash);*/

	//$bash = "sudo /var/www/ta/gateway/tes 2>&1";
	/*$bash = "sudo /var/www/ta/gateway/tes ".$userID;
	$output = shell_exec($bash);*/
	/*echo "hasil: ";
	print_r($output);*/
	
	
	/*$cekApiKey = kurl('localhost:8080/api/'.$apiKey,"GET","");
	//echo $cekApiKey;
	$hasilDecode = json_decode(substr($cekApiKey, 1,-1));
	if (array_key_exists('error', $hasilDecode)) {
		//echo "apiKey salah";
	}else{
		//echo "apiKey bener";
	}*/
	//$apiKey = kurl('localhost:8080/api','POST','{"devicetype":"my gateway"}');
	getThingsToken();
	$output = kurl('localhost:8080/api/'.$apiKey."/lights","GET");
	if (!empty($output)) {
		$json_obj = json_decode($output);
		$count = 0;
		foreach ($json_obj as $obj) {
			$count++;
			//echo $count.". Name: ".$obj->name;
		}

		for ($i=1; $i <= $count; $i++) { 
			echo "Lampu ke".$i."<br>";

			$output = kurl($gatewayBaseUrl.$apiKey."//lights/".$i,"GET","");

			//echo $output;
			$temp_json_obj = json_decode($output, true);
			$hex = fGetHex((($temp_json_obj['state']['hue'] / 65535) * 360),($temp_json_obj['state']['sat']/255) * 100,100);
			echo "Nama : ".$temp_json_obj['name']."<br>";
			echo "<div id='".$i."-status'>onOff: ".($temp_json_obj['state']['on'] == true ? "true":"false")."</div><br><br>";
			/*echo '<form action="process.php" method="POST">
			<input type="hidden" name="action" value="1">
			<input type="hidden" name="id" value="'.$i.'">
			<input type="hidden" name="value" value="'.($temp_json_obj['state']['on'] == true ? "false":"true").'"><br>
			<input type="submit" value="'.($temp_json_obj['state']['on'] == true ? "Matiin":"Nyalain").'">
			</form>';*/
			echo '<button id="'.$i.'-onOff" onclick="onOff('.$i.','.($temp_json_obj['state']['on'] == true ? "false":"true").')" >'.($temp_json_obj['state']['on'] == true ? "Matiin":"Nyalain").'</button><br>';
			echo '<input type="range" id="'.$i.'-bri" onchange="brightness('.$i.')" style="width:255px" value="'.$temp_json_obj['state']['bri'].'" min="0" max="255" step="1"><br>';
			echo '<input value="'.$hex.'" onchange="hsv('.$i.',this.color.hsv[0],this.color.hsv[1])" id="'.$i.'-hsv" class="color {pickerMode:\'HSV\', slider:false}"><br>';
			$exist = dbSelect('things','*','local_id = '.$i);
			//print_r($exist);
			if (mysqli_num_rows($exist) < 1) {
				echo '<form action="process.php" method="POST">
					<input type="hidden" name="action" value="2">
					<input type="hidden" name="id" value="'.$i.'">
					<input type="hidden" name="nama" value="'.$temp_json_obj['name'].'">
					<input type="submit" value="Daftarkan '.$temp_json_obj['name'].'">
				</form>';
			}else{
				$result = $exist->fetch_assoc();
				// print_r($result);
				echo "Things sudah terdaftar dengan id: ".$result['id'];
				
			}
			echo "<br><hr><br>";
		}
	}
	
?>

<script type="text/javascript" src="jscolor/jscolor.js"></script>
<script>
var xmlhttp;

function brightness(id){
	var nilai = document.getElementById(id+"-bri").value;
	loadXMLDoc("action=1&attr=bri&id="+id+"&value="+nilai, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	//console.log(result);
		  	if (result.includes('sukses')) {
		  		//console.log(result);

		  	}else{
		  		alert("gagal ubah brightness");
		  	}
		  }
	});
}

function hsv(id,hues,sats){
	//console.log("hue: "+hues+", sat: "+sats);
	var hue = Math.ceil((hues / 6) * 65535);
	var sat = Math.ceil(sats * 255);
	var konten = "action=1&attr=hue::sat&id="+id+"&value="+hue+"::"+sat;
	//console.log(konten);
	loadXMLDoc(konten, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	//console.log(result);
		  	if (result.includes('sukses')) {
		  		//console.log(result);

		  	}else{
		  		//console.log(result);
		  		//alert("gagal ubah hsv");
		  	}
		  }
	});
}

function onOff(id, nilai){
	loadXMLDoc("action=1&attr=on&id="+id+"&value="+nilai, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	//console.log(result);
		  	if (result.includes('sukses')) {
		  		var state = result.split(":")[1];
		  		document.getElementById(id+"-status").innerHTML="onOff: "+state;
		  		document.getElementById(id+"-onOff").innerHTML=(state == 'false' ? "Nyalain":"Matiin");
		  		document.getElementById(id+"-onOff").setAttribute("onclick","onOff("+id+","+(state == 'false' ? "true":"false")+")");

		  	};
		  }
	});
}

function loadXMLDoc(konten, callback)
{

if (window.XMLHttpRequest)
  {// code for IE7+, Firefox, Chrome, Opera, Safari
  xmlhttp=new XMLHttpRequest();
  }
else
  {// code for IE6, IE5
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
xmlhttp.onreadystatechange=callback;
xmlhttp.open("POST","process.php",true);
xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
//console.log(konten);
xmlhttp.send(konten);
}
</script>