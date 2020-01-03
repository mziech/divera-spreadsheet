<?php
include __DIR__. '/../vendor/autoload.php';
$authentication = \DiveraSpreadSheet\Authentication::get();
?>
<!doctype html>
<html>
<head>
    <title>Divera Spreadsheet</title>
</head>
<body>
<pre>
<?php
print_r($authentication);
?>
</pre>

</body>
</html>
