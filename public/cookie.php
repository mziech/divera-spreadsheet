<?php
include __DIR__. '/../vendor/autoload.php';
// \DiveraSpreadSheet\Authentication::setDashboardCookie();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Divera Spreadsheet Dashboard Cookie</title>
</head>
<body>

<h1>Dashboard Cookie</h1>

<form action="" method="post">
    <p>Set cookie value:</p>
    <label for="token">
        Token:
    </label>
    <input type="hidden" name="action" value="set">
    <input id="token" type="text" name="token" value="<?php echo htmlentities(\DiveraSpreadSheet\Authentication::getDashboardCookie()); ?>">
    <button type="submit">Set</button>
</form>

<form action="" method="post">

    <input type="hidden" name="action" value="set">
    <input id="token" type="text" name="token" value="<?php echo htmlentities(\DiveraSpreadSheet\Authentication::getDashboardCookie()); ?>">
    <button type="submit">Set</button>
</form>

</body>
</html>
