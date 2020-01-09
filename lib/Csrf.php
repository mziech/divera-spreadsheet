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


class Csrf {

    private static $instance = null;

    public function getToken() {
        session_start();
        if (!array_key_exists('csrf', $_SESSION)) {
            $_SESSION['csrf'] = bin2hex(random_bytes(20));
        }
        return $_SESSION['csrf'];
    }

    public function check() {
        $token = $this->getToken();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this;
        }

        if ($token !== $_REQUEST['_csrf']) {
            throw new \RuntimeException('CSRF check failed!');
        }

        return $this;
    }

    public function input() {
        echo '<input type="hidden" name="_csrf" value="' . htmlspecialchars($this->getToken()) . '">';
    }

    /**
     * @return Csrf
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = new Csrf();
        }
        return self::$instance;
    }

}