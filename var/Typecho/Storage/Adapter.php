<?php

/**
 * Typecho_Storage_Adapter 
 * 
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
interface Typecho_Storage_Adapter
{
    public static function isAvailable();

    public static function check(array $config);

    public static function config(Typecho_Widget_Helper_Form $form);

    public function get($path);

    public function add($localPath);

    public function set($path, $localPath);

    public function delete($path);
}

