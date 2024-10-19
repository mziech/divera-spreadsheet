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

    private $admin;

    /**
     * Authentication constructor.
     * @param $user
     * @param bool $dashboard
     * @param bool $admin
     */
    public function __construct($user, bool $dashboard, bool $admin) {
        $this->user = $user;
        $this->dashboard = $dashboard;
        $this->admin = $admin;
    }

    /**
     * @return mixed
     */
    private static function loadDashboards() {
        if (!file_exists(__DIR__ . "/../data/dashboards.json")) {
            return [];
        }

        return json_decode(file_get_contents(__DIR__ . "/../data/dashboards.json"), true);
    }

    /**
     * @param $dashboards
     */
    private static function saveDashboards($dashboards) {
        file_put_contents(__DIR__ . "/../data/dashboards.json", json_encode($dashboards));
    }

    public static function deleteDashboardToken($token) {
        $authentication = self::get();
        $dashboards = self::loadDashboards();
        if ($dashboards[$token]['user'] === $authentication->getUser()) {
            unset($dashboards[$token]);
            self::saveDashboards($dashboards);
        }
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

    /**
     * @return bool|bool
     */
    public function getAdmin() {
        return $this->admin;
    }

    public static function generateDashboardCookie() {
        $authentication = self::get();
        if (!$authentication->getAdmin()) {
            return false;
        }

        $payload = [
            'user' => $authentication->getUser(),
            'timestamp' => date('c'),
        ];

        $token = '';
        for ($i = 0; $i < 10; $i++) {
            if ($i > 0) {
                $token .= '-';
            }
            $token .= strtoupper(bin2hex(random_bytes(2)));
        }
        $dashboards = self::loadDashboards();
        $dashboards[$token] = $payload;
        self::saveDashboards($dashboards);
        return $token;
    }

    public static function getUserTokens() {
        $authentication = self::get();
        if ($authentication->getUser() === null) {
            return [];
        }

        return array_filter(self::loadDashboards(), function ($it) use ($authentication) {
            return array_key_exists('user', $it) && $it['user'] === $authentication->getUser();
        });
    }

    public static function setDashboardCookie($token) {
        setcookie(self::DASHBOARD_COOKIE, $token, time()+60*60*24*365*10, "", "", isset($_SERVER['HTTPS']), true);
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

        $dashboards = self::loadDashboards();
        if (!array_key_exists($token, $dashboards)) {
            return false;
        }

        return true;
    }

    private static function load() {
        global $_SERVER;

        if (self::isDashboard()) {
            return new Authentication(null, true, false);
        }

        phpCAS::setLogger(Logger::get('CAS'));
        // phpCAS::setVerbose(true);

        $url = parse_url(Config::get()->casUrl);
        $casServiceUrl = Config::get()->casServiceUrl;
        if ($casServiceUrl !== null) {
            //$casServiceUrlParsed = parse_url($casServiceUrl);
            //$casServiceBaseUrl = $casServiceUrlParsed['scheme'] . '://' . $casServiceUrlParsed['host'] . ($casServiceUrlParsed['port'] !== null ? ':' . $casServiceUrlParsed['port'] : '');
            $casServiceBaseUrl = $casServiceUrl;
        } else if ($_SERVER['HTTPS'] ?? false) {
            $casServiceBaseUrl = 'https://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == 443 ? ':' . $_SERVER['SERVER_PORT'] : '');
        } else {
            $casServiceBaseUrl = 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == 80 ? ':' . $_SERVER['SERVER_PORT'] : '');
        }
        Logger::get(__CLASS__)->debug("CAS service base URL is: $casServiceBaseUrl");
        phpCAS::client(CAS_VERSION_3_0, $url['host'], $url['port'] !== null ? $url['port'] : 443, $url['path'], $casServiceBaseUrl);
        if ($url['scheme'] === 'http') {
            phpCAS::setNoCasServerValidation();
            phpCAS::setServerLoginURL(Config::get()->casUrl . "/login?service=" . phpCAS::getServiceURL());
            phpCAS::setServerServiceValidateURL(Config::get()->casUrl . "/p3/serviceValidate");
        } else {
            phpCAS::setCasServerCACert("/etc/ssl/certs/ca-certificates.crt");
        }
        if ($casServiceUrl !== null) {
            phpCAS::setFixedServiceURL(Config::get()->casServiceUrl);
        }
        phpCAS::forceAuthentication();
        $memberOf = phpCAS::hasAttribute('memberOf') ? phpCAS::getAttribute('memberOf') : [];
        if (!is_array($memberOf)) {
            $memberOf = [$memberOf];
        }
        $admin = empty(Config::get()->casAdminGroups)
            ? true
            : !empty(array_intersect($memberOf, Config::get()->casAdminGroups));
        return new Authentication(phpCAS::getUser(), false, $admin);
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