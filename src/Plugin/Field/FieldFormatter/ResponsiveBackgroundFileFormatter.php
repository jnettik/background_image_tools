<?php

declare(strict_types=1);

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

/**
 * Render field as a responsive background image.
 *
 * @FieldFormatter(
 *   id = "responsive_background_image_tools_file",
 *   label = @Translation("Responsive Background Image"),
 *   field_types = {
 *     "image"
 *   },
 *   provider = "responsive_image"
 * )
 */
class ResponsiveBackgroundFileFormatter extends BackgroundFormatterBase {
  // Passthrough base class, but keep this in case we need more specific
  // functionality for the formatter.
}
