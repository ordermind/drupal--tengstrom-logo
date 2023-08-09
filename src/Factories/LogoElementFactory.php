<?php

declare(strict_types=1);

namespace Drupal\tengstrom_logo\Factories;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\tengstrom_config_logo\LogoFileLoader;

class LogoElementFactory {
  protected EntityStorageInterface $imageStyleStorage;
  protected LogoFileLoader $logoFileLoader;
  protected ExtensionPathResolver $extensionPathResolver;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LogoFileLoader $logoFileLoader,
    ExtensionPathResolver $extensionPathResolver
  ) {
    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
    $this->logoFileLoader = $logoFileLoader;
    $this->extensionPathResolver = $extensionPathResolver;
  }

  public function create(?string $imageStyle, ?string $linkPath, bool $allowFallback): array {
    $logoUri = $this->getLogoFileUri($allowFallback);
    if (!$logoUri) {
      return [];
    }

    $imageElement = $this->createImageElement($imageStyle, $logoUri);

    if (empty($linkPath)) {
      return $imageElement;
    }

    return $this->createLinkElement($linkPath, $imageElement);
  }

  protected function createLinkElement(string $linkPath, array $imageElement) {
    return [
      '#type' => 'link',
      '#title' => $imageElement,
      '#url' => Url::fromRoute($linkPath),
    ];
  }

  protected function createImageElement(?string $imageStyleName, string $logoUri): array {
    if ($imageStyleName && substr($logoUri, 0, 9) === 'public://') {
      return $this->renderStyledImage($imageStyleName, $logoUri);
    }

    return $this->renderOriginalImage($logoUri);
  }

  protected function renderOriginalImage(string $logoUri): array {
    return [
      '#theme' => 'image',
      '#uri' => $logoUri,
    ];
  }

  protected function renderStyledImage(string $imageStyleName, string $logoUri): array {
    return [
      '#theme' => 'image_style',
      '#uri' => $logoUri,
      '#style_name' => $imageStyleName,
    ];
  }

  protected function getLogoFileUri(bool $allowFallback): ?string {
    $logoFile = $this->logoFileLoader->loadLogo();

    if ($logoFile instanceof FileInterface) {
      return $logoFile->getFileUri();
    }

    if (!$allowFallback) {
      return NULL;
    }

    return '/' . $this->extensionPathResolver->getPath('module', 'tengstrom_logo') . '/images/fallback-logo.png';
  }

}
