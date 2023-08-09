<?php

declare(strict_types=1);

namespace Drupal\tengstrom_logo\Factories;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\tengstrom_config_logo\LogoFileLoader;

class LogoElementFactory {
  protected EntityStorageInterface $imageStyleStorage;
  protected LogoFileLoader $logoFileLoader;
  protected ExtensionPathResolver $extensionPathResolver;
  protected PublicStream $publicStream;

  public function __construct(
    EntityTypeManager $entityTypeManager,
    LogoFileLoader $logoFileLoader,
    ExtensionPathResolver $extensionPathResolver,
    PublicStream $publicStream
  ) {
    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
    $this->logoFileLoader = $logoFileLoader;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->publicStream = $publicStream;
  }

  public function create(?string $imageStyle, ?string $linkPath): array {
    $logoUri = $this->getLogoFileUri();
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
    if ($imageStyleName && substr($logoUri, 0, 10) === 'public://') {
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

  protected function getLogoFileUri(): string {
    $logoFile = $this->logoFileLoader->loadLogo();

    if ($logoFile instanceof FileInterface) {
      return $logoFile->getFileUri();
    }

    return Url::fromUri('base:' . $this->extensionPathResolver->getPath('module', 'tengstrom_logo') . '/images/fallback-logo.png')->toString();
  }

}
