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

    public static function copy(SheetCell $other) {
        $cell = new SheetCell();
        $cell->text = $other->text;
        $cell->bg = $other->bg;
        $cell->comment = $other->comment;
        $cell->center = $other->center;
        $cell->wrap = $other->wrap;
        $cell->url = $other->url;
        return $cell;
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