<?php

/**
 * @file
 * API file.
 */

use Drupal\ui_patterns\Definition\PatternDefinition;

/**
 * Alter pattern layout "Manage display" form.
 *
 * @param array $form
 *   Pattern settings fieldset.
 * @param \Drupal\ui_patterns\Definition\PatternDefinition $definition
 *   Pattern definition.
 * @param array $configuration
 *   Pattern configuration.
 *
 */
function hook_ui_patterns_layout_form_alter(array &$form, PatternDefinition $definition, array $configuration) {
  $form['element'] = ['#type' => 'input'];
}
