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


use phpCAS;

class Authentication {

    private static $instance = null;

    const DASHBOARD_COOKIE = "divera-spreadsheet-dashboard";

    private $user;

    private $dashboard;

    /**
     * Authentication constructor.
     * @param $user
     * @param bool $dashboard
     */
    public function __construct($user, bool $dashboard) {
        $this->user = $user;
        $this->dashboard = $dashboard;
    }

    /**
     * @return mixed
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @return bool|bool
     */
    public function getDashboard() {
        return $this->dashboard;
    }

    public static function generateDashboardCookie() {
        $authentication = self::get();
        if ($authentication->dashboard !== false) {
            return false;
        }

        $payload = [
            'user' => $authentication->user,
            'timestamp' => date('c'),
        ];

        $token = '';
        for ($i = 0; $i < 10; $i++) {
            if ($i > 0) {
                $token .= '-';
            }
            $token .= strtoupper(bin2hex(random_bytes(2)));
        }
        $dashboards = json_decode(file_get_contents(__DIR__ . "/../data/dashboards.json"), true);
        $dashboards[$token] = $payload;
        file_put_contents(__DIR__ . "/../data/dashboards.json", json_encode($dashboards));
        return $token;
    }

    public static function setDashboardCookie($token) {
        setcookie(self::DASHBOARD_COOKIE, $token, -1);
    }

    public static function getDashboardCookie() {
        if (!array_key_exists(self::DASHBOARD_COOKIE, $_COOKIE)) {
            return false;
        }

        return $_COOKIE[self::DASHBOARD_COOKIE];
    }

    private static function isDashboard() {
        $token = self::getDashboardCookie();
        if ($token === false) {
            return false;
        }

        $dashboards = json_decode(file_get_contents(__DIR__ . "/../data/dashboards.json"));
        if (!array_key_exists($token, $dashboards)) {
            return false;
        }

        return true;
    }

    private static function load() {
        if (self::isDashboard()) {
            return new Authentication(null, true);
        }

        phpCAS::setDebug();
        phpCAS::setVerbose(true);
        $url = parse_url(Config::get()->casUrl);
        phpCAS::client(CAS_VERSION_3_0, $url['host'], $url['port'] !== null ? $url['port'] : 443, $url['path']);
        if ($url['scheme'] === 'http') {
            phpCAS::setNoCasServerValidation();
            phpCAS::setServerLoginURL(Config::get()->casUrl . "/login?service=" . phpCAS::getServiceURL());
            phpCAS::setServerServiceValidateURL(Config::get()->casUrl . "/p3/serviceValidate");
        } else {
            phpCAS::setCasServerCACert("/etc/ssl/certs/ca-certificates.crt");
        }
        if (Config::get()->casServiceUrl !== null) {
            phpCAS::setFixedServiceURL(Config::get()->casServiceUrl);
        }
        phpCAS::forceAuthentication();
        return new Authentication(phpCAS::getUser(), false);
    }

    /**
     * @return Authentication
     */
    public static function get() {
        if (self::$instance === null) {
            self::$instance = self::load();
        }
        return self::$instance;
    }

}