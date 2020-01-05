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
print_r(date_default_timezone_get());
echo("<br>");
print_r($authentication);
?>
</pre>

</body>
</html>
