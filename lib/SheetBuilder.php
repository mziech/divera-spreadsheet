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


class SheetBuilder {

    private $data;
    private $all;
    private $events;
    private $xlsxLinks;

    /**
     * SheetBuilder constructor.
     * @param $events
     * @param $xlsxLinks
     */
    public function __construct($events, $xlsxLinks) {
        $this->data = Data::get();
        $this->all = $this->data->getAll();
        $this->events = $events;
        $this->xlsxLinks = $xlsxLinks;
    }

    private function getSortedUcrs() {
        $ucrToRank = [];
        foreach ($this->all["data"]["cluster"]["consumer"] as $ucr => $user) {
            $ucrToRank[$ucr] = [
                $this->getGroupRank($user["groups"]),
                $this->getUserRank($ucr, $user["qualifications"]),
                $user["stdformat_name"]
            ];
        }

        $ucrs = array_keys($ucrToRank);
        usort($ucrs, function ($a, $b) use ($ucrToRank) {
            return $this->arraycmp($ucrToRank[$a], $ucrToRank[$b]);
        });
        return $ucrs;
    }

    private function arraycmp($a, $b) {
        if (count($a) !== count($b)) {
            return count($a) - count($b);
        }

        if (empty($a) && empty($b)) {
            return 0;
        }

        if ($a[0] === $b[0]) {
            return $this->arraycmp(array_slice($a, 1), array_slice($b, 1));
        }

        if (is_numeric($a[0]) && is_numeric($b[0])) {
            return $a[0] - $b[0];
        }

        return strcasecmp($a[0], $b[0]);
    }

    private function getGroupRank($groups) {
        foreach (array_keys(Config::get()->groups) as $index => $group) {
            if (in_array($group, $groups)) {
                return $index;
            }
        }
        return 999;
    }

    private function getGroupCell($groups) {
        foreach (Config::get()->groups as $group => $config) {
            if (in_array($group, $groups)) {
                return SheetCell::text($config["short"])->setBg($config["bg"]);
            }
        }
        return SheetCell::text("???");
    }

    private function getUserRank($ucr, $qualifications) {
        if (array_key_exists($ucr, Config::get()->users) && array_key_exists("rank", Config::get()->users[$ucr])) {
            return Config::get()->users[$ucr]["rank"];
        }

        $current = null;
        foreach (Config::get()->qualificationRanking as $qualification => $rank) {
            if (in_array($qualification, $qualifications)) {
                if ($current == null) {
                    $current = $rank;
                } else {
                    $current = min($rank, $current);
                }
            }
        }
        return $current == null ? 0 : $current;
    }


    public function build() {
        $lastBg = '';
        $rows = [];

        $rows[] = array_merge([
            SheetCell::text(""),
            SheetCell::text(date("d.m.Y H:i", $this->data->getTimestamp()))->setBg("#00ffff"),
            SheetCell::text("BETA")->setBg("#00ff00")->setCenter(true),
            SheetCell::text(""),
        ], $this->getEventBlanks());

        $rows[] = array_merge([
            SheetCell::text("Nr"),
            SheetCell::text("Name"),
            SheetCell::text("Gruppe"),
            SheetCell::text("Eigener Status"),
        ], $this->getEventHeaders());

        if ($this->xlsxLinks) {
            $rows[] = array_merge([
                SheetCell::text(""),
                SheetCell::text("💾 Alle in Excel")->setUrl($this->xlsxLinks ? "xlsx.php" : null),
                SheetCell::text(""),
                SheetCell::text(""),
            ], $this->getEventXlsxLinks());
        }

        $statusCells = $this->getStatusCells();

        $nr = 1;
        foreach ($this->getSortedUcrs() as $ucr) {
            $user = $this->all["data"]["cluster"]["consumer"][$ucr];
            $nameCell = SheetCell::text($user["stdformat_name"]);
            $groupCell = $this->getGroupCell($user["groups"]);
            if ($lastBg != $groupCell->getBg()) {
                $nameCell->setBg($groupCell->getBg());
                $lastBg = $groupCell->getBg();
            }

            $rows[] = array_merge([
                SheetCell::text($nr++)->setCenter(true),
                $nameCell,
                $groupCell,
                array_key_exists($ucr, $statusCells) ? $statusCells[$ucr] : SheetCell::text("")
            ], $this->getEventCells($ucr));
        }
        return $rows;
    }

    private function getStatusCells() {
        $statusCells = [];
        foreach ($this->all["data"]["cluster"]["status"] as $id => $status) {
            $statusCells[$id] = SheetCell::text(substr($status["name"], 0, Config::get()->statusLength))
                ->setBg('#' . $status["color_hex"])
                ->setCenter(true);
        }

        $ucrCells = [];
        foreach ($this->all["data"]["monitor"]["3"] as $ucr => $monitor) {
            if (array_key_exists($monitor["status"], $statusCells)) {
                $ucrCells[$ucr] = $statusCells[$monitor["status"]];
            }
        }

        return $ucrCells;
    }

    private function getEventHeaders() {
        return array_map(function ($event) {
            return SheetCell::text($this->getEventTime($event) . "\r\n\r\n" . $event["title"])
                ->setCenter(true)->setWrap(true);
        }, array_values($this->events["data"]["items"]));
    }

    private function getEventXlsxLinks() {
        return array_map(function ($event) {
            return SheetCell::text("💾 Excel")
                ->setUrl("xlsx.php?event=" . $event["id"]);
        }, array_values($this->events["data"]["items"]));
    }

    private function getEventBlanks() {
        return array_map(function ($event) {
            return SheetCell::text("");
        }, array_values($this->events["data"]["items"]));
    }

    private function getEventTime($event) {
        $zone = new \DateTimeZone("Europe/Berlin");
        $start = new \DateTime('@' . $event["start"]);
        $end = new \DateTime('@' . $event["end"]);
        $start->setTimezone($zone);
        $end->setTimezone($zone);

        $startDay = $start->format("d.m.Y");
        $endDay = $end->format("d.m.Y");

        if ($event["fullday"]) {
            if ($startDay !== $endDay) {
                return "$startDay - $endDay";
            } else {
                return $startDay;
            }
        } else {
            $startTime = $start->format("H:i");
            $endTime = $end->format("H:i");

            if ($startDay !== $endDay) {
                return "$startDay $startTime - $endDay $endTime";
            } else if ($startTime !== $endTime) {
                return "$startDay\r\n$startTime - $endTime";
            } else {
                return "$startDay\r\n$startTime";
            }
        }
    }

    private function getEventCells($ucr) {
        return array_map(function ($event) use ($ucr) {
            $cell = new SheetCell();
            $cell->setCenter(true);
            if (!in_array($ucr, $event["ucr_addressed"])) {
                $cell->setBg(Config::get()->notAddressedBg);
            }

            foreach ($event["participationnotes"] as $note) {
                if ($note["id"] === $ucr) {
                    $cell->setComment($note["note"] . PHP_EOL . PHP_EOL . date("d.m.Y H:i", $note["ts"]));
                }
            }

            foreach ($event["participationlist"] as $answer => $ucrs) {
                if (in_array($ucr, $ucrs)) {
                    $responseType = Config::get()->responseTypes[$answer];
                    $cell->setText($responseType["text"])->setBg($responseType["bg"]);
                }
            }

            return $cell;
        }, array_values($this->events["data"]["items"]));
    }

}
