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
    private $alarms;
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
    public function getEvents($alarm, $event) {
        if ($alarm !== null) {
            return ["data" => [ "items" => []]];
        }

        if ($event !== null && array_key_exists($event, $this->events["data"]["items"])) {
            return ["data" => [ "items" => [
                $event => $this->events["data"]["items"][$event]
            ]]];
        }

        return $this->events;
    }

    /**
     * @return mixed
     */
    public function getAlarms($alarm, $event) {
        if ($event !== null) {
            return ["data" => [ "items" => []]];
        }

        if ($alarm !== null && array_key_exists($alarm, $this->alarms["data"]["items"])) {
            return ["data" => [ "items" => [
                $alarm => $this->alarms["data"]["items"][$alarm]
            ]]];
        }

        return $this->alarms;
    }

    public function getFilename($alarm, $event) {
        if ($alarm !== null && array_key_exists($alarm, $this->alarms["data"]["items"])) {
            $item = $this->alarms["data"]["items"][$alarm];
            return date('Y-m-d', $item['date']) . ' ' . $item['title'];
        }

        if ($event !== null && array_key_exists($event, $this->events["data"]["items"])) {
            $item = $this->events["data"]["items"][$event];
            return date('Y-m-d', $item['start']) . ' ' . $item['title'];
        }

        return Config::get()->defaultTitle;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    private static function getOrUpdate($filename, \DateInterval $maxAge, callable $loader) {
        $path = self::PATH . "/$filename";
        $deadline = new \DateTime();
        $deadline->sub($maxAge);
        $mtime = @filemtime($path);
        if ($mtime === false || $mtime < $deadline->getTimestamp()) {
            $newData = @$loader();
            if ($newData !== false) {
                file_put_contents($path, $newData);
            }
        }
        return json_decode(file_get_contents($path), true);
    }

    public static function refresh() {
        if (!Authentication::get()->getAdmin()) {
            throw new \RuntimeException("You are not allowed to trigger a refresh!");
        }

        file_put_contents(self::PATH . "/all.json", DiveraApi::get()->getAll());
        file_put_contents(self::PATH . "/events.json", DiveraApi::get()->getEvents());
        file_put_contents(self::PATH . "/alarms.json", DiveraApi::get()->getAlarms());
    }

    private static function load() {
        $data = new Data();
        $data->all = self::getOrUpdate("all.json", new \DateInterval("PT60M"), function () {
            return DiveraApi::get()->getAll();
        });
        $data->events = self::getOrUpdate("events.json", new \DateInterval("PT10M"), function () {
            return DiveraApi::get()->getEvents();
        });
        $data->alarms = self::getOrUpdate("alarms.json", new \DateInterval("PT10M"), function () {
            return DiveraApi::get()->getAlarms();
        });
        $data->timestamp = max(
            filemtime(self::PATH . "/events.json"),
            filemtime(self::PATH . "/alarms.json")
        );
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