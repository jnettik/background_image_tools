<?php

declare(strict_types=1);

namespace Drupal\background_image_tools\Plugin\Field\FieldFormatter;

use Drupal\background_image_tools\Services\BackgroundMediaRenderer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
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
   * Drupal module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandler object.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token object.
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
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    Token $token
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
    $this->moduleHandler = $module_handler;
    $this->token = $token;
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
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('token')
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
    $token_support = $this->moduleHandler->moduleExists('token');

    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Selector'),
      '#description' => $this->t('Set the CSS selector to style the image to. This field supports Tokens.'),
      '#default_value' => $settings['selector'],
    ];

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#description' => $this->t('Pick which image style should render this image.'),
      '#default_value' => $settings['image_style'],
      '#options' => $this->getImageStyles(),
    ];

    if ($token_support) {
      $form['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$form['#entity_type']],
      ];
    }

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
        $host_entity = $item->getEntity();
        $host_entity_type = $host_entity->getEntityTypeId();
        $selector = $this->token->replace($settings['selector'], [
          $host_entity_type => $host_entity,
        ]);

        $styles[] = $this->backgroundRenderer->getStyles(
          $selector,
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
