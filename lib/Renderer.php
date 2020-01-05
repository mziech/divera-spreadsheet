<?php


namespace DiveraSpreadSheet;


use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Renderer {

    private $event;
    private $data;
    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct() {
        date_default_timezone_set(Config::get()->timeZone);
        $this->event = $_GET["event"];
        $this->authentication = Authentication::get();
        $this->data = Data::get();
    }

    private function createSpreadsheet($xlsxLinks=true) {
        $sheetBuilder = new SheetBuilder(
            $this->data->getEvents($this->event),
            $xlsxLinks && !$this->authentication->getDashboard()
        );

        $rows = $sheetBuilder->build();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setTitle($this->data->getEventName($this->event));
        $worksheet = $spreadsheet->getActiveSheet();
        $rowIndex = 1;
        $lastColumn = 'A';
        foreach ($rows as $row) {
            $colIndex = 1;
            /** @var \DiveraSpreadSheet\SheetCell $cell */
            foreach ($row as $cell) {
                $c = $worksheet->getCellByColumnAndRow($colIndex, $rowIndex);
                $c->getStyle()->getBorders()->getAllBorders()->getColor()->setRGB("000000");
                $c->getStyle()->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $c->getStyle()->getFont()->setName("Arial");
                $c->getStyle()->getAlignment()->setWrapText($cell->isWrap());
                if ($cell->getCenter()) {
                    $c->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }
                $c->setValue($cell->getText());
                if ($cell->getBg()) {
                    $c->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $c->getStyle()->getFill()->getStartColor()->setRGB(substr($cell->getBg(), 1));
                }
                if ($cell->getUrl()) {
                    $c->getHyperlink()->setUrl($cell->getUrl());
                }
                if ($rowIndex == 1) {
                    $lastColumn = $c->getColumn();
                }
                $colIndex++;
            }
            $rowIndex++;
        }
        $worksheet->getAutoFilter()->setRange("A2:{$lastColumn}2");
        for ($col = 'A'; $col <= 'C'; $col++) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
        $worksheet->calculateColumnWidths();

        return $spreadsheet;
    }

    public function html() {
        $spreadsheet = $this->createSpreadsheet();
        $spreadsheet->getActiveSheet()->getPageMargins()
            ->setTop(0.0)
            ->setBottom(0.0)
            ->setLeft(0.0)
            ->setRight(0.0);
        $html = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
        $html->setUseEmbeddedCSS(true);
        $html->setUseInlineCss(true);
        $html->save("php://output");
    }

    public function xlsx() {
        $title = preg_replace("/[^a-z0-9 -]/i", "", $this->data->getEventName($this->event));
        $xlsx = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->createSpreadsheet(false));
        header('Content-disposition: attachment; filename="'. $title .'.xlsx"');
        $xlsx->save("php://output");
    }

}