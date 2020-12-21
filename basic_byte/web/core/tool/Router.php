<?php

declare(strict_types = 1);

namespace TortuLive;

class Router {

  // inicializa lo necerario para responder
  public static function request() {

    Byte::$request_rewrite = Router::rewrite();
    // no hay superisor
    if (Byte::$request_rewrite == '') {

      if (is_file(PATH_APP . "supervisor" . DIRECTORY_SEPARATOR . Byte::$supervisor . '.php')) {
        $class = 'TortuLive\\' . Byte::$supervisor;
        Byte::$obj_supervisor = new $class();
        return TRUE;
      } else {
        return FALSE;
      }

    } else {
      //con superisor
      $parts = explode('/', Byte::$request_rewrite);
      Byte::$supervisor = current($parts);

      if (is_file(PATH_APP . "supervisor" . DIRECTORY_SEPARATOR . Byte::$supervisor . '.php')) {
        $class = 'TortuLive\\' . Byte::$supervisor;
        Byte::$obj_supervisor = new $class();
      } else {
        return FALSE;
      }

      // no hay action
      if (next($parts) === FALSE) {
        return TRUE;
      }

      Byte::$action = current($parts);
      View::set_page_action(Byte::$action);
      $is_callable = is_callable([Byte::$obj_supervisor, Byte::$action]);
      $is_action_equal_supervisor = Byte::$action == Byte::$supervisor;
      $is_construct = Byte::$action == '__construct';
      $is_magic = FALSE;

      if (mb_strlen(Byte::$action) > 2 && '__' == substr(Byte::$action, 0, 2)) {
        $is_magic = TRUE;
      }

      if ($is_construct || $is_action_equal_supervisor || $is_magic || !$is_callable) {
        return FALSE;
      }

      // intenta acceder a la función del supervisor
      try {
        $reflection_method = new \ReflectionMethod(Byte::$obj_supervisor, Byte::$action);
      } catch (\ReflectionException $e) {
        return FALSE;
      }
      // no hay parámetros
      if (next($parts) === FALSE) {
        return TRUE;
      }

      Byte::$parameters = array_slice($parts, key($parts));
      $num_parameters = count(Byte::$parameters);
      $num_required_parameters = $reflection_method->getNumberOfRequiredParameters();
      $num_parameters = $reflection_method->getNumberOfParameters();
      // distinto número de parámetros
      if ($num_parameters < $num_required_parameters ||  $num_parameters > $num_parameters) {
        return FALSE;
      }

      return TRUE;

    }//con superisor

  }

  // obtener la verdadera petición
  private static function rewrite() {

    Byte::$request = $_GET['request'] ?? '/';
    Byte::$request = filter_var(Byte::$request, FILTER_SANITIZE_URL);
    $last = mb_substr(Byte::$request, -1);
    // url sin la / del final
    if ($last == '/') {
      Byte::$request = mb_substr(Byte::$request, 0, -1);
    }
    // si existe una redirección la usamos
    return LIST_REWRITE_REQUESTS[Byte::$request] ?? Byte::$request;

  }

}