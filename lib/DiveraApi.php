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


class DiveraApi {
    private static $instance;

    private $baseUrl = 'https://www.divera247.com/api/v2';

    public function getAll() {
        $key = urlencode(Config::get()->allAccessKey);
        return file_get_contents("{$this->baseUrl}/pull/all?accesskey=$key");
    }

    public function getEvents() {
        $key = urlencode(Config::get()->eventsAccessKey);
        return file_get_contents("{$this->baseUrl}/events?accesskey=$key");
    }

    public function getAlarms() {
        $key = urlencode(Config::get()->eventsAccessKey);
        return file_get_contents("{$this->baseUrl}/alarms?accesskey=$key");
    }

    /**
     * @return DiveraApi
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = new DiveraApi();
        }
        return self::$instance;
    }


}