<?php

namespace Drupal\background_image_tools\Services;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the background media renderer service.
 */
interface BackgroundMediaRendererInterface {

  /**
   * Renders the CSS for the background image.
   *
   * @param string $selector
   *   The CSS selector to use.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The Media or File (Image) entity to use for the background.
   * @param string $image_style
   *   The ImageStyle to use for the image.
   *
   * @return array
   *   Render array to be attached to site `head`.
   */
  public function getStyles(string $selector, ContentEntityInterface $entity, string $image_style);

}
