<?php

declare(strict_types = 1);

namespace TortuLive;

class View {

  private static $_frame = '';
  private static $_page_action = '';
  private static $_contents = [];
  private static $_head_meta = [];
  private static $_response = '';

  public static function generate($status) {

    // el recurso no ha sido encontrado
    if ($status == 404) {
      self::e404();
    }
    // cuando el supervisor usa echo, var_dump...
    try {
      ob_start();
        $reflection_method = new \ReflectionMethod(Byte::$obj_supervisor, Byte::$action);
        $reflection_method->invokeArgs(Byte::$obj_supervisor, Byte::$parameters);
    } catch (\ReflectionException $e) {
      if (ERROR_REPORTING) {
        echo '<pre id="ReflectionException" style="display: none">' . $e . '</pre>';
      }
    } finally {
        $content = ob_get_contents();
      ob_end_clean();

      View::add_content($content);
    }
    // hacer las variables del supervisor accesibles a la página
    extract(get_object_vars(Byte::$obj_supervisor), EXTR_OVERWRITE);

    $page_action = PATH_PAGE . Byte::$supervisor . DIRECTORY_SEPARATOR .  View::page_action() . '.phtml';
    // añadir la página
    if (!is_file($page_action) && ERROR_REPORTING) {
      echo '<pre id="page_action" style="display: none">' . $page_action . '</pre>';
    } else {
      ob_start();
        include $page_action;
        $content = ob_get_contents();
      ob_end_clean();
      self::add_content($content);
    }

    $frame = PATH_FRAME .  DIRECTORY_SEPARATOR . View::frame() . '.phtml';
    // añadir el marco
    if (!is_file($frame) && ERROR_REPORTING) {
      echo '<pre id="frame" style="display: none">' . $frame . '</pre>';
    } else {
      ob_start();
        include $frame;
        $content = ob_get_contents();
      ob_end_clean();
      self::add_response($content);
    }

    self::response();

  }

  public static function response() {

    if (!CACHE) {
      header("Expires: on, 01 Jan 1970 00:00:00 GMT");
      header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
      header("Cache-Control: max-age=0, no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
    }

    $all_content = ob_get_contents();
    ob_end_clean();
    $time_end = microtime(true);

    $trace = '';
    if (TRACE) {
      ob_start();
        echo '<pre id="trace" style="display: none">';
        var_dump($_SESSION);
        echo 'memory_limit: ' . ini_get('memory_limit'). PHP_EOL;
        echo 'memory_get_peak_usage: ' .  Kit::bytes_size_human(memory_get_peak_usage(TRUE)). PHP_EOL;
        echo 'max_execution_time: ' . ini_get('max_execution_time') . 's' . PHP_EOL;
        echo 'max_input_time: ' . ini_get('max_input_time') . 's' . PHP_EOL;
        $execution_time = round($time_end - TIME_START, 4);
        echo "execution_time: {$execution_time}s" . PHP_EOL;
        echo '</pre>';
        $trace = ob_get_contents();
      ob_end_clean();
    }
    // generar siempre una respuesta distinta
    // para evitar usar la cache del navegador
    $cache = '';
    if (!CACHE) {
      ob_start();
        echo "<!--{$time_end}-->";
        $cache = ob_get_contents();
      ob_end_clean();
    }

    echo $all_content . self::$_response . $trace . $cache;
    exit();

  }

  public static function add_response($content) {
    if (mb_strlen(trim($content)) > 0) {
      self::$_response .= trim($content);
    }
  }

  public static function e404() {
    header("HTTP/1.1 404 Not Found");
    header("Status: 404 Not Found");
    Byte::$supervisor = 'e404';
    Byte::$action = 'default';
    Byte::$parameters = [];
    self::set_frame('default');
    self::set_page_action('default');
    $class = 'TortuLive\\' . Byte::$supervisor;
    Byte::$obj_supervisor = new $class();
  }

  public static function add_head_meta($content) {
    if (mb_strlen(trim($content)) > 0) {
      self::$_head_meta[] = trim($content);
    }
  }

  public static function head_meta() {

    $charset = Byte::$obj_supervisor->_charset;
    $title = htmlspecialchars(Byte::$obj_supervisor->_title, ENT_QUOTES, "UTF-8");
    $description = htmlspecialchars(Byte::$obj_supervisor->_description, ENT_QUOTES, "UTF-8");
    $keywords = htmlspecialchars(Byte::$obj_supervisor->_keywords, ENT_QUOTES, "UTF-8");
    $author = htmlspecialchars(Byte::$obj_supervisor->_author, ENT_QUOTES, "UTF-8");
    $generator = htmlspecialchars(Byte::$obj_supervisor->_generator, ENT_QUOTES, "UTF-8");

    if (mb_strlen(trim($charset)) > 0) {
      self::$_head_meta[] = <<<TXT
      <meta charset="$charset" />
TXT;
    }
    if (mb_strlen(trim($title)) > 0) {
      self::$_head_meta[] = <<<TXT
      <title>$title</title>
TXT;
    }
    if (mb_strlen(trim($description)) > 0) {
      self::$_head_meta[] = <<<TXT
      <meta content="$description" name="description" />
TXT;
    }
    if (mb_strlen(trim($keywords)) > 0) {
      self::$_head_meta[] = <<<TXT
      <meta content="$keywords" name="keywords" />
TXT;
    }
    if (mb_strlen(trim($author)) > 0) {
      self::$_head_meta[] = <<<TXT
      <meta content="$author" name="author" />
TXT;
    }
    if (mb_strlen(trim($generator)) > 0) {
      self::$_head_meta[] =  <<<TXT
      <meta content="$generator" name="generator" />
TXT;
    }

    if (count(self::$_head_meta) > 0) {
      foreach(self::$_head_meta as $meta) {
        echo $meta . PHP_EOL;
      }
      self::$_head_meta = [];
    }

  }

  public static function content() {
    $result = '';
    if (count(self::$_contents) > 0) {
      foreach(self::$_contents as $content) {
        $result .= $content;
      }
    }
    return $result;
  }

  public static function add_content($content) {
    if (mb_strlen(trim($content)) > 0) {
      self::$_contents[] = trim($content);
    }
  }

  public static function set_page_action($page_action) {
    self::$_page_action = trim($page_action);
  }

  public static function page_action() {
    if (self::$_page_action == '') {
      self::$_page_action = Byte::$page_action;
    }
    return self::$_page_action;
  }

  public static function set_frame($frame) {
    self::$_frame = trim($frame);
  }

  public static function frame() {
    if (self::$_frame == '') {
      self::$_frame = Byte::$frame;
    }
    return self::$_frame;
  }

  public static function block($path, $params = []) {
    $block = PATH_BLOCK . $path . '.phtml';
    // añadir el bloque
    if (is_file($block)) {

      ob_start();
        extract($params, EXTR_OVERWRITE);
        include $block;
        $content = ob_get_contents();
      ob_end_clean();
      return $content;

    } else if (ERROR_REPORTING) {
       echo '<pre id="block" style="display: none">' . $block . '</pre>';
    }
  }

}