<?php

namespace Drupal\ui_patterns\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns\UiPatternsSettings;

/**
 * Renders a pattern element.
 *
 * @RenderElement("pattern")
 */
class Pattern extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => FALSE,
      '#multiple_sources' => FALSE,
      '#pre_render' => [
        [$class, 'processContext'],
        [$class, 'processRenderArray'],
        [$class, 'processLibraries'],
        [$class, 'processMultipleSources'],
        [$class, 'processFields'],
        [$class, 'processSettings'],
        [$class, 'processUse'],
      ],
    ];
  }

  /**
   * Process render array.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processRenderArray(array $element) {
    $element['#theme'] = UiPatterns::getPatternDefinition($element['#id'])->getThemeHook();

    if (isset($element['#attributes']) && !empty($element['#attributes']) && is_array($element['#attributes'])) {
      $element['#attributes'] = new Attribute($element['#attributes']);
    }
    else {
      $element['#attributes'] = new Attribute();
    }

    unset($element['#type']);
    return $element;
  }

  /**
   * Process libraries.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processLibraries(array $element) {
    foreach (UiPatterns::getPatternDefinition($element['#id'])->getLibrariesNames() as $library) {
      $element['#attached']['library'][] = $library;
    }

    return $element;
  }

  /**
   * Process fields.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processFields(array $element) {
    // Make sure we don't render anything in case fields are empty.
    if (self::hasFields($element)) {
      $fields = $element['#fields'];
      unset($element['#fields']);

      foreach ($fields as $name => $field) {
        $key = '#' . $name;
        $element[$key] = $field;
      }
    }
    else {
      // There are maybe regions added to the pattern by third party
      // modules. For example layout builder adds the 'Add Block' link.
      // So simple remap them.
      $definition = UiPatterns::getPatternDefinition($element['#id']);
      // Check if the pattern is empty
      $empty_fields = TRUE;
      foreach ($definition->getFields() as $key => $field) {
        if (isset($element[$key])) {
          $element['#' . $key] = $element[$key];
          unset($element[$key]);
          $empty_fields = FALSE;
        }
      }

      if ($empty_fields == TRUE) {
        $element['#markup'] = '';
      }
    }
    return $element;
  }

  /**
   * Process use property.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processUse(array $element) {
    $definition = UiPatterns::getPatternDefinition($element['#id']);
    if ($definition->hasUse()) {
      $element['#use'] = $definition->getUse();
    }

    return $element;
  }

  /**
   * Process settings.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processSettings(array $element) {
    // Make sure we don't render anything in case fields are empty.
    if (self::hasSettings($element)) {
      $settings = isset($element['#settings']) ? $element['#settings'] : [];
      $context = $element['#context'];
      $pattern_id = $element['#id'];
      $entity = $context->getProperty('entity');
      $settings = UiPatternsSettings::preprocess($pattern_id, $settings, $entity);
      unset($element['#settings']);
      foreach ($settings as $name => $setting) {
        $key = '#' . $name;
        if (!isset($element[$key])) {
          $element[$key] = $setting;
        }
        else {
          if ($setting instanceof Attribute && $element[$key] instanceof Attribute) {
            $element[$key] = new Attribute(array_merge($setting->toArray(), $element[$key]->toArray()));
          }
          elseif (is_array($element[$key]) && is_array($setting)) {
            $element[$key] = array_merge($element[$key], $setting);
          }
        }
      }
    }
    return $element;
  }

  /**
   * Process fields.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processMultipleSources(array $element) {
    // Make sure we don't render anything in case fields are empty.
    if (self::hasFields($element) && self::hasMultipleSources($element)) {
      foreach ($element['#fields'] as $name => $field) {
        // This guarantees backward compatibility: single sources be simple.
        $element['#fields'][$name] = reset($field);
        if (count($field) > 1) {
          /** @var \Drupal\ui_patterns\Element\PatternContext $context */
          $context = $element['#context'];
          $context->setProperty('pattern', $element['#id']);
          $context->setProperty('field', $name);

          // Render multiple sources with "patterns_destination" template.
          $element['#fields'][$name] = [
            '#sources' => $field,
            '#context' => $context,
            '#theme' => 'patterns_destination',
          ];
        }
      }
    }
    return $element;
  }

  /**
   * Process context.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\ui_patterns\Exception\PatternRenderException
   *    Throws an exception if no context type is specified.
   */
  public static function processContext(array $element) {

    if (self::hasValidContext($element)) {
      $context = $element['#context'];
      $element['#context'] = new PatternContext($context['type'], $element['#context']);
    }
    else {
      $element['#context'] = new PatternContext('empty');
    }

    return $element;
  }

  /**
   * Whereas pattern has settings or not.
   *
   * @return bool
   *    TRUE or FALSE.
   */
  public static function hasSettings($element) {
    $definition = UiPatterns::getPatternDefinition($element['#id']);
    if ($definition != NULL && count($definition->getSettings()) != 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Whereas pattern has field or not.
   *
   * @param array $element
   *   Render array.
   *
   * @return bool
   *    TRUE or FALSE.
   */
  public static function hasFields($element) {
    return isset($element['#fields']) && !empty($element['#fields']) && is_array($element['#fields']);
  }

  /**
   * Whereas pattern fields can accept multiple sources.
   *
   * @param array $element
   *   Render array.
   *
   * @return bool
   *    TRUE or FALSE.
   */
  public static function hasMultipleSources($element) {
    return isset($element['#multiple_sources']) && $element['#multiple_sources'] === TRUE;
  }

  /**
   * Whereas pattern has a valid context, i.e. context "type" is set.
   *
   * @param array $element
   *   Render array.
   *
   * @return bool
   *    TRUE or FALSE.
   */
  public static function hasValidContext($element) {
    return isset($element['#context']) && is_array($element['#context']) && !empty($element['#context']['type']);
  }

}
