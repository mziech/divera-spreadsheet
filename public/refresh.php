<?php
include __DIR__. '/../vendor/autoload.php';
?>
<!doctype html>
<html>
<head>
    <title>Divera Spreadsheet</title>
    <meta http-equiv="refresh" content="5; URL=index.php"></head>
<body>
<?php
\DiveraSpreadSheet\Data::refresh();
?>
<div style="text-align: center; font-size: larger">
    Aktualisierung wurde durchgeführt, Sie werden in wenigen Sekunden wieder auf <a href="index.php">die Liste</a> weitergeleitet.
</div>
</body>
</html>
