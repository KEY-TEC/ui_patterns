<?php

namespace Drupal\ui_patterns_ds\Plugin\UiPatterns\Source;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ui_patterns\Plugin\PatternSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Display Suite field template source plugin.
 *
 * @UiPatternsSource(
 *   id = "ds_field_template",
 *   label = @Translation("Field template"),
 *   provider = "ds",
 *   tags = {
 *     "ds_field_template"
 *   }
 * )
 */
class DsFieldTemplateSource extends PatternSourceBase implements ContainerFactoryPluginInterface {

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFields() {
    $sources = [];
    $field_name = $this->getContextProperty('field_name');
    $entity_type = $this->getContextProperty('entity_type');
    $bundle = $this->getContextProperty('bundle');
    $field_definistions = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = isset($field_definistions[$field_name]) ? $field_definistions[$field_name]: NULL;
    if ($field !== null) {
      $label = $field->getLabel();
      $sources[] = $this->getSourceField($field_name, $label);
      foreach ($field->getFieldStorageDefinition()->getColumns() as $column_name => $column) {
        $sources[] = $this->getSourceField($field_name . '__' . $column_name, $label . ': ' . $column_name);
      }
    }

    return $sources;
  }

}
