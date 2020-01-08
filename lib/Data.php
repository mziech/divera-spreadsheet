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


class Data {

    private static $instance = null;

    const PATH = __DIR__ . '/../data';
    private $all;
    private $events;
    private $timestamp;

    /**
     * @return mixed
     */
    public function getAll() {
        return $this->all;
    }

    /**
     * @return mixed
     */
    public function getEvents($event) {
        if ($event !== null && array_key_exists($event, $this->events["data"]["items"])) {
            return ["data" => [ "items" => [
                $event => $this->events["data"]["items"][$event]
            ]]];
        }

        return $this->events;
    }

    public function getEventName($event) {
        if (!array_key_exists($event, $this->events["data"]["items"])) {
            return Config::get()->defaultTitle;
        }

        $item = $this->events["data"]["items"][$event];
        return date('Y-m-d', $item['start']) . ' ' . $item['title'];
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    private static function getOrUpdate($filename, \DateInterval $maxAge, callable $loader) {
        $path = self::PATH . "/$filename";
        $deadline = new \DateTime();
        $deadline->sub($maxAge);
        $mtime = filemtime($path);
        if ($mtime === false || $mtime < $deadline->getTimestamp()) {
            $newData = @$loader();
            if ($newData !== false) {
                file_put_contents($path, $newData);
            }
        }
        return json_decode(file_get_contents($path), true);
    }

    private static function load() {
        $data = new Data();
        $data->all = self::getOrUpdate("all.json", new \DateInterval("P1D"), function () {
            return DiveraApi::get()->getAll();
        });
        $data->events = self::getOrUpdate("events.json", new \DateInterval("PT10M"), function () {
            return DiveraApi::get()->getEvents();
        });
        $data->timestamp = filemtime(self::PATH . "/events.json");
        return $data;
    }

    /**
     * @return Data
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = self::load();
        }
        return self::$instance;
    }

}