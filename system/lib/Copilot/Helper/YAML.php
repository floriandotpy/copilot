<?php

namespace Copilot\Helper;

/**
 *
 */
class YAML extends \Lime\Helper {


    protected $cachePath = false;

    /**
     * [setCachePath description]
     * @param [type] $path [description]
     */
    public function setCachePath($path){
        $this->cachePath = is_string($path) ? rtrim($path, "/\\") : $path;
    }

    /**
     * [fromString description]
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function fromString($string) {

        return \Spyc::YAMLLoadString($string);
    }

    /**
     * [fromFile description]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function fromFile($file) {

        if ($this->cachePath) {

            $cachedfile = $this->get_cached_file($file);

            if ($cachedfile) {

                return include($cachedfile);
            }
        }

        return \Spyc::YAMLLoad($file);
    }

    /**
     * [get_cached_file description]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    protected function get_cached_file($file) {

        $cachedfile = $this->cachePath.'/'.basename($file).'.'.md5($file).'.php';

        if (!file_exists($cachedfile)) {
            $cachedfile = $this->cache_file($file, $cachedfile, null);
        }

        if ($cachedfile) {

            $mtime = filemtime($file);

            if(filemtime($cachedfile)!=$mtime) {
                $cachedfile = $this->cache_file($file, $cachedfile, $mtime);
            }

            return $cachedfile;
        }

        return false;
    }

    /**
     * [cache_file description]
     * @param  [type] $file       [description]
     * @param  [type] $cachedfile [description]
     * @param  [type] $filemtime  [description]
     * @return [type]             [description]
     */
    protected function cache_file($file, $cachedfile, $filemtime = null) {

        if (!$filemtime){
            $filemtime = filemtime($file);
        }

        $data = var_export(\Spyc::YAMLLoad($file), true);

        if (file_put_contents($cachedfile, "<?php return {$data};")) {
            touch($cachedfile,  $filemtime);
            return $cachedfile;
        }

        return false;
    }

}
