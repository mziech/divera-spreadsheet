<?php


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