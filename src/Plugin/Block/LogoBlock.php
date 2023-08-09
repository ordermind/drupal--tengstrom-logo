<?php

declare(strict_types=1);

namespace Drupal\tengstrom_logo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\tengstrom_logo\Factories\LogoElementFactory;
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
  protected EntityStorageInterface $imageStyleStorage;
  protected LogoElementFactory $logoElementFactory;

  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityStorageInterface $imageStyleStorage,
    LogoElementFactory $logoElementFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->imageStyleStorage = $imageStyleStorage;
    $this->logoElementFactory = $logoElementFactory;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('tengstrom_logo.logo_element_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return $this->logoElementFactory->create($config['image_style'], $config['link_path']);
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

}
