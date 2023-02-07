<?php

namespace Drupal\background_image_tools\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Class BackgroundMediaRenderer.
 */
class BackgroundMediaRenderer {

  /**
   * Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal UUID service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * File Url Generator Service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileGenerator;

  /**
   * Constructs a new BackgroundsBackgroundImageRenderer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager object.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The Uuid object.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_generator
   *   The File Generator interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Php $uuid,
    FileUrlGeneratorInterface $file_generator
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;
    $this->fileGenerator = $file_generator;
  }

  /**
   * Return the path of the background image.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The Media entity to use.
   * @param string $image_style
   *   The ImageStyle path to get.
   *
   * @return string
   *   The file path.
   */
  protected function getFilePath(ContentEntityInterface $entity, string $image_style) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $entity;

    // Check to see if passed entity is a Media entity. If so, get the File
    // entity we need for processing.
    if ($entity->getEntityTypeId() == 'media') {
      /** @var \Drupal\media\MediaInterface $entity */
      $fid = $entity->getSource()->getSourceFieldValue($entity);
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
    }

    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = $this->entityTypeManager->getStorage('image_style')->load($image_style);
    $file_path = $style->buildUrl($file->getFileUri());

    return $file_path;
  }

  /**
   * Generate CSS for the page.
   *
   * @param string $selector
   *   The selector for the CSS.
   * @param string $file_path
   *   The path to the image file.
   *
   * @return string
   *   The CSS to be rendered.
   */
  protected function generateStyles(string $selector, string $file_path) {
    // @todo It's possible to pass this off to Twig which would make writing
    // the CSS easier.
    $css = sprintf('%s {', $selector);
    $css .= sprintf('background-image: url(\'%s\');', $this->fileGenerator->transformRelative($file_path));
    $css .= '}';

    return $css;
  }

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
  public function getStyles(
    string $selector,
    ContentEntityInterface $entity,
    string $image_style
  ) {
    $image_url = $this->getFilePath($entity, $image_style);
    $css = $this->generateStyles($selector, $image_url);

    return [
      [
        '#tag' => 'style',
        '#value' => $css,
      ],
      "background_image_tools_{$entity->id()}_{$this->uuid->generate()}",
    ];
  }

}
