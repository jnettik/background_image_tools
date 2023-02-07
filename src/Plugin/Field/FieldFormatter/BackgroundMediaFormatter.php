<?php

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

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
  // Passthrough base class, but keep this in case we need more specific
  // functionality for the formatter.
}
