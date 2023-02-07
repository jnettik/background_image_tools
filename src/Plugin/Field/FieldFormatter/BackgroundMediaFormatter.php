<?php

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Render field as background image.
 *
 * @FieldFormatter(
 *   id = "background_image_tools_media",
 *   label = @Translation("Background Image"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class BackgroundMediaFormatter extends BackgroundFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media');
  }

}
