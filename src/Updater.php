<?php

namespace Aten\DrupalTools;

use Drupal\Core\Entity\EntityDefinitionUpdateManager;

class Updater {

  public static function batch(&$sandbox, $size = 10, $chunk = FALSE) {
    return new UpdaterBatch($sandbox, $size, $chunk);
  }

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

  /**
   * TODO: Remove $bundle as it is not used for field storage load.
   *
   * @param $field_name
   * @param $entity_type_id
   * @param null $bundle
   * @param $module
   */
  public static function installEntityField($field_name, $entity_type_id, $bundle = NULL, $module) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    if ($definition_update_manager->getFieldStorageDefinition($field_name, $entity_type_id)) {
      return;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    if (empty($field_definitions[$field_name])) {
      throw new \Exception("$entity_type_id field \"$field_name\" is not defined");
    }
    $definition_update_manager->installFieldStorageDefinition($field_name, $entity_type_id, $module, $field_definitions[$field_name]);
  }

  public static function updateEntityField($field_name, $entity_type_id, $data_mapping = []) {
    /** @var EntityDefinitionUpdateManager $definition_update_manager */
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

    $storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
    $original_storage_definitions = $entity_last_installed_schema_repository->getLastInstalledFieldStorageDefinitions($entity_type_id);
    if (empty($original_storage_definitions[$field_name])) {
      throw new \Exception("$entity_type_id field \"$field_name\" is not defined");
    }

    $data = static::fetchFieldData($entity_type_id, $field_name, $original_storage_definitions);

    // TODO: Clear the data.
    static::repopulateFieldData($data, $data_mapping, $entity_type_id, 'commerce_remote_ids', $storage_definitions);

    $debug = 'true';
    //$definition_update_manager->updateFieldStorageDefinition($field_definitions[$field_name]);
  }

  public static function clearFieldData() {

  }

  public static function repopulateFieldData(array $data, array $data_mapping = [], $entity_type_id, $field_name, $storage_definitions) {
    $entity_type = \Drupal::service('entity_type.manager')->getDefinition($entity_type_id);
    $table_mapping = \Drupal::service('entity_type.manager')->getStorage($entity_type_id)->getTableMapping($storage_definitions);
    $database = \Drupal::service('database');
    $storage_definition = $storage_definitions[$field_name];
    $column_definitions = $storage_definition->getColumns();
    $column_names = array_map(function($column_name) use ($table_mapping, $storage_definition) {
      return $table_mapping->getFieldColumnName($storage_definition, $column_name);
    }, array_keys($column_definitions));

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $storage_definition->isDeleted();
      if ($entity_type->isRevisionable()) {
        $table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
      }
      else {
        $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
      }
      $query = $database->insert($table_name);
      // basically take values from row, and turn into fields.
//      $query
//        ->fields()
//        ->execute();

    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      // Ascertain the table this field is mapped too.
      $field_name = $storage_definition->getName();
      $table_name = $table_mapping->getFieldTableName($field_name);
      $query = $database->update($table_name);
//      $query
//        ->condition($entity_type->getKey('id'),  $data[$entity_type->getKey('id')])
//        same mapping as above
//        ->fields('t', array_merge([$entity_type->getKey('id')], $column_names))
//        ->distinct(TRUE);
    }
    foreach ($data as $datum) {
      $datum = (array) $datum;
      static::map($datum, $data_mapping);
      $query->fields($datum)->execute();

      $debug = 'true';
    }

  }

  private static function map(array &$datum, array $mapping) {
    foreach ($mapping as $from => $to) {
      if (!isset($datum[$from])) {
        continue;
      }
      $datum[$to] = $datum[$from];
      unset($datum[$from]);
    }
  }

  public static function fetchFieldData($entity_type_id, $field_name, $storage_definitions) {
    $entity_type = \Drupal::service('entity_type.manager')->getDefinition($entity_type_id);
    $table_mapping = \Drupal::service('entity_type.manager')->getStorage($entity_type_id)->getTableMapping($storage_definitions);
    $database = \Drupal::service('database');
    $storage_definition = $storage_definitions[$field_name];
    $column_definitions = $storage_definition->getColumns();
    $id_key = $entity_type->getKey('id');
    $column_names = array_map(function($column_name) use ($table_mapping, $storage_definition) {
      return $table_mapping->getFieldColumnName($storage_definition, $column_name);
    }, array_keys($column_definitions));

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $storage_definition->isDeleted();
      if ($entity_type->isRevisionable()) {
        $table_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
      }
      else {
        $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
      }
      $query = $database->select($table_name, 't');
      $or = $query->orConditionGroup();
      foreach ($column_definitions as $column_name => $data) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
      }
      $query->condition($or);
      $query
        ->fields('t', array_merge(['entity_id'], $column_names))
        ->distinct(TRUE);
    }
    elseif ($table_mapping->allowsSharedTableStorage($storage_definition)) {
      // Ascertain the table this field is mapped too.
      $field_name = $storage_definition->getName();
      $table_name = $table_mapping->getFieldTableName($field_name);
      $query = $database->select($table_name, 't');
      $or = $query->orConditionGroup();

      foreach (array_keys($column_definitions) as $property_name) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $property_name));
      }
      $query->condition($or);
      $query
        ->fields('t', array_merge([$id_key], $column_names))
        ->distinct(TRUE);
    }

    // @todo Find a way to count field data also for fields having custom
    //   storage. See https://www.drupal.org/node/2337753.
    // If we are performing the query just to check if the field has data
    // limit the number of rows.
    return isset($query) ? $query->execute()->fetchAllAssoc($id_key) : [];
  }

  public static function uninstallEntityField($field_name, $entity_type_id, $bundle, $module) {
    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    if (!$definition_update_manager->getFieldStorageDefinition($field_name, $entity_type_id)) {
      return;
    }

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id, $bundle);
    if (empty($field_definitions[$field_name])) {
      throw new \Exception("$entity_type_id field \"$field_name\" is not defined");
    }
    $definition_update_manager->uninstallFieldStorageDefinition($field_definitions[$field_name]);
  }

  public static function entities() {
    return new UpdaterEntities();
  }
}
