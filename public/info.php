<?php
/*
 * divera-spreadsheet - A tool to format Divera API responses as a spreadsheet
 * Copyright © 2020 Marco Ziech (marco@ziech.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

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
print_r(ini_get("session.gc_maxlifetime"));
echo("<br>");
print_r($authentication);
echo("<br>");
print_r(phpCAS::getAttributes());
?>
</pre>

</body>
</html>
