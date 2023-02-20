<?php

declare(strict_types=1);

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

use Drupal\background_image_tools\Services\BackgroundMediaRenderer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Background Field Formatters.
 */
abstract class BackgroundFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The background image render service.
   *
   * @var \Drupal\background_image_tools\Services\BackgroundMediaRenderer
   */
  protected $backgroundRenderer;

  /**
   * Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a BackgroundImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Defines an interface for entity field definitions.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\background_image_tools\Services\BackgroundMediaRenderer $background_renderer
   *   Background renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager object.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    BackgroundMediaRenderer $background_renderer,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->backgroundRenderer = $background_renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) : static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('background_image_tools.media'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() : array {
    return [
      'selector' => '',
      'image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) : array {
    $settings = $this->getSettings();

    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Selector'),
      '#default_value' => $this->t('Set the CSS selector to style the image to.'),
      '#default_value' => $settings['selector'],
    ];

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->t('Pick which image style should render this image.'),
      '#default_value' => $settings['image_style'],
      '#options' => $this->getImageStyles(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() : array {
    $settings = $this->getSettings();
    $summary = parent::settingsSummary();

    $summary[] = isset($settings['selector'])
      ? $this->t('CSS Selector: @selector', ['@selector' => $settings['selector']])
      : $this->t('CSS Selector: None');

    $summary[] = isset($settings['image_style'])
      ? $this->t('Image Style: @image_style', ['@image_style' => $this->getImageStyleLabel($settings['image_style'])])
      : $this->t('Image Style: None');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) : array {
    $settings = $this->getSettings();
    $styles = [];

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    foreach ($items as $item) {
      $entity = $item->get('entity')->getValue();

      if ($this->isImage($entity)) {
        $styles[] = $this->backgroundRenderer->getStyles(
          $settings['selector'],
          $entity,
          $settings['image_style']
        );
      }
    }

    return [
      '#attached' => [
        'html_head' => $styles,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) : array {
    // Prevent field from rendering.
    return [];
  }

  /**
   * A methoed that can get used to filter out entities from being used.
   *
   * Defaults to TRUE unless method is overridden.
   *
   * @param \Drupal\media\MediaInterface|Drupal\file\FileInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Whether this passes the checks.
   */
  public function isImage(MediaInterface|FileInterface $entity) : bool {
    return TRUE;
  }

  /**
   * Get an array of all image style info.
   *
   * @return array
   *   The image style info.
   */
  protected function getImageStyles() : array {
    /** @var \Drupal\image\ImageStyleStorage $image_style_storage */
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');
    $image_styles = $image_style_storage->loadMultiple();
    $styles = [];

    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($image_styles as $machine_name => $style) {
      $styles[$machine_name] = $style->label();
    }

    return $styles;
  }

  /**
   * Get human readable name of an image style.
   *
   * @param string $image_style
   *   Machine name of an image style.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The image style label.
   */
  protected function getImageStyleLabel(string $image_style) : TranslatableMarkup|string {
    $image_styles = $this->getImageStyles();
    return $image_styles[$image_style] ?? $this->t('None');
  }

}
