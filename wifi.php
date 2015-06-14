<?php
	$bash = "sudo iwlist wlan1 scan | grep ESSID"; //2>&1

	$output = shell_exec($bash);
	//echo "hasil: <br>";
	//echo $output;
	echo "Daftar access point di sekitar anda:<br>";
	$ssid = explode("ESSID:", $output);
	for ($i=0; $i < count($ssid); $i++) { 
		//$ssid[$i] = substr($ssid[$i],1,-1);
		$ssid[$i] = trim($ssid[$i]);
		$ssid[$i] = str_replace('"',"", $ssid[$i]);

		echo '<a href="?id='.$ssid[$i].'">'.$ssid[$i].'</a><br>';
	}
	echo "<br><br>";
	if (isset($_GET['id'])) {
		$id = $_GET['id'];
		$bash = 'sudo iwconfig wlan1 essid "'.$id.'"'; //2>&1
		$output = shell_exec($bash);
		$bash = "sudo dhclient wlan1"; //2>&1
		$output = shell_exec($bash);
		$bash = "hostname -I";
		$output = shell_exec($bash);
		$ip = explode(" ", $output);
		echo 'Silakan hubungkan perangkat anda dengan '.$id.', kemudian buka <a href="http://'.$ip[1].'/ta">'.$ip[1].'/ta</a>';
	}
?>