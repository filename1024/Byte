<?php

declare(strict_types = 1);

namespace TortuLive;

class Kit {

  const BYTE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  const BYTE_PRECISION = [4, 4, 4, 4, 4, 4, 4, 4, 4];
  const BYTE_NEXT = 1024;

  public static function bytes_size_human($bytes = 0, $precision = NULL) {
    $bytes = (int)$bytes;
    for ($i = 0; ($bytes / self::BYTE_NEXT) >= 0.9 && $i < count(self::BYTE_UNITS); $i++) {
      $bytes /= self::BYTE_NEXT;
    }
    return round($bytes, is_null($precision) ? self::BYTE_PRECISION[$i] : (int)$precision) . self::BYTE_UNITS[$i];
  }

}