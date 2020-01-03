<?php
namespace DiveraSpreadSheet;


class SheetCell {
    private $text;
    private $bg;
    private $comment;
    private $center = false;
    private $wrap = false;
    private $url;

    public static function text($text) {
        return (new SheetCell())->setText($text);
    }

    /**
     * @return mixed
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param mixed $text
     * @return SheetCell
     */
    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBg() {
        return $this->bg;
    }

    /**
     * @param mixed $bg
     * @return SheetCell
     */
    public function setBg($bg) {
        $this->bg = $bg;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     * @return SheetCell
     */
    public function setComment($comment) {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCenter() {
        return $this->center;
    }

    /**
     * @param mixed $center
     * @return SheetCell
     */
    public function setCenter($center) {
        $this->center = $center;
        return $this;
    }

    /**
     * @return bool
     */
    public function isWrap() {
        return $this->wrap;
    }

    /**
     * @param bool $wrap
     * @return SheetCell
     */
    public function setWrap($wrap) {
        $this->wrap = $wrap;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param mixed $url
     * @return SheetCell
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

}