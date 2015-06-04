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
    include "settings.php";
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
    //return $sql;
    return dbQuery($sql);
}

function dbSelect($table_name, $select_query, $where_query){
	
	$sql = "SELECT ".$select_query." FROM ".$table_name;
	if ($where_query != '') {
		$sql .= " WHERE ".$where_query;
	}
	//echo $sql;
	//return $sql;
    return dbQuery($sql);
	
}

function dbQuery($sql){
	include "database.php";

	$result = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $result;
}


/*
    **  Converts HSV to RGB values
    ** –––––––––––––––––––––––––––––––––––––––––––––––––––––
    **  Reference: http://en.wikipedia.org/wiki/HSL_and_HSV
    **  Purpose:   Useful for generating colours with
    **             same hue-value for web designs.
    **  Input:     Hue        (H) Integer 0-360
    **             Saturation (S) Integer 0-100
    **             Lightness  (V) Integer 0-100
    **  Output:    String "R,G,B"
    **             Suitable for CSS function RGB().
    */
	//credit : https://gist.github.com/Jadzia626/2323023

    function fGetHex($iH, $iS, $iV) {

        if($iH < 0)   $iH = 0;   // Hue:
        if($iH > 360) $iH = 360; //   0-360
        if($iS < 0)   $iS = 0;   // Saturation:
        if($iS > 100) $iS = 100; //   0-100
        if($iV < 0)   $iV = 0;   // Lightness:
        if($iV > 100) $iV = 100; //   0-100

        $dS = $iS/100.0; // Saturation: 0.0-1.0
        $dV = $iV/100.0; // Lightness:  0.0-1.0
        $dC = $dV*$dS;   // Chroma:     0.0-1.0
        $dH = $iH/60.0;  // H-Prime:    0.0-6.0
        $dT = $dH;       // Temp variable

        while($dT >= 2.0) $dT -= 2.0; // php modulus does not work with float
        $dX = $dC*(1-abs($dT-1));     // as used in the Wikipedia link

        switch($dH) {
            case($dH >= 0.0 && $dH < 1.0):
                $dR = $dC; $dG = $dX; $dB = 0.0; break;
            case($dH >= 1.0 && $dH < 2.0):
                $dR = $dX; $dG = $dC; $dB = 0.0; break;
            case($dH >= 2.0 && $dH < 3.0):
                $dR = 0.0; $dG = $dC; $dB = $dX; break;
            case($dH >= 3.0 && $dH < 4.0):
                $dR = 0.0; $dG = $dX; $dB = $dC; break;
            case($dH >= 4.0 && $dH < 5.0):
                $dR = $dX; $dG = 0.0; $dB = $dC; break;
            case($dH >= 5.0 && $dH < 6.0):
                $dR = $dC; $dG = 0.0; $dB = $dX; break;
            default:
                $dR = 0.0; $dG = 0.0; $dB = 0.0; break;
        }

        $dM  = $dV - $dC;
        $dR += $dM; $dG += $dM; $dB += $dM;
        $dR *= 255; $dG *= 255; $dB *= 255;

        $dR = str_pad(dechex(round($dR)), 2, "0", STR_PAD_LEFT);
        $dG = str_pad(dechex(round($dG)), 2, "0", STR_PAD_LEFT);
        $dB = str_pad(dechex(round($dB)), 2, "0", STR_PAD_LEFT);
        return $dR.$dG.$dB;
    }

?>