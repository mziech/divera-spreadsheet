<?php
include __DIR__. '/../vendor/autoload.php';
if ($_POST['action'] === 'set') {
    \DiveraSpreadSheet\Authentication::setDashboardCookie($_POST['token']);
} else if ($_POST['action'] === 'generate') {
    $token = \DiveraSpreadSheet\Authentication::generateDashboardCookie();
}

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

<?php
    if ($_POST['action'] === 'set') {
        echo '<p>Dashboard cookie set</p>';
    }
?>

<form action="" method="post">
    <p>Set cookie value:</p>
    <label for="token">
        Token:
    </label>
    <input type="hidden" name="action" value="set">
    <input id="token" type="text" name="token" value="">
    <button type="submit">Set</button>
</form>

<?php
if ($_POST['action'] === 'generate') {
    echo "<p>Generated dashboard token: $token</p>";
}
?>

<form action="" method="post">
    <input type="hidden" name="action" value="generate">
    <button type="submit">Generate</button>
</form>

</body>
</html>
