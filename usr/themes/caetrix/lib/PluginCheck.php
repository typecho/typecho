<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * PluginCheck.php
 * Author     : metheno
 * Date       : 2017/03/12
 * Version    :
 * Description:
 */
 
class PluginCheck {

  private static function getPluginList() {
    $plugins = Typecho_Plugin::export();
    $plugins = $plugins['activated'];
    return $plugins;
  }

  public static function tePostViewsExists() {
    if (is_array(self::getPluginList()) && array_key_exists('TePostViews', self::getPluginList())) {
      return true;
    }
  }
}
