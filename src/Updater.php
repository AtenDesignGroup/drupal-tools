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

  public static function uninstallEntityType($entity_type_id) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    if (!$entity_type = $entity_type_manager->getDefinition($entity_type_id)) {
      throw new \Exception("Entity type $entity_type_id does not exist or is not installed");
    }
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $definition_update_manager->uninstallEntityType($entity_type);
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

  public static function installEntityField($field_name, $entity_type_id, $bundle, $module) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    if ($definition_update_manager->getFieldStorageDefinition($field_name, $entity_type_id)) {
      return;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    if (empty($field_definitions[$field_name])) {
      throw new \Exception("$entity_type_id field \"$field_name\" is not defined");
    }
    $definition_update_manager->installFieldStorageDefinition($field_name, $entity_type_id, $module, $field_definitions[$field_name]);
  }

  public static function uninstallEntityField($field_name, $entity_type_id, $bundle, $module) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    if (!$definition_update_manager->getFieldStorageDefinition($field_name, $entity_type_id)) {
      return;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    if (empty($field_definitions[$field_name])) {
      throw new \Exception("$entity_type_id field \"$field_name\" is not defined");
    }
    $definition_update_manager->uninstallFieldStorageDefinition($field_definitions[$field_name]);
  }
}
