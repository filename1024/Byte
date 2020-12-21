<?php

declare(strict_types = 1);

/**
 * Copyright (c) 2020 - present TortuLive.com <filename@tortulive.net>
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright   Copyright (c) 2020 - present TortuLive.com <filename@tortulive.net>
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 * @author      filename <filename@tortulive.net>
 * @link        https://byte.tortulive.com
 */

use TortuLive\Byte;

define('TIME_START', microtime(true));
require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Byte.php';
$app = new Byte();
// ¡suerte!
$app->luck();