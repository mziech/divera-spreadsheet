<?php

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
