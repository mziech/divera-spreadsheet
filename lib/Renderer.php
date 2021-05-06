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


namespace DiveraSpreadSheet;


use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Renderer {

    private $alarm;
    private $event;
    private $data;
    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct() {
        // Workaround for https://github.com/ezyang/htmlpurifier/issues/71
        $html_purifier_cache_dir = sys_get_temp_dir() . '/HTMLPurifier/DefinitionCache';
        if (!is_dir($html_purifier_cache_dir)) {
            mkdir($html_purifier_cache_dir, 0770, TRUE);
        }
        \HTMLPurifier_ConfigSchema::instance()->add('Cache.SerializerPath', $html_purifier_cache_dir, \HTMLPurifier_VarParser::C_STRING, true);

        date_default_timezone_set(Config::get()->timeZone);
        $this->alarm = $_GET["alarm"];
        $this->event = $_GET["event"];
        $this->authentication = Authentication::get();
        $this->data = Data::get();
    }

    private function createSpreadsheet($xlsxLinks=true) {
        $sheetBuilder = new SheetBuilder(
            $this->data->getAlarms($this->alarm, $this->event),
            $this->data->getEvents($this->alarm, $this->event),
            $xlsxLinks && !$this->authentication->getDashboard()
        );

        $rows = $sheetBuilder->build();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setTitle($this->data->getFilename($this->alarm, $this->event));
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
                if ($cell->getComment()) {
                    $text = $worksheet->getComment($c->getCoordinate())->getText()->createText($cell->getComment());
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
        $worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
        $worksheet->freezePaneByColumnAndRow(4, 3);

        return $spreadsheet;
    }

    public function html() {
        $spreadsheet = $this->createSpreadsheet();
        $spreadsheet->getActiveSheet()->getPageMargins()
            ->setTop(0.0)
            ->setBottom(0.0)
            ->setLeft(0.0)
            ->setRight(0.0);
        $html = new CustomHtml($spreadsheet);
        $html->setUseEmbeddedCSS(true);
        $html->save("php://output");
    }

    public function xlsx() {
        $title = preg_replace("/[^a-z0-9 -]/i", "", $this->data->getFilename($this->alarm, $this->event));
        $xlsx = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->createSpreadsheet(false));
        header('Content-disposition: attachment; filename="'. $title .'.xlsx"');
        $xlsx->save("php://output");
    }

}