<?php
namespace DiveraSpreadSheet;


class SheetBuilder {

    private $all;
    private $events;
    private $xlsxLinks;

    /**
     * SheetBuilder constructor.
     * @param $all
     * @param $events
     * @param $xlsxLinks
     */
    public function __construct($all, $events, $xlsxLinks) {
        $this->all = $all;
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
            SheetCell::text("Name")->setUrl($this->xlsxLinks ? "xlsx.php" : null),
            SheetCell::text("Gruppe"),
        ], $this->getEventHeaders());

        foreach ($this->getSortedUcrs() as $ucr) {
            $user = $this->all["data"]["cluster"]["consumer"][$ucr];
            $nameCell = SheetCell::text($user["stdformat_name"]);
            $groupCell = $this->getGroupCell($user["groups"]);
            if ($lastBg != $groupCell->getBg()) {
                $nameCell->setBg($groupCell->getBg());
                $lastBg = $groupCell->getBg();
            }

            $rows[] = array_merge([
                $nameCell,
                $groupCell,
            ], $this->getEventCells($ucr));
        }
        return $rows;
    }

    private function getEventHeaders() {
        return array_map(function ($event) {
            $cell = SheetCell::text($this->getEventTime($event) . "\r\n\r\n" . $event["title"])
                ->setCenter(true)->setWrap(true);
            if ($this->xlsxLinks) {
                $cell->setUrl("xlsx.php?event=" . $event["id"]);
            }
            return $cell;
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
                return "$startDay $startTime - $endTime";
            } else {
                return "$startDay $startTime";
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
