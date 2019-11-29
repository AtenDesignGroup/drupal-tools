<?php

namespace Aten\DrupalTools;

class ArrayIterator extends \ArrayIterator {

  /**
   * @param $size
   * @param bool $preserve_keys
   *
   * @return array
   */
  public function chunk($size, $preserve_keys = FALSE) {
    $sets = array_chunk($this->getArrayCopy(), $size, $preserve_keys);
    foreach ($sets as &$set) {
      $set = new static($set);
    }
    return $sets;
  }
}
