<?php

declare(strict_types = 1);

namespace TortuLive;

class E404 extends SupervisorRoot {

  public function default() {
    $this->_title = "Página no encontrada";
    $this->_description = "Página no encontrada";
  }

}