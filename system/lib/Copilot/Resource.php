<?php

namespace Copilot;

use copi;

/**
 *
 */
class Resource {

    protected $path;
    protected $dir;
    protected $filename;
    protected $ext;
    protected $exists;

    /**
     * [__construct description]
     * @param [type] $path [description]
     */
    public function __construct($path) {

        $this->path     = $path;
        $this->filename = basename($path);
        $this->dir      = dirname($path);
        $this->ext      = pathinfo($path, \PATHINFO_EXTENSION);
        $this->exists   = file_exists($path);
    }

    /**
     * [path description]
     * @return [type]
     */
    public function path() {
        return $this->path;
    }

    /**
     * [ext description]
     * @return [type]
     */
    public function ext() {
        return $this->ext;
    }

    /**
     * [filename description]
     * @return [type]
     */
    public function filename() {
        return $this->filename;
    }

    /**
     * [dir description]
     * @return [type]
     */
    public function dir() {
        return $this->dir;
    }

    /**
     * [exists description]
     * @return [type] [description]
     */
    public function exists() {
        return $this->exists;
    }

    /**
     * [size description]
     * @param  [type] $format [description]
     * @return [type]         [description]
     */
    public function size($format = null) {

        if (!$this->exists) {
            return 0;
        }

        $size = filesize($size);

        return $format ? copi::$app->helper('utils')->formatSize($size) : $size;
    }

    /**
     * [modified description]
     * @param  [type] $format [description]
     * @return [type]         [description]
     */
    public function modified($format = null) {

        $timestamp = filemtime($this->path);

        return $format ? date($format, $timestamp) : $timestamp;
    }

    /**
     * [content description]
     * @return [type] [description]
     */
    public function content() {

        return $this->exists() ? file_get_contents($this->path) : null;
    }

    public function imageSize() {

        if (!$this->exists || !$this->isImage()) {
            return false;
        }

        return getimagesize($this->path);
    }

    /**
     * [url description]
     * @return [type] [description]
     */
    public function url() {

        if (!$this->exists) {
            return '';
        }

        return copi::$app->pathToUrl($this->path);
    }

    /**
     * [thumb_url description]
     * @return [type] [description]
     */
    public function thumb_url() {

        if (!$this->exists || !$this->isImage()) {
            return '';
        }

        $args = func_get_args();

        array_unshift($args, $this->path);

        return call_user_func_array('thumb_url', $args);
    }

    /**
     * [isImage description]
     * @return boolean [description]
     */
    public function isImage() {
        return preg_match('/\.(jpg|jpeg|gif|png)$/i', $this->path);
    }

    /**
     * [__toString description]
     * @return string [description]
     */
    public function __toString() {
        return $this->content();
    }

}