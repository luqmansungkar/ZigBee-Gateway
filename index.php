<?php
include 'functions.php';
include 'settings.php';
	/*$command = "/opt/jdk1.8.0_33/bin/java -classpath /home/pi/cobaJava/org.eclipse.po.mqtt.utility.jar:/home/pi/cobaJava/org.eclipse.paho.client.mqttv3.jar:/home/pi/cobaJava Mulai 2>&1";
	/*$keluaran = exec($command);
	echo "keluaran: ";
	print_r($keluaran);*/

	/*$bash = "sudo /home/pi/cobaJava/tes 2>&1";
	$output = shell_exec($bash);
	echo "hasil: ";
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
			echo "Nama : ".$temp_json_obj['name']."<br>";
			echo "onOff: ".($temp_json_obj['state']['on'] == true ? "true":"false");
			echo '<form action="process.php" method="POST">
			<input type="hidden" name="action" value="1">
			<input type="hidden" name="id" value="'.$i.'">
			<input type="hidden" name="value" value="'.($temp_json_obj['state']['on'] == true ? "false":"true").'"><br>
			<input type="submit" value="'.($temp_json_obj['state']['on'] == true ? "Matiin":"Nyalain").'">
			</form>';
			$exist = dbSelect('things','*','local_id = '.$i);
			if (mysqli_num_rows($exist) < 0) {
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
			
		}
	}
	
?>
