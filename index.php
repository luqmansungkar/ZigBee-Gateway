<?php
include 'functions.php';
include 'settings.php';
?>
<head>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<script src="js/jquery-2.1.4.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
</head>
<?php
	$userExist = dbSelect('setting','*',"kunci = 'user_id'");
	//print_r($userExist);
	if (mysqli_num_rows($userExist) < 1) {
?>
<div id="login">
	<button style="margin-left:25px;margin-top:25px" class="btn btn-primary" data-toggle="modal" data-target=".fade">Login</button>

	<div class="modal fade">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Masukkan email anda</h4>
	      </div>
	      <div class="modal-body">
      		<input type="email" class="form-control" id="email" placeholder="nama@domain.com">
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="login()">Submit</button>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</div>
<?php 
	}
?>
<div class="row">
  <div class="col-md-6" style="padding-left:40px">
  <h2>Lights</h2>
<?php

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
		//$count = 0;
		$id_lampu = [];
		foreach ($json_obj as $key => $value) {
			array_push($id_lampu, $key);
			//echo $count.". Name: ".$obj->name;
		}
		//print_r($id_lampu);
		for ($i=0; $i < count($id_lampu); $i++) { 
			echo "Lampu ke".($i+1)."<br>";

			$output = kurl($gatewayBaseUrl.$apiKey."//lights/".$id_lampu[$i],"GET","");
			//echo $output;
			//echo $output;
			$temp_json_obj = json_decode($output, true);
			$hex = fGetHex((($temp_json_obj['state']['hue'] / 65535) * 360),($temp_json_obj['state']['sat']/255) * 100,100);
			echo "Nama : ".$temp_json_obj['name']."<br>";
			echo "<div id='".$id_lampu[$i]."-status'>onOff: ".($temp_json_obj['state']['on'] == true ? "true":"false")."</div><br><br>";

			echo '<button class="btn btn-default" id="'.$id_lampu[$i].'-onOff" onclick="onOff('.$id_lampu[$i].','.($temp_json_obj['state']['on'] == true ? "false":"true").',1)" >'.($temp_json_obj['state']['on'] == true ? "Matiin":"Nyalain").'</button><br>';
			echo '<input type="range" id="'.$id_lampu[$i].'-bri-1" onchange="brightness('.$id_lampu[$i].',1)" style="width:255px" value="'.$temp_json_obj['state']['bri'].'" min="0" max="255" step="1"><br>';
			echo '<input value="'.$hex.'" onchange="hsv('.$id_lampu[$i].',this.color.hsv[0],this.color.hsv[1],1)" id="'.$id_lampu[$i].'-hsv" class="color {pickerMode:\'HSV\', slider:false}"><br><br>';
			$exist = dbSelect('things','*','local_id = '.$id_lampu[$i].' and type = "Lampu"');
			//print_r($exist);
			if (mysqli_num_rows($exist) < 1) {

				echo '<a href="register-things.php?tipe=1&id='.$id_lampu[$i].'&nama='.$temp_json_obj['name'].'"><button class="btn btn-default">Daftarkan '.$temp_json_obj['name'].'</button></a>';
			}else{
				$result = $exist->fetch_assoc();
				// print_r($result);
				echo "<br>Atribut yang diberikan diperbolehkan di akses adalah: ".$result['access']."<br>";
				echo "Atribut yang diberikan diperbolehkan di kontrol adalah: ".$result['control']."<br><br>";
				echo "Things sudah terdaftar dengan id: ".$result['id'];
				
			}
			echo "<br><hr><br>";
		}
	}
	
?>
</div>
<div class="col-md-6">
	<h2>Groups</h2>
<?php 
	$output2 = kurl('localhost:8080/api/'.$apiKey."/groups","GET");
	if (!empty($output2)) {
		$json_obj = json_decode($output2);
		$id_lampu = [];
		foreach ($json_obj as $key => $value) {
			array_push($id_lampu, $key);
			//echo $count.". Name: ".$obj->name;
		}
		for ($i=0; $i < count($id_lampu); $i++) { 
			echo "Grup ke".($i+1)."<br>";

			$output2 = kurl($gatewayBaseUrl.$apiKey."//groups/".$id_lampu[$i],"GET","");
			//echo $output2;
			//echo $output2;
			$temp_json_obj = json_decode($output2, true);
			$hex = fGetHex((($temp_json_obj['action']['hue'] / 65535) * 360),($temp_json_obj['action']['sat']/255) * 100,100);
			echo "Nama : ".$temp_json_obj['name']."<br>";
			echo "<div id='".$id_lampu[$i]."-status-2'>onOff: ".($temp_json_obj['action']['on'] == true ? "true":"false")."</div>";
			$idAnggota = "";
			foreach ($temp_json_obj['lights'] as $key) {
				$idAnggota .= $key.",";
			}
			$idAnggota = rtrim($idAnggota,",");
			echo "Id Anggota: ".$idAnggota."<br><br>";
			echo '<button class="btn btn-default" id="'.$id_lampu[$i].'-onOff-2" onclick="onOff('.$id_lampu[$i].','.($temp_json_obj['action']['on'] == true ? "false":"true").',2)" >'.($temp_json_obj['action']['on'] == true ? "Matiin":"Nyalain").'</button><br>';
			echo '<input type="range" id="'.$id_lampu[$i].'-bri-2" onchange="brightness('.$id_lampu[$i].',2)" style="width:255px" value="'.$temp_json_obj['action']['bri'].'" min="0" max="255" step="1"><br>';
			echo '<input type="hidden" id="'.$id_lampu[$i].'-anggota" value="'.$idAnggota.'">';
			echo '<input value="'.$hex.'" onchange="hsv('.$id_lampu[$i].',this.color.hsv[0],this.color.hsv[1],2)" id="'.$id_lampu[$i].'-hsv-2" class="color {pickerMode:\'HSV\', slider:false}"><br><br>';
			$exist = dbSelect('things','*','local_id = '.$id_lampu[$i].' and type = "Group"');
			//print_r($exist);
			if (mysqli_num_rows($exist) < 1) {
				echo '<a href="register-things.php?tipe=2&id='.$id_lampu[$i].'&nama='.$temp_json_obj['name'].'"><button class="btn btn-default">Daftarkan '.$temp_json_obj['name'].'</button></a>';
			}else{
				$result = $exist->fetch_assoc();
				// print_r($result);
				echo "<br>Atribut yang diberikan diperbolehkan di akses adalah: ".$result['access']."<br>";
				echo "Atribut yang diberikan diperbolehkan di kontrol adalah: ".$result['control']."<br><br>";
				echo "Group sudah terdaftar dengan id: ".$result['id'];
				
			}
			echo "<br><hr><br>";
		}
	}
?>
</div>
</div>

<script type="text/javascript" src="jscolor/jscolor.js"></script>
<script>
var xmlhttp;

function brightness(id,mode){
	var nilai = document.getElementById(id+"-bri-"+mode).value;
	loadXMLDoc("mode="+mode+"&action=1&attr=bri&id="+id+"&value="+nilai, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	console.log(result);
		  	if (result.includes('sukses')) {
		  		//console.log(result);

		  	}else{
		  		alert("gagal ubah brightness");
		  	}
		  }
	});
}

function hsv(id,hues,sats,mode){
	//console.log("hue: "+hues+", sat: "+sats);
	var hue = Math.ceil((hues / 6) * 65535);
	var sat = Math.ceil(sats * 255);
	var konten = "mode="+mode+"&action=1&attr=hue::sat&id="+id+"&value="+hue+"::"+sat;
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

function onOff(id, nilai,mode){
	var konten = "mode="+mode+"&action=1&attr=on&id="+id+"&value="+nilai;
	//console.log(xmlhttp);
	loadXMLDoc(konten, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	//console.log(result);
		  	if (result.includes('sukses')) {
		  		//console.log(result);
		  		var state = result.split(":")[1];
		  		if (mode == 2) {
		  			document.getElementById(id+"-status-2").innerHTML="onOff: "+state;
		  			document.getElementById(id+"-onOff-2").innerHTML=(state == 'false' ? "Nyalain":"Matiin");
		  			document.getElementById(id+"-onOff-2").setAttribute("onclick","onOff("+id+","+(state == 'false' ? "true":"false")+",2)");

		  			var anggota = document.getElementById(id+"-anggota").value;
		  			anggota = anggota.split(",");
		  			for (var i = 0; i < anggota.length; i++) {
		  				//console.log(anggota[i]);
		  				document.getElementById(anggota[i]+"-status").innerHTML="onOff: "+state;
		  				document.getElementById(anggota[i]+"-onOff").innerHTML=(state == 'false' ? "Nyalain":"Matiin");
		  				document.getElementById(anggota[i]+"-onOff").setAttribute("onclick","onOff("+anggota[i]+","+(state == 'false' ? "true":"false")+",1)");
		  			};

		  		}else{
		  			document.getElementById(id+"-status").innerHTML="onOff: "+state;
		  			document.getElementById(id+"-onOff").innerHTML=(state == 'false' ? "Nyalain":"Matiin");
		  			document.getElementById(id+"-onOff").setAttribute("onclick","onOff("+id+","+(state == 'false' ? "true":"false")+",1)");
		  		}

		  	};
		  }
	});
}

function login(){
	var email = document.getElementById("email").value;
	var konten = "action=3&email="+email;
	loadXMLDoc(konten, function(){
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  	//document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
		  	var result = xmlhttp.responseText;
		  	console.log(result);
		  	if (result == 1) {
		  		//console.log(result);
		  		var div = document.getElementById("login");
		  		div.parentNode.removeChild(div);
		  	}else{
		  		//console.log(result);
		  		//alert("gagal ubah hsv");
		  	}
		  }
	});
}

function loadXMLDoc(konten, callback)
{
console.log(konten);
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