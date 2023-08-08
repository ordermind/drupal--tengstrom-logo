<?php

namespace Drupal\tengstrom_logo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image\ImageStyleInterface;
use Drupal\tengstrom_config_logo\LogoFileLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Logo Block.
 *
 * @Block(
 *   id = "tengstrom_logo",
 *   admin_label = @Translation("Logo Block"),
 * )
 */
class LogoBlock extends BlockBase implements ContainerFactoryPluginInterface {
  private EntityStorageInterface $imageStyleStorage;
  private LogoFileLoader $logoFileLoader;

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityStorageInterface $imageStyleStorage,
    LogoFileLoader $logoFileLoader
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->imageStyleStorage = $imageStyleStorage;
    $this->logoFileLoader = $logoFileLoader;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entityTypeManager = $container->get('entity_type.manager');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entityTypeManager->getStorage('image_style'),
      $container->get('tengstrom_config_logo.file_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $imageRender = !empty($config['image_style']) ? $this->renderStyledImage($config['image_style']) : $this->renderOriginalImage();

    if (empty($config['link_path'])) {
      return $imageRender;
    }

    return [
      '#type' => 'link',
      '#title' => $imageRender,
      '#url' => Url::fromRoute($config['link_path']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $imageStyles = $this->imageStyleStorage->loadMultiple();

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#default_value' => $config['image_style'] ?? NULL,
      '#options' => array_combine(
        array_keys($imageStyles),
        array_map(
          fn (ImageStyleInterface $imageStyle) => $imageStyle->label(),
          $imageStyles
        )
      ),
      '#empty_option' => $this->t('- None -'),
    ];

    $form['link_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link path'),
      '#default_value' => $config['link_path'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['image_style'] = $values['image_style'];
    $this->configuration['link_path'] = $values['link_path'];
  }

  private function renderOriginalImage(): array {
    $logoFile = $this->logoFileLoader->loadLogo();
    if (!$logoFile) {
      return [];
    }

    return [
      '#theme' => 'image',
      '#uri' => $logoFile->getFileUri(),
    ];
  }

  private function renderStyledImage(string $imageStyleName): array {
    $logoFile = $this->logoFileLoader->loadLogo();
    if (!$logoFile) {
      return [];
    }

    return [
      '#theme' => 'image_style',
      '#uri' => $logoFile->getFileUri(),
      '#style_name' => $imageStyleName,
    ];
  }

}
