<?php

declare(strict_types = 1);

namespace TortuLive;

class Kit {

  const BYTE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const BYTE_PRECISION = [4, 4, 4, 4, 4, 4, 4, 4, 4];
  const BYTE_NEXT = 1024;

  private static $num_strings = 0;
  private static $strings_replaces = [];

  private static $all = [];
  private static $parts = [];

  private static $tags_pre = [];
  private static $open_pre = [];
  private static $close_pre = [];

  private static $tags_code = [];
  private static $open_code = [];
  private static $close_code = [];

  private static $list_script = [];
  private static $list_style = [];

  private static $patterns = ['#<script.*>.*</script>#isuUS', '#<style.*>.*</style>#isuUS', '#<textarea.*>.*</textarea>#isuUS'];
  private static $pattern_nested = ['#<pre.*>#isuUS', '#<code.*>#isuUS'];

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

  public static function min_css($css) {

    $result = preg_replace ('#\/\*[\s\S]*?\*\/#', '', $css);
    $result = str_replace(array("\r\n", "\r"), "\n", $result);
    $result = str_replace("\n", '', $result);
    $result = preg_replace('#\s{2,}#', ' ', $result);
    $result = preg_replace('#\s*\{\s*#Sm', '{', $result);
    $result = preg_replace('#\s*\}\s*#Sm', '}', $result);
    $result = preg_replace('#\s*\(\s*#Sm', '(', $result);
    $result = preg_replace('#\s*\)\s*#Sm', ')', $result);
    $result = preg_replace('#;\}#Sm', '}', $result);
    $result = preg_replace('#\s*\:\s*#Sm', ':', $result);
    $result = preg_replace('#\s*>\s*#Sm', '>', $result);
    $result = preg_replace('#\;\s*#Sm', ';', $result);

    return $result;

  }

  public static function min_js($js) {

    // guarda las cadenas
    $result = self::search_string($js);
    // comentarios de una línea
    $result = preg_replace ('#^[\t ]*?\/\/.*\s?#mS', '', $result);
    $result = preg_replace ('#([\s;})]+)\/\/.*#mS', '\\1', $result);
    // comentarios de varias líneas
    $result = preg_replace ('#\/\*[\s\S]*?\*\/#S', '', $result);

    $procesing = FALSE;

    while ($procesing) {
      $comment = [];
      // buscar comentarios
      preg_match('#\/\*.*?\*\/#Ss', $result, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) > 0) {
        $comment[$matches[0][1]] = [
          'length' => strlen($matches[0][0])
        ];
      }
      preg_match('#(?<=;)\s*\/\/[^\n]+#Sm', $result, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) > 0) {
        $comment[$matches[0][1]] = [
          'length' => strlen($matches[0][0])
        ];
      }
      ksort($comment);

      if (count($comment) > 0) {
        // lo primero se quita y vuelta...
        $search = mb_substr($result, array_keys($comment)[0], $comment[array_keys($comment)[0]]['length']);
        $result = str_replace($search, '', $result);
      } else {
        $procesing = FALSE;
      }

    }

    // saltos de líneas
    $result = str_replace(["\r\n", "\r"], "\n", $result);
    while(preg_match('#\s*\n+\s*#Sm', $result)) {
      $result = preg_replace('#\s*\n+\s*#Sm',  ' ', $result);
    }
    // espacios antes y después de...
    // 1
    $result = preg_replace('#\s*;\s*#Sm', ';', $result);
    $result = preg_replace('#\s*,\s*#Sm', ',', $result);
    $result = preg_replace('#\s*:\s*#Sm', ':', $result);
    $result = preg_replace('#\s*\?\s*#Sm', '?', $result);
    $result = preg_replace('#\s*\[\s*#Sm', '[', $result);
    $result = preg_replace('#\s*\]\s*#Sm', ']', $result);
    $result = preg_replace('#\s*\(\s*#Sm', '(', $result);
    $result = preg_replace('#\s*\)\s*#Sm', ')', $result);
    $result = preg_replace('#\s*\{\s*#Sm', '{', $result);
    $result = preg_replace('#\s*\}\s*#Sm', '}', $result);
    $result = preg_replace('#\s*=\s*#Sm', '=', $result);
    $result = preg_replace('#\s*<\s*#Sm', '<', $result);
    $result = preg_replace('#\s*>\s*#Sm', '>', $result);
    $result = preg_replace('#\s*\+\s*#Sm', '+', $result);
    $result = preg_replace('#\s*\-\s*#Sm', '-', $result);
    // 2
    $result = preg_replace('#\s*\(\)\s*#Sm', '()', $result);
    $result = preg_replace('#\s*==\s*#Sm', '==', $result);
    $result = preg_replace('#\s*!=\s*#Sm', '!=', $result);
    $result = preg_replace('#\s*>=\s*#Sm', '!=', $result);
    $result = preg_replace('#\s*<=\s*#Sm', '!=', $result);
    $result = preg_replace('#\s*&&\s*#Sm', '&&', $result);
    $result = preg_replace('#\s*\|\|\s*#Sm', '||', $result);
    $result = preg_replace('#\s*=>\s*#Sm', '!=', $result);
    // 3
    $result = preg_replace('#\s*===\s*#Sm', '===', $result);
    $result = preg_replace('#\s*!==\s*#Sm', '!==', $result);
    // extra
    $result = preg_replace('#\s*true\s*#Sm', '!0', $result);
    $result = preg_replace('#\s*false\s*#Sm', '!1', $result);
    // espacios
    $result = preg_replace('#\s+#Sm', ' ', $result);
    //repone cadenas
    $result = self::restore_strings($result);

    return $result;

  }

  private static function search_string($text) {

    return preg_replace_callback(
      // lo que empiece por ' " ` y acabe por lo mismo
      // que no tenga delante una \
      "#(['\"`])(.*?(?<!\\\\)(\\\\\\\\)*+)\\1#",
      ['self', 'replace_strings'],
      $text
    );

  }

  private static function replace_strings($matches) {
    // $matches[1] = la ' las " o la `
    // $matches[2] la cadena que queremos guardar
    $index = $matches[1] . '(' . self::$num_strings++ . ')' .  $matches[1];
    self::$strings_replaces[$index] = $matches[2];

    return $matches[1] . $index . $matches[1];

  }

  private static function restore_strings($result) {

    foreach (self::$strings_replaces as $key => $value) {
      $result = str_replace($key, $value, $result);
    }

    return $result;

  }

  private static function array_sort($array, $on) {

    $new_array = [];
    $sortable_array = [];

    if (count($array) > 0) {
      foreach ($array as $k => $v) {
        if (is_array($v)) {
          foreach ($v as $k2 => $v2) {
            if ($k2 == $on) {
              $sortable_array[$k] = $v2;
            }
          }
        } else {
          $sortable_array[$k] = $v;
        }
      }
      asort($sortable_array);
      foreach ($sortable_array as $k => $v) {
        $new_array[] = $array[$k];
      }
    }

    return $new_array;

  }

  private static function process($pattern, $content) {

    $result = preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

    if ($result !== FALSE) {
      foreach ($matches[0] as $i => $part) {
        self::$all[$part[1]] = $part[0];
        if ($pattern == '#<pre.*>#isuUS') {
          self::$open_pre[] = $part[1];
          self::$tags_pre[] = $part[1];
        }
        if ($pattern == '#<code.*>#isuUS') {
          self::$open_code[] = $part[1];
          self::$tags_code[] = $part[1];
        }
      }
    }

    return $result;

  }

  private static function get_next($list, $last) {

    $result = FALSE;

    foreach ($list as $part) {
      if ($part['start'] >= $last) {
        return $part;
      }
    }

    return $result;

  }

  private static function pair_all_pre() {

    $pair = [];
    sort(self::$tags_pre, SORT_NUMERIC);
    $count = count(self::$open_pre);
    $done = 0;
    $pos = 0;

    do {
      $pos = 0;
      foreach(self::$tags_pre as $i => $tag) {
        if (in_array($tag, self::$open_pre)) {
          $pos = $i;
        } else {
          $pair[] = [
            'start' => self::$tags_pre[$pos],
            'end' => self::$tags_pre[$i]
          ];
          $done++;
          unset(self::$tags_pre[$pos]);
          unset(self::$tags_pre[$i]);
          break;
        }
      }
    } while($count > $done);

    $pair = self::array_sort($pair, 'start');

    return $pair;

  }

  private static function pair_all_code() {

    $pair = [];
    $done = 0;
    $pos = 0;
    sort(self::$tags_code, SORT_NUMERIC);
    $count = count(self::$open_code);

    do {
      $pos = 0;
      foreach(self::$tags_code as $i => $tag) {
        if (in_array($tag, self::$open_code)) {
          $pos = $i;
        } else {
          $pair[] = [
            'start' => self::$tags_code[$pos],
            'end' => self::$tags_code[$i]
          ];
          $done++;
          unset(self::$tags_code[$pos]);
          unset(self::$tags_code[$i]);
          break;
        }
      }
    } while($count > $done);

    $pair = self::array_sort($pair, 'start');

    return $pair;

  }

  private static function strpos_recursive($haystack, $needle, $offset = 0, &$results = []) {

    $offset = strpos($haystack, $needle, $offset);
    if ($offset === false) {
      return $results;
    } else {
      $results[] = $offset;
      return self::strpos_recursive($haystack, $needle, ($offset + 1), $results);
    }

  }

  public static function min_html($html) {

    $content_html = '';
    $list_html = [];
    $cursor_html = 0;

    foreach (self::$patterns as $pattern) {
      $result = self::process($pattern, $html);
      if ($result === FALSE) {
        break;
      }
    }
    foreach (self::$pattern_nested as $pattern) {
      self::process($pattern, $html);
    }

    if (count(self::$all) == 0) {
      self::$all[0] = $html;
      self::$parts[0] = $html;
    }

    self::$close_pre = self::strpos_recursive($html, '</pre>');
    self::$close_code = self::strpos_recursive($html, '</code>');

    self::$tags_pre = array_merge(self::$tags_pre, self::$close_pre);
    self::$tags_code = array_merge(self::$tags_code, self::$close_code);

    // reparar anidados
    ksort(self::$all);

    foreach (self::$all as $pos => $part) {
      foreach (self::$all as $pos_c => $part_c) {
        if ($pos_c > $pos && $pos_c < ($pos + strlen($part))) {
          //esta contenido
          $content = self::$all[$pos];
          $before = substr(self::$all[$pos], 0, $pos_c - $pos);
          $after = substr($content, $pos_c - $pos + strlen($part_c));
          self::$all[$pos] = $before;
          self::$all[$pos_c + strlen($part_c)] = $after;
        }
      }
    }

    ksort(self::$all);

    $pair_pre = [];
    if (count(self::$open_pre) == count(self::$close_pre)) {
      // emparejar si hay anidados del mismo
      $pair_pre = self::pair_all_pre();
    }
    $pair_code = [];
    if (count(self::$open_code) == count(self::$close_code)) {
      // emparejar si hay anidados del mismo
      $pair_code = self::pair_all_code();
    }

    // dejar en self::$parts el que contenga a los demás
    // dejaría <pre><code></code></pre>
    // en vez de un <pre> y un <code>
    if (count($pair_pre) > 0 && count($pair_code) > 0) {

      $done = FALSE;
      $last_end = 0;

      do {
        $pre = self::get_next($pair_pre, $last_end);
        $code = self::get_next($pair_code, $last_end);

        if ($pre !== FALSE && $code !== FALSE) {
          if ($pre['start'] < $code['start']) {
            self::$parts[$pre['start']] = substr($html, $pre['start'],  ($pre['end'] - $pre['start']) + 6);
            $last_end = $pre['end'] + 6;
          } else {
            self::$parts[$code['start']] = substr($html, $code['start'],  ($code['end'] - $code['start']) + 7);
            $last_end = $code['end'] + 7;
          }
        } else if ($pre !== FALSE) {
          self::$parts[$pre['start']] = substr($html, $pre['start'],  ($pre['end'] - $pre['start']) + 6);
          $last_end = $pre['end'] + 6;
        } else if ($code !== FALSE) {
          self::$parts[$code['start']] = substr($html, $code['start'],  ($code['end'] - $code['start']) + 7);
          $last_end = $code['end'] + 7;
        } else {
          $done = TRUE;
        }

      } while (!$done);

    } else if (count($pair_pre) > 0) {

      $x = 0;
      foreach ($pair_pre as $part) {
        $jump = FALSE;
        if ($x > 0) {
          for ($y = $x; $y > 0; $y--) {
            if ($part['start'] > $pair_pre[($x - 1)]['start'] && $part['end'] < $pair_pre[($x - 1)]['end']) {
              $jump = TRUE;
            }
          }
        }
        $x++;
        if ($jump) {
          continue;
        }
        self::$parts[$part['start']] = substr($html, $part['start'],  ($part['end'] - $part['start'])+6);
      }

    } else if (count($pair_code) > 0) {

      $x = 0;
      foreach ($pair_code as $i => $part) {
        $jump = FALSE;
        if ($x > 0) {
          for ($y = $x; $y > 0; $y--) {
            if ($part['start'] > $pair_code[($x - 1)]['start'] && $part['end'] < $pair_code[($x - 1)]['end']) {
              $jump = TRUE;
            }
          }
        }
        $x++;
        if ($jump) {
          continue;
        }
        self::$parts[$part['start']] = substr($html, $part['start'],  ($part['end'] - $part['start']) + 7);
      }

    }

    foreach (self::$all as $start => $value) {

      if (substr($value, 0, 7) == '<script') {
        self::$parts[$start] = $value;
        self::$list_script[$start] = [
          'start_script' => strpos($value, '>') + 1,
          'end_script' => strpos($value, '</script>')
        ];
      } else if (substr($value, 0, 6) == '<style') {
        self::$parts[$start] = $value;
        self::$list_style[$start] = [
          'start_style' => strpos($value, '>') + 1,
          'end_style' => strpos($value, '</style>')
        ];
      } else if (substr($value, 0, 9) == '<textarea') {
        self::$parts[$start] = $value;
      }

    }

    ksort(self::$parts);

    // dejar en $list_html todas las partes
    foreach (self::$parts as $start => $value) {
      if ($cursor_html != $start) {
        $list_html[$cursor_html] = substr($html, $cursor_html, ($start-$cursor_html));
        $cursor_html += ($start-$cursor_html);
        $list_html[$cursor_html] = substr($html, $cursor_html, ($start-$cursor_html) + strlen($value));
        $cursor_html += ($start-$cursor_html) + strlen($value);
      } else {
        $list_html[$cursor_html] = substr($html, $cursor_html, strlen($value));
        $cursor_html += strlen($value);
      }
    }

    // procesar las partes
    foreach ($list_html as $start => $html) {

      if (isset(self::$list_script[$start]) && self::$list_script[$start]['start_script'] == self::$list_script[$start]['end_script']) {
        $content_html .= $html;

      } else if (isset(self::$list_script[$start]) && self::$list_script[$start]['start_script'] != self::$list_script[$start]['end_script']) {
        $html_start = substr($html, 0, self::$list_script[$start]['start_script']);
        $html_content = self::min_js(substr($html, self::$list_script[$start]['start_script'], -9)).substr($html, -9);
        $content_html .= $html_start . $html_content;

      } else if (isset(self::$list_style[$start]) && self::$list_style[$start]['start_style'] == self::$list_style[$start]['end_style']) {
        $content_html .= $html;

      } else if (isset(self::$list_style[$start]) && self::$list_style[$start]['start_style'] != self::$list_style[$start]['end_style']) {
        $html_start = substr($html, 0, self::$list_style[$start]['start_style']);
        $html_content = self::min_css(substr($html, self::$list_style[$start]['start_style'], -8)).substr($html, -8);
        $content_html .= $html_start . $html_content;

     } else {
        // etiquetas pre, code y textarea no hacer nada...
        if (substr($html, 0, 4) != '<pre' && substr($html, 0, 5) != '<code' && substr($html, 0, 9) != '<textarea') {
          $html = preg_replace('#\s+#Sm', ' ', $html);
          $html = preg_replace('#\s+/>#Sm', '/>', $html);
        }
        $content_html .= $html;
      }

    }

    return $content_html;

  }

}