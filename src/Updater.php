<?php

namespace Aten\DrupalTools;

use Drupal\Core\Entity\EntityDefinitionUpdateManager;

class Updater {

  public static function reinstallEntityType($entity_type_id) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    if (!$entity_type = $entity_type_manager->getDefinition($entity_type_id)) {
      throw new \Exception("Entity type $entity_type_id does not exist or is not installed");
    }
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $definition_update_manager->uninstallEntityType($entity_type);
    $definition_update_manager->installEntityType($entity_type);
  }

  public static function installEntityType($entity_type_id) {
    /** @var EntityDefinitionUpdateManager $definition_update_manager */
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    if ($definition_update_manager->getEntityType($entity_type_id)) {
      return;
    }
    $entity_type_manager = \Drupal::service('entity_type.manager');
    if (!$entity_type = $entity_type_manager->getDefinition($entity_type_id)) {
      throw new \Exception("Entity type $entity_type_id does not exist");
    }
    $definition_update_manager->installEntityType($entity_type);
  }
}