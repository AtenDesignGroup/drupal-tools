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
    // Store the actual total if this is chunked for the summary.
    $this->sandbox['total'] = count($records);
  }

  public function process($callable) {
    for ($x = 0; $x < $this->size; $x++) {
      $record = array_shift($this->sandbox['records']);
      $callable($record);
      $this->sandbox['current'] = [
        'record' => $record,
        'progress' => $this->sandbox['progress'],
      ];
      $this->sandbox['progress']++;
    }

    $this->sandbox['#finished'] = $this->sandbox['progress'] >= $this->sandbox['max'] ? TRUE : $this->sandbox['progress'] / $this->sandbox['max'];
  }

  public function summary() {
    $progress = $this->sandbox['progress'];
    // Process range or count of the total amount of records.
    if ($this->chunk !== FALSE) {
      // Current progress was incremented so rewind to display.
      $range_start = $this->sandbox['current']['progress'] * $this->chunk;
      $range_end = $range_start + count($this->sandbox['current']['record']);
      $progress = "{$range_start} - {$range_end}";
    }
    return t('Processed @count out of @total total records.', [
      '@count' => $progress,
      '@total' => $this->sandbox['total'],
    ]);
  }

}
