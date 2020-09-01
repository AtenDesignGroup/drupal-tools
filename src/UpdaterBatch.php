<?php

namespace Aten\DrupalTools;

class UpdaterBatch {

  protected $sandbox;

  protected $size;

  protected $chunk;

  public function __construct(&$sandbox, $size = 10, $chunk = FALSE) {
    $this->sandbox = &$sandbox;
    $this->size = $size;
    $this->chunk = $chunk;
  }

  public function inProgress() {
    return isset($this->sandbox['progress']);
  }

  public function init($records) {
    // The count of nodes visited so far.
    $this->sandbox['progress'] = 0;
    if ($this->chunk !== FALSE) {
      $this->sandbox['records'] = array_chunk($records, $this->chunk, TRUE);
    }
    else {
      $this->sandbox['records'] = $records;
    }
    $this->sandbox['max'] = count($this->sandbox['records']);
  }

  public function process($callable) {
    for ($x = 0; $x < $this->size; $x++) {
      $record = array_shift($this->sandbox['records']);
      $callable($record);
      $this->sandbox['progress']++;
    }

    $this->sandbox['#finished'] = $this->sandbox['progress'] >= $this->sandbox['max'] ? TRUE : $this->sandbox['progress'] / $this->sandbox['max'];
  }

  public function summary() {
    return t('Processed @count out of @total total records.', [
      '@count' => $this->sandbox['progress'],
      '@total' => $this->sandbox['total'],
    ]);
  }

}
