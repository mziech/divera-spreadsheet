<?php


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