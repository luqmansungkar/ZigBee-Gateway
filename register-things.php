<head>
	<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<?php
	if (isset($_POST['register'])) {
		$value = $_POST['register'];
		foreach ($value as $nilai) {
			echo $nilai."<br>";
		}
	}
?>

<h3>Pilih hak akses untuk tiap atribut :</h3>
<form action="process.php" method="POST" >
	<input type="hidden" name="action" value="2">
	<input type="hidden" name="id" value="<?php echo $_GET["id"] ?>">
	<input type="hidden" name="nama" value="<?php echo $_GET["nama"] ?>">
	<table class="table">
		<thead>
			<tr>
				<th></th>
				<th>Berikan akses kontrol</th>
				<th>Bagikan ke internet</th>
			</tr>
		</thead>
		<tr>
			<td>Brightness</td>
			<td><input name="bri[]" value="ctrl" type="checkbox"></td>
			<td><input name="bri[]" value="acc" type="checkbox"></td>
		</tr>
		<tr>
			<td>Hue</td>
			<td><input name="hue[]" value="ctrl" type="checkbox"></td>
			<td><input name="hue[]" value="acc" type="checkbox"></td>
		</tr>
		<tr>
			<td>Saturation</td>
			<td><input name="sat[]" value="ctrl" type="checkbox"></td>
			<td><input name="sat[]" value="acc" type="checkbox"></td>
		</tr>
		<tr>
			<td colspan="3">
				<button type="submit" class="btn btn-default">Daftar</button>
			</td>
		</tr>
	</table>

</form>