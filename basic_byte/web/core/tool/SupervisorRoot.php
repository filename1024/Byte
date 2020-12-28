<?php

declare(strict_types = 1);

namespace TortuLive;

class SupervisorRoot {

  public $_charset = CHARSET;
  public $_lang = LANG;
  public $_dir = DIR;
  public $_author = AUTHOR;
  public $_generator = GENERATOR;
  public $_title = '';
  public $_description = '';
  public $_keywords = '';
  public $_device = '';

  public function  __construct() {
    $this->_device = Kit::device();
  }

}