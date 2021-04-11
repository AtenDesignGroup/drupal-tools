<?php

namespace Aten\DrupalTools;

class UpdaterEntities {

  protected $data = [];

  public function ensureMultiple($entity_type_id, array $entity_data) {
    $entities = [];
    foreach ($entity_data as $uuid => $data) {
      $entities[$uuid] = $this->ensure($entity_type_id, $uuid, $data);
    }
    return $entities;
  }

  public function ensure($entity_type_id, $uuid, array $data) {
    if ($entity = $this->get($entity_type_id, $uuid)) {
      return $entity;
    }
    if ($entity = $this->load($entity_type_id, $uuid)) {
      return $entity;
    }
    $data['uuid'] = $uuid;
    $entity = \Drupal::service('entity_type.manager')->getStorage($entity_type_id)->create($data);
    $entity->save();
    $this->data[$entity_type_id][$uuid] = $entity;
    return $entity;
  }

  public function load($entity_type_id, $uuid) {
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid($entity_type_id, $uuid);
    if (!empty($entity)) {
      $this->data[$entity_type_id][$uuid] = $entity;
      return $entity;
    }
    return FALSE;
  }

  public function get($entity_type_id, $uuid) {
    return $this->data[$entity_type_id][$uuid] ?? NULL;
  }

  public function overwrite($entity_type_id, $uuid, array $data) {
    if (!$entity = $this->load($entity_type_id, $uuid)) {
      return;
    }
    foreach ($data as $key => $value) {
      if (!$entity->hasField($key)) {
        continue;
      }
      $entity->set($key, $value);
    }
    $entity->save();
  }

  public function getId($entity_type_id, $uuid) {
    if (!$entity = $this->get($entity_type_id, $uuid)) {
      return;
    }
    return $entity->id();
  }
}
