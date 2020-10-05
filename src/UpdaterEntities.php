<?php

namespace Aten\DrupalTools;

class UpdaterEntities {

  protected $data = [];

  public function ensureMultiple($entity_type_id, array $entities) {
    foreach ($entities as $uuid => $data) {
      $this->ensure($entity_type_id, $uuid, $data);
    }
  }

  public function ensure($entity_type_id, $uuid, array $data) {
    if ($this->get($entity_type_id, $uuid)) {
      return $this->get($entity_type_id, $uuid);
    }
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid($entity_type_id, $uuid);
    if (!empty($entity)) {
      $this->data[$entity_type_id][$uuid] = $entity;
      return $entity;
    }
    $data['uuid'] = $uuid;
    $entity = \Drupal::service('entity_type.manager')->getStorage($entity_type_id)->create($data);
    $entity->save();
    $this->data[$entity_type_id][$uuid] = $entity;
    return $entity;
  }

  public function get($entity_type_id, $uuid) {
    return $this->data[$entity_type_id][$uuid] ?? NULL;
  }

  public function getId($entity_type_id, $uuid) {
    if (!$entity = $this->get($entity_type_id, $uuid)) {
      return;
    }
    return $entity->id();
  }
}
