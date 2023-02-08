<?php

declare(strict_types=1);

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

/**
 * Render field as background image.
 *
 * @FieldFormatter(
 *   id = "background_image_tools_file",
 *   label = @Translation("Background Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BackgroundFileFormatter extends BackgroundFormatterBase {
  // Passthrough base class, but keep this in case we need more specific
  // functionality for the formatter.
}
