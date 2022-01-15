<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * UACheck.php
 * Author     : metheno
 * Date       : 2017/03/12
 * Version    : constant_selfhost_sj0
 * Description:
 */
class UACheck {

  private static $_userAgent;

  private static function getUserAgent() {
    self::$_userAgent = $_SERVER['HTTP_USER_AGENT'];
    return self::$_userAgent;
  }
  
  public static function is() {
    if (strpos(self::getUserAgent(), 'Safari') && !strpos(self::getUserAgent(), 'Chrome'))
      return 'Safari';
    if (stripos(self::getUserAgent(), 'Mobile'))
      return 'Mobile';
    if (stripos(self::getUserAgent(), 'Macintosh; Intel Mac OS X'))
      return 'Mac OS';
    if (strpos(self::getUserAgent(), 'iPhone OS') && strpos(self::getUserAgent(), 'iPad'))
      return 'iOS';
    if (stripos(self::getUserAgent(), 'Windows NT'))
      return 'Windows';
    if (stripos(self::getUserAgent(), 'MSIE'))
      return 'Internet Explorer';
    if (stripos(self::getUserAgent(), 'Edge'))
      return 'Microsoft Edge';
  }
}