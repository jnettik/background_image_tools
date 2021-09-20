<?php

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

use Drupal\background_image_tools\Services\BackgroundMediaRenderer;
use Drupal\background_image_tools\Services\BackgroundsBackgroundImageRenderer;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class BackgroundMediaFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The background image render service.
   *
   * @var \Drupal\background_image_tools\Services\BackgroundMediaRenderer
   */
  protected $backgroundRenderer;

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
   * @param \Drupal\backgrounds\Services\BackgroundMediaRenderer $background_renderer
   *   Background renderer service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    BackgroundMediaRenderer $background_renderer
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
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('background_image_tools.media')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'selector' => '',
      'image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Selector'),
      '#default_value' => $this->t('Set the CSS selector to style the image to.'),
      '#default_value' => $settings['selector'],
    ];

    // @todo pull in dynamic list of styles.
    $form['image_style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->t('Pick which image style should render this image.'),
      '#default_value' => $settings['image_style'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    if ($settings['selector']) {
      $summary[] = $this->t('CSS Selector: @selector', ['@selector' => $settings['selector']]);
    }

    if ($settings['image_style']) {
      $summary[] = $this->t('Image Style: @image_style', ['@image_style' => $settings['image_style']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
