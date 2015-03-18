<?php

namespace Copilot;

use copi;

/**
 *
 */
class Page {

    const CONTENT_MARKER = "\n===";

    protected static $metaCache  = [];
    protected static $pagesCache = [];

    protected $meta;
    protected $path;
    protected $dir;
    protected $filename;
    protected $basename;
    protected $ext;
    protected $content;
    protected $url;
    protected $absUrl;
    protected $parts;

    protected $depth;
    protected $files;

    /**
     * [fromCache description]
     * @param  [type]
     * @return [type]
     */
    public static function fromCache($path) {

        if (!isset(self::$pagesCache[$path])) {
            self::$pagesCache[$path] = new self($path);
        }

        return self::$pagesCache[$path];
    }

    /**
     * [__construct description]
     * @param [type]
     */
    public function __construct($path) {

        $this->path     = $path;
        $this->ext      = pathinfo($path, \PATHINFO_EXTENSION);
        $this->dir      = dirname($path);
        $this->filename = basename($path);
        $this->basename = basename($path, '.'.$this->ext);
        $this->url      = null;
        $this->absUrl   = copi::pathToUrl($this->dir);
        $this->meta     = null;
        $this->content  = null;
        $this->parts    = null;
        $this->dept     = null;
        $this->files    = []; // files cache

    }

    /**
     * [meta description]
     * @param  [type] $key     [description]
     * @param  [type] $default [description]
     * @return [type]          [description]
     */
    public function meta($key=null, $default=null) {

        if (!$this->meta) {
            $this->meta = $this->_meta();
        }

        if ($key) {
            return $this->meta->get($key, $default);
        }

        return $this->meta;
    }

    /**
     * [set description]
     * @param [type] $key   [description]
     * @param [type] $value [description]
     */
    public function set($key, $value) {

        $this->meta->extend([$key => $value]);

        return $this;
    }

    /**
     * [path description]
     * @return [type] [description]
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
     * [url description]
     * @return [type]
     */
    public function url() {

        if (is_null($this->url)) {

            $this->url = str_replace(copi::$app->path('content:'), '/', $this->path);
            $this->url = copi::$app->routeUrl($this->url);
            $this->url = str_replace($this->filename, ($this->isIndex() ? '' : $this->basename), $this->url);
        }

        return $this->url;
    }

    /**
     * [permalink description]
     * @return [type] [description]
     */
    public function permalink() {
        return copi::$app->getSiteUrl(false).$this->url();
    }

    /**
     * [isIndex description]
     * @return boolean
     */
    public function isIndex() {
        return ($this->basename == 'index');
    }

    /**
     * [isRootIndex description]
     * @return boolean [description]
     */
    public function isRootIndex() {
        return ($this->isIndex() && copi::$app['route'] == '/'); // @TODO make more pretty
    }

    /**
     * [isVisible description]
     * @return boolean
     */
    public function isVisible() {
        return (substr($this->filename, 0, 1) !== '_');
    }

    /**
     * [isHidden description]
     * @return boolean
     */
    public function isHidden() {
        return !$this->isVisible();
    }

    /**
     * [parent description]
     * @return [type]
     */
    public function parent() {

        $page      = null;
        $indexfile = ($this->isIndex() ? dirname($this->dir) : $this->dir).'/index';

        if (file_exists("{$indexfile}.html")) {

            $page = self::fromCache("{$indexfile}.html");

        } elseif(file_exists("{$indexfile}.md")) {

            $page = self::fromCache("{$indexfile}.md");
        }

        return $page;
    }

    /**
     * [children description]
     * @return [type]
     */
    public function children() {

        if ($this->isIndex())  {

            $collection = PageCollection::fromFolder($this->dir)->not($this);

        } else {

            $collection = new PageCollection([]);
        }

        return $collection;
    }

    /**
     * [siblings description]
     * @return [type]
     */
    public function siblings($filter = null) {

        if ($this->isIndex())  {

            if ($this->isRootIndex()) {

                $collection = new PageCollection([]);

            } else {

                $collection = PageCollection::fromFolder(dirname($this->dir))->not($this);
            }

        } else {

            $collection = PageCollection::fromFolder($this->dir)->not($this);

            if (!$this->isIndex()) {
                $collection = $collection->not($this->parent());
            }
        }

        // apply filter
        if ($filter && $collection->count()) {
            $collection = $collection->filter($filter);
        }

        return $collection;
    }

    /**
     * [pages description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function pages($path = '') {

        $dir = false;

        if (strpos($path, ':') !== false) {

            $dir = copi::$app->path($path);

        } else {

            $dir = $this->dir."/".trim($path, '/');

        }

        $pages = copi::pages($dir);

        return $pages;
    }

    /**
     * [page description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function page($path) {

        if (strpos($path, ':') !== false) {

            $path = copi::path($path);

        } else {

            if ($this->isIndex())  {
                $path = dirname($this->dir)."/".trim($path, '/');
            } else {
                $path = $this->dir."/".trim($path, '/');
            }
        }

        return copi::page($path);
    }

    /**
     * [depth description]
     * @return [type]
     */
    public function depth() {

        if (is_null($this->depth)) {
            $this->depth = count(explode('/', str_replace(CP_ROOT_DIR.'/content', '', $this->dir))) - ($this->isIndex() ? 2 : 1);
        }

        return $this->depth;
    }

    /**
     * [data description]
     * @param  [type] $store [description]
     * @return [type]        [description]
     */
    public function data($store) {

        $store = $this->dir."/{$store}.yaml";

        return copi::data($store);
    }

    /**
     * [file description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function file($path) {

        $res  = new Resource($this->_getPath($path));

        return $res;
    }

    /**
     * [files description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function files($path) {

        if (!isset($this->files[$path])) {

            $files = [];

            foreach(copi::$app->helper('fs')->ls($this->_getPath($path)) as $file) {

                if ($file->isFile()) {
                    $files[] = new Resource($file->getRealPath());
                }
            }

            $this->files = new \DataCollection($files);
        }

        return $this->files[$path];
    }

    /**
     * [image description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function image($path) {

        $img = $this->file($path);

        return ($img->exists() && $img->isImage()) ? $img : null;
    }

    /**
     * [images description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function images($path) {

        return $this->files($path)->filter('$item->exists() && $item->isImage()');
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
     * @param  [type] $part [description]
     * @return [type]       [description]
     */
    public function content($part = null) {

        if (is_null($this->content)) {

            $this->content = '';
            $content       = file_get_contents($this->path);
            $content       = copi::view($this->path, ['page' => $this]);

            if ($dividerpos = strpos($content, self::CONTENT_MARKER)) {

                $content = substr($content, strpos($content, self::CONTENT_MARKER) + strlen(self::CONTENT_MARKER));
            }

            if ($this->ext == 'md') {
                $content = copi::$app->helper('markdown')->parse($content);
            }

            // try to fix relative urls
            $this->content = copi::helper('utils')->fixRelativeUrls($content, $this->absUrl.'/');

            copi::$app->trigger('copi.page.content', [$this]);
        }

        return $part ? $this->parts($part) : $this->content;
    }

    /**
     * [setContent description]
     * @param [type] $content [description]
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * [render description]
     * @param  [type] $slots [description]
     * @return [type]        [description]
     */
    public function render($slots = []) {

        $content = $this->content();

        if ($layout = $this->meta('layout', 'default')) {

            $slots['page']         = $this;
            $slots['page_content'] = $content;

            if (strpos($layout, ':') !== false) {

                $layout = copi::$app->path($layout);

            } elseif (!copi::$app->isAbsolutePath($layout)) {

                $layout = "layouts:{$layout}.html";
            }

            $content = copi::view($layout, $slots);
        }

        // try to fix relative urls
        $content = copi::helper('utils')->fixRelativeUrls($content, $this->absUrl.'/');

        copi::trigger('copi.page.render', [&$content]);

        return $content;
    }

    /**
     * [parts description]
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    public function parts($name = null) {

        if (is_null($this->parts)) {

            $content = $this->content();
            $parts   = ['content' => []];
            $current = 'content';

            foreach(explode("\n", $content) as $line) {

                $cline = trim($line);

                // start new part
                if (strpos($cline, '<!-- part:')===0) {
                    $current = trim(str_replace(['<!-- part:', '-->'], '', $cline));
                    $parts[$current] = [];
                    continue;
                }

                $parts[$current][] = $line;
            }

            // glue up lines
            foreach ($parts as $key => &$content) {
                $parts[$key] = implode("\n", $content);
            }

            $this->parts = $parts;
        }

        if ($name) {
            return isset($this->parts[$name]) ? $this->parts[$name] : null;
        }

        return $this->parts;
    }


    /**
     * [_meta description]
     * @return [type] [description]
     */
    protected function _meta(){

        $meta = $this->_collectMeta();
        $code = file_get_contents($this->path);

        if ($dividerpos = strpos($code, self::CONTENT_MARKER)) {
            $code = substr($code, 0, $dividerpos);
        }

        if ($code) {
            $meta += copi::$app->helper('yaml')->fromString($code);
        }

        $meta = new \ContainerArray($meta);

        return $meta;
    }

    /**
     * [_collectMeta description]
     * @return [type]
     */
    protected function _collectMeta() {

        $meta = [];

        $dir  = $this->dir;

        while ($dir != CP_ROOT_DIR) {

            $metafile = "{$dir}/_meta.yaml";

            if (!isset(self::$metaCache[$metafile])) {

                self::$metaCache[$metafile] = file_exists($metafile) ? copi::$app->helper('yaml')->fromFile($metafile) : false;
            }

            if (self::$metaCache[$metafile]) {
                $meta += self::$metaCache[$metafile];
            }

            $dir = dirname($dir);
        }

        return $meta;
    }

    /**
     * [_getPath description]
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    protected function _getPath($path) {

        return (strpos($path, ':') !== false) ? copi::$app->path($path) : $this->dir."/".trim($path, '/');
    }

    /**
     * [__toString description]
     * @return string [description]
     */
    public function __toString() {
        return $this->content();
    }
}
