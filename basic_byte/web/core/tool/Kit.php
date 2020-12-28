<?php

declare(strict_types = 1);

namespace TortuLive;

class Kit {

  const BYTE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const BYTE_PRECISION = [4, 4, 4, 4, 4, 4, 4, 4, 4];
  const BYTE_NEXT = 1024;

  // https://gist.github.com/liunian/9338301
  public static function bytes_size_human($bytes = 0, $precision = NULL) {
    $bytes = (int)$bytes;
    for ($i = 0; ($bytes / self::BYTE_NEXT) >= 0.9 && $i < count(self::BYTE_UNITS); $i++) {
      $bytes /= self::BYTE_NEXT;
    }
    return round($bytes, is_null($precision) ? self::BYTE_PRECISION[$i] : (int)$precision) . self::BYTE_UNITS[$i];
  }

  // https://stackoverflow.com/questions/65034205/detect-mobile-device-using-http-user-agent-with-php
  public static function device() {

    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
      return 'tablet';
    }

    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
      return 'mobile';
    }

    if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) || ((isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])))) {
      return 'mobile';
    }

    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
    $mobile_agents = [
      'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
      'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
      'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
      'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
      'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap',
      'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-',
      'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh',
      'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr',
      'webc', 'winw', 'winw', 'xda ', 'xda-'
    ];

    if (in_array($mobile_ua, $mobile_agents)) {
      return 'mobile';
    }

    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'opera mini') > 0) {
        $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : (isset($_SERVER['HTTP_DEVICE_STOCK_UA']) ? $_SERVER['HTTP_DEVICE_STOCK_UA'] : ''));
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
          return 'tablet';
        }
        return 'mobile';
    }

    return 'desktop';

  }

}