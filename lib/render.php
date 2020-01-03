<?php

include __DIR__ . '/../vendor/autoload.php';
//set_include_path(__DIR__);
//spl_autoload_register();

$sheetBuilder = new DiveraSpreadSheet\SheetBuilder(
    json_decode(file_get_contents(__DIR__ . '/../data/all.json'), true),
    json_decode(file_get_contents(__DIR__ . '/../data/events.json'), true)
);

$rows = $sheetBuilder->build();

print_r($rows);

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$worksheet = $spreadsheet->getActiveSheet();
$rowIndex = 0;
foreach ($rows as $row) {
    $colIndex = 0;
    /** @var \DiveraSpreadSheet\SheetCell $cell */
    foreach ($row as $cell) {
        echo "$rowIndex $colIndex -- {$cell->getText()}\n";
        $c = $worksheet->getCellByColumnAndRow($colIndex, $rowIndex);
        $c->setValue($cell->getText());
        if ($cell->getBg()) {
            $c->getStyle()->getFill()->getStartColor()->setRGB($cell->getBg());
        }
        $colIndex++;
    }
    $rowIndex++;
}

$html = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
$html->save("php://stdout");

$xlsx = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$xlsx->save("/tmp/tests.xlsx");
