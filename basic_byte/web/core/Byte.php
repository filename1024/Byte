<?php

declare(strict_types = 1);

namespace TortuLive;

class Byte {

  private $_error_reporting = FALSE;
  private $_cache = TRUE;
  // sessión y cookie
  private $_seconds = (365 * 24 * 3600);
  private $_trace = FALSE;
  // redireciones
  private $_list_rewrite_requests = [];
  // por defecto
  public static $obj_supervisor = NULL;
  public static $supervisor = 'bit';
  public static $action = 'default';
  public static $frame = 'default';
  public static $page_action = 'default';
  public static $parameters = [];
  public static $request = '';
  public static $request_rewrite = '';

  public function __construct() {

    // carpeta raíz /
    define('PATH_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    // web/
    define('PATH_WEB', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
    // web/core/
    define('PATH_CORE', dirname(__FILE__) . DIRECTORY_SEPARATOR);
    // web/core/tool/
    define('PATH_TOOL', PATH_CORE . 'tool' . DIRECTORY_SEPARATOR);
    // web/app/
    define('PATH_APP', PATH_WEB . 'app' . DIRECTORY_SEPARATOR);
     // web/app/class/
     define('PATH_CLASS', PATH_APP . 'class' . DIRECTORY_SEPARATOR);
     // web/app/frame/
    define('PATH_FRAME', PATH_APP . 'frame' . DIRECTORY_SEPARATOR);
    // web/app/lib/
    define('PATH_LIB', PATH_APP . 'lib' . DIRECTORY_SEPARATOR);
    // web/app/page/
    define('PATH_PAGE', PATH_APP . 'page' . DIRECTORY_SEPARATOR);
    // web/app/supervisor/
    define('PATH_SUPERVISOR', PATH_APP . 'supervisor' . DIRECTORY_SEPARATOR);
    // web/public/
    define('PATH_PUBLIC', PATH_WEB . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
    // autocarga de clases
    spl_autoload_register([__CLASS__, '_autoload_class']);

    $config = (object)parse_ini_file(PATH_APP . 'config.ini', true);
    // configuraciones generales
    $this->_error_reporting = $config->general['error_reporting'] ?? $this->_error_reporting;
    $this->_cache = $config->general['cache'] ?? $this->_cache;
    $this->seconds = $config->general['seconds'] ?? $this->_seconds;
    $this->_trace = $config->general['trace'] ?? $this->_trace;
    self::$supervisor = $config->general['supervisor'] ?? self::$supervisor;
    self::$action = $config->general['action'] ?? self::$action;
    self::$frame = $config->general['frame'] ?? self::$frame;
    self::$page_action = $config->general['page_action'] ?? self::$page_action;
    // redireciones
    $this->_list_rewrite_requests = $config->rewrite['list_rewrite_requests'] ?? $this->_list_rewrite_requests;
    // supervisor
    $charset = $config->supervisor['charset'] ?? 'utf-8';
    $lang = $config->supervisor['lang'] ?? 'es';
    $dir = $config->supervisor['dir'] ?? 'ltr';
    $author = $config->supervisor['author'] ?? 'filename';
    $generator = $config->supervisor['generator'] ?? 'Byte';

    define('ERROR_REPORTING', $this->_error_reporting);
    define('CACHE', $this->_cache);
    define('SECONDS', $this->_seconds);
    define('TRACE', $this->_trace);
    define('LIST_REWRITE_REQUESTS', $this->_list_rewrite_requests);
    define('CHARSET', $charset);
    define('LANG', $lang);
    define('DIR', $dir);
    define('AUTHOR', $author);
    define('GENERATOR', $generator);

  }

  // si la clase existe en alguna de las rutas
  // se incluye
  private function _autoload_class($class) {

    $pos = strpos($class, 'TortuLive\\');
    // clases propias del framework
    if ($pos !== FALSE) {
      $supervisor = self::$supervisor;
      switch ($class) {
        case 'TortuLive\\Router':
          return require_once PATH_TOOL . 'Router.php';
          break;
        case 'TortuLive\\View':
          return require_once PATH_TOOL . 'View.php';
          break;
        case 'TortuLive\\Kit':
          return require_once PATH_TOOL . 'Kit.php';
          break;
        case 'TortuLive\\SupervisorRoot':
          return require_once PATH_TOOL . 'SupervisorRoot.php';
          break;
        case "TortuLive\\$supervisor":
          return require_once PATH_SUPERVISOR . "$supervisor.php";
          break;
        default:
          if (ERROR_REPORTING) {
            echo '<pre id="autoload_class" style="display: none">(class)' . $class . '</pre>';
          }
        break;
      }

    } else {
      // otras clases
      $file = "$class.php";
      if (is_file(PATH_ROOT . $file)) {
        return require_once PATH_ROOT . $file;
      } else if (is_file(PATH_WEB . $file)) {
        return require_once PATH_WEB . $file;
      } else if (is_file(PATH_CORE . $file)) {
        return require_once PATH_CORE . $file;
      } else if (is_file(PATH_TOOL . $file)) {
        return require_once PATH_TOOL . $file;
      } else if (is_file(PATH_LIB . $file)) {
        return require_once PATH_LIB . $file;
      } else if (is_file(PATH_CLASS . $file)) {
        return require_once PATH_CLASS . $file;
      } else if (is_file(PATH_SUPERVISOR . $file)) {
        return require_once PATH_SUPERVISOR . $file;
      } else if (ERROR_REPORTING) {
        echo '<pre id="autoload_class" style="display: none">' . $class . ':' . $file . '</pre>';
      }

    }

  }

  public static function _error_handler(int $num, string $msg, string $file, int $line) {
    echo '<pre id="error" style="display: none">' . $line. ': ' . $file. ': ' .$msg. ': ' . $num . '</pre>';
  }

  private function _init() {

    try {

      ob_start();
      if (ERROR_REPORTING) {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', "On");
        set_error_handler([__CLASS__, '_error_handler'], E_ALL | E_STRICT);
      } else {
        error_reporting(0);
        ini_set('display_errors', "Off");
      }

      ini_set('session.cookie_lifetime', strval(SECONDS));
      ini_set('session.gc_maxlifetime', strval(SECONDS));

    } catch (\Error $e) {
      var_dump($e);
    } finally {
      $trash_init = ob_get_contents();
      ob_end_clean();
    }

    if (ERROR_REPORTING && trim($trash_init) != '') {
      echo '<pre id="trash_init" style="display: none">' . $trash_init . '</pre>';
    }

  }

  // ¡suerte!
  public function luck() {

    try {

      ob_start();
        $this->_init();
        header('X-Powered-By: Byte');
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $life_time = time() + SECONDS;
        setcookie(session_name(), session_id(), $life_time, '/', $_SERVER['SERVER_NAME'], TRUE);
        session_name("byte");
        session_start();
        $status = 404;

        if (Router::request()) {
          $status = 200;
        }

        View::generate($status);

    } catch (\Error $e) {
        var_dump($e);
    } finally {
        $trash_luck = ob_get_contents();
      ob_end_clean();
    }

    if (ERROR_REPORTING && trim($trash_luck) != '') {
      echo '<pre id="trash_luck" style="display: none">' . $trash_luck . '</pre>';
    }

  }

}