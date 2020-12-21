<?php

declare(strict_types = 1);

namespace TortuLive;

class Bit extends SupervisorRoot {

  function  __construct() {
		parent::__construct();
  }

  public function default() {
    $this->_title = "¡Hola humanos desde $this->_generator!";
    $this->_description = "¿Quieres hacer una página web?  $this->_generator te ayuda a conseguirlo.";
  }

  public function credits() {
    $this->_title = 'Títulos de Crédito y Agradecimientos';
    $this->_description = "Agradecemos a todas las personas y sus creaciones que hacen posible $this->_generator.";
  }

}