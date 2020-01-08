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

class Config {

    private static $instance  = null;

    public $casUrl = '';

    public $casServiceUrl = null;

    public $notAddressedBg = '#333333';

    public $responseTypes = [];

    public $users = [];

    public $groups = [];

    public $qualificationRanking = [];

    public $defaultTitle = 'Helferliste';

    public $allAccessKey = '';

    public $eventsAccessKey = '';

    public $timeZone = 'Europe/Berlin';

    private static function load() {
        $json = json_decode(file_get_contents(__DIR__ . '/../data/config.json'), true);
        $config = new Config();
        $vars = array_keys(get_class_vars(Config::class));
        foreach ($json as $k => $v) {
            if (!in_array($k, $vars)) {
                continue;
            }

            $config->$k = $v;
        }
        return $config;
    }

    /**
     * @return Config
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = self::load();
        }
        return self::$instance;
    }

}
