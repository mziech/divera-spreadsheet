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
    private $alarms;
    private $events;
    private $xlsxLinks;

    /**
     * SheetBuilder constructor.
     * @param $events
     * @param $xlsxLinks
     */
    public function __construct($alarms, $events, $xlsxLinks) {
        $this->data = Data::get();
        $this->all = $this->data->getAll();
        $this->alarms = $alarms;
        $this->events = $events;
        $this->xlsxLinks = $xlsxLinks;
    }

    private function getSortedUcrs() {
        $ucrToRank = [];
        foreach ($this->all["data"]["cluster"]["consumer"] as $ucr => $user) {
            if (!empty(array_intersect($user["groups"], Config::get()->excludeGroups))) {
                continue;
            }

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
            Authentication::get()->getAdmin() ? SheetCell::text("↻")->setUrl("refresh.php") : SheetCell::text(""),
            SheetCell::text(date("d.m.Y H:i", $this->data->getTimestamp()))->setBg("#00ffff"),
            SheetCell::text("BETA")->setBg("#00ff00")->setCenter(true),
            SheetCell::text(""),
        ], $this->getAlarmBlanks(), $this->getEventBlanks());

        $rows[] = array_merge([
            SheetCell::text("Nr"),
            SheetCell::text("Name"),
            SheetCell::text("Gruppe"),
            SheetCell::text("Eigener Status"),
        ], $this->getAlarmHeaders(), $this->getEventHeaders());

        if ($this->xlsxLinks) {
            $rows[] = array_merge([
                SheetCell::text(""),
                SheetCell::text("💾 Alle in Excel")->setUrl($this->xlsxLinks ? "xlsx.php" : null),
                SheetCell::text(""),
                SheetCell::text(""),
            ], $this->getAlarmXlsxLinks(), $this->getEventXlsxLinks());
        }

        $monitorStatusCells = $this->getMonitorStatusCells();
        $alarmStatusCells = $this->getAlarmStatusCells();

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
                array_key_exists($ucr, $monitorStatusCells) ? $monitorStatusCells[$ucr] : SheetCell::text("")
            ], $this->getAlarmCells($ucr, $alarmStatusCells), $this->getEventCells($ucr));
        }
        return $rows;
    }

    private function getMonitorStatusCells() {
        $statusCells = [];
        foreach ($this->all["data"]["cluster"]["status"] as $id => $status) {
            $statusCells[$id] = SheetCell::text(substr($status["name"], 0, Config::get()->statusLength))
                ->setBg('#' . $status["color_hex"])
                ->setCenter(true);
        }

        $deadline = (new \DateTimeImmutable())->sub(new \DateInterval(Config::get()->statusOutdatedInterval))->getTimestamp();
        $monitorCells = [];
        foreach ($this->all["data"]["monitor"]["3"] as $ucr => $monitor) {
            if (array_key_exists($monitor["status"], $statusCells)) {
                $monitorCells[$ucr] = SheetCell::copy($statusCells[$monitor["status"]])
                    ->setComment("Status zuletzt aktualisiert am " . date("d.m.Y", $monitor["ts"]));
                if ($monitor["ts"] < $deadline) {
                    $monitorCells[$ucr]->setBg(Config::get()->statusOutdatedColor);
                }
            }
        }

        return $monitorCells;
    }

    private function getAlarmStatusCells() {
        $statuses = $this->all["data"]["cluster"]["status"];

        $zone = new \DateTimeZone(Config::get()->timeZone);
        $alarmCells = [];
        foreach ($this->alarms["data"]["items"] as $id => $alarm) {
            $alarmCells[$id] = [];
            foreach ($this->alarms["data"]["items"][$id]["ucr_answered"] as $status_id => $ucrs) {
                if (array_key_exists($status_id, $statuses)) {
                    $status = $statuses[$status_id];
                    $duration = $status["time"] > 0 ? new \DateInterval("PT" . $status["time"] . "M") : null;
                    foreach ($ucrs as $ucr => $item) {
                        $ts = new \DateTime('@' . $item["ts"]);
                        $ts->setTimezone($zone);
                        $alarmCells[$id][$ucr] = SheetCell::text(substr($status["name"], 0, Config::get()->statusLength))
                            ->setBg('#' . $status["color_hex"])
                            ->setComment(trim("{$item["note"]}\r\n\r\nStatus: {$status["name"]}\r\nGesetzt um: " . $ts->format("d.m.Y H:i")))
                            ->setCenter(true);

                        if ($duration !== null) {
                            $ts->add($duration);
                            $alarmCells[$id][$ucr]->setText("⇥ " . $ts->format("H:i"));
                        }
                    }
                }
            }
        }

        return $alarmCells;
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
        $zone = new \DateTimeZone(Config::get()->timeZone);
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

    private function getAlarmBlanks() {
        return array_map(function ($event) {
            return SheetCell::text("");
        }, array_values($this->alarms["data"]["items"]));
    }

    private function getAlarmHeaders() {
        return array_map(function ($alarm) {
            $zone = new \DateTimeZone(Config::get()->timeZone);
            $time = new \DateTime('@' . $alarm["date"]);
            $time->setTimezone($zone);
            return SheetCell::text($time->format("d.m.Y H:i") . "\r\n\r\n" . $alarm["title"])
                ->setCenter(true)->setBg("#ffaaaa")->setWrap(true);
        }, array_values($this->alarms["data"]["items"]));
    }

    private function getAlarmXlsxLinks() {
        return array_map(function ($alarm) {
            return SheetCell::text("💾 Excel")
                ->setUrl("xlsx.php?alarm=" . $alarm["id"]);
        }, array_values($this->alarms["data"]["items"]));
    }

    private function getAlarmCells($ucr, $alarmStatusCells) {
        return array_map(function ($alarm) use ($alarmStatusCells, $ucr) {
            if (array_key_exists($ucr, $alarmStatusCells[$alarm["id"]])) {
                return $alarmStatusCells[$alarm["id"]][$ucr];
            }

            $cell = new SheetCell();
            $cell->setCenter(true);
            if (!in_array($ucr, $alarm["ucr_addressed"])) {
                $cell->setBg(Config::get()->notAddressedBg);
            }

            if (in_array($ucr, $alarm["ucr_read"])) {
                $cell->setText("👀");
            }

            return $cell;
        }, array_values($this->alarms["data"]["items"]));
    }

}
