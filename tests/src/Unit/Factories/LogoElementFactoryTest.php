<?php

declare(strict_types=1);

namespace Drupal\Tests\tengstrom_logo\Unit\Factories;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\tengstrom_config_logo\LogoFileLoader;
use Drupal\tengstrom_logo\Factories\LogoElementFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LogoElementFactoryTest extends UnitTestCase {
  use ProphecyTrait;

  /**
   * @dataProvider provideCreateCases
   */
  public function testCreate(
    array $expectedResult,
    bool $hasUploadedLogo,
    bool $allowFallback,
    ?string $imageStyle,
    ?string $linkPath
  ): void {
    $mockImageStyleStorage = $this->prophesize(EntityStorageInterface::class);
    $imageStyleStorage = $mockImageStyleStorage->reveal();

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getStorage('image_style')->willReturn($imageStyleStorage);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockLogoFileLoader = $this->prophesize(LogoFileLoader::class);
    if ($hasUploadedLogo) {
      $mockLogo = $this->prophesize(FileInterface::class);
      $mockLogo->getFileUri()->willReturn('public://uploaded_file.png');
      $logo = $mockLogo->reveal();
      $mockLogoFileLoader->loadLogo()->willReturn($logo);
    }
    else {
      $mockLogoFileLoader->loadLogo()->willReturn(NULL);
    }
    $logoFileLoader = $mockLogoFileLoader->reveal();

    $mockExtensionPathResolver = $this->prophesize(ExtensionPathResolver::class);
    $mockExtensionPathResolver->getPath('module', 'tengstrom_logo')->willReturn('modules/tengstrom/tengstrom_logo');
    $extensionPathResolver = $mockExtensionPathResolver->reveal();

    $factory = new LogoElementFactory($entityTypeManager, $logoFileLoader, $extensionPathResolver);
    $result = $factory->create($imageStyle, $linkPath, $allowFallback);
    $this->assertEquals($expectedResult, $result);
  }

  public function provideCreateCases(): array {
    return [
      [
        [], FALSE, FALSE, NULL, NULL,
      ],
      [
        [], FALSE, FALSE, NULL, '<front>',
      ],
      [
        [], FALSE, FALSE, 'valid_style', NULL,
      ],
      [
        [], FALSE, FALSE, 'valid_style', '<front>',
      ],
      // Fallback image without image style and link.
      [
        [
          '#theme' => 'image',
          '#uri' => '/modules/tengstrom/tengstrom_logo/images/fallback-logo.png',
        ], FALSE, TRUE, NULL, NULL,
      ],
      // Fallback image without image style but with a link.
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => '/modules/tengstrom/tengstrom_logo/images/fallback-logo.png',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], FALSE, TRUE, NULL, '<front>',
      ],
      // Fallback image with image style but without link (image style should not be used)
      [
        [
          '#theme' => 'image',
          '#uri' => '/modules/tengstrom/tengstrom_logo/images/fallback-logo.png',
        ], FALSE, TRUE, 'valid_style', NULL,
      ],
      // Fallback image with image style and with link (image style should not be used)
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => '/modules/tengstrom/tengstrom_logo/images/fallback-logo.png',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], FALSE, TRUE, 'valid_style', '<front>',
      ],
      // Uploaded image without image style and link. (No fallback allowed)
      [
        [
          '#theme' => 'image',
          '#uri' => 'public://uploaded_file.png',
        ], TRUE, FALSE, NULL, NULL,
      ],
      // Uploaded image without image style but with a link. (No fallback allowed)
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://uploaded_file.png',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], TRUE, FALSE, NULL, '<front>',
      ],
      // Uploaded image with image style but without link. (No fallback allowed)
      [
        [
          '#theme' => 'image_style',
          '#uri' => 'public://uploaded_file.png',
          '#style_name' => 'valid_style',
        ], TRUE, FALSE, 'valid_style', NULL,
      ],
      // Uploaded image with image style and with a link. (No fallback allowed)
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image_style',
            '#uri' => 'public://uploaded_file.png',
            '#style_name' => 'valid_style',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], TRUE, FALSE, 'valid_style', '<front>',
      ],
      // Uploaded image without image style and link. (Fallback allowed)
      [
        [
          '#theme' => 'image',
          '#uri' => 'public://uploaded_file.png',
        ], TRUE, TRUE, NULL, NULL,
      ],
      // Uploaded image without image style but with a link. (Fallback allowed)
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://uploaded_file.png',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], TRUE, TRUE, NULL, '<front>',
      ],
      // Uploaded image with image style but without link. (Fallback allowed)
      [
        [
          '#theme' => 'image_style',
          '#uri' => 'public://uploaded_file.png',
          '#style_name' => 'valid_style',
        ], TRUE, TRUE, 'valid_style', NULL,
      ],
      // Uploaded image with image style and with a link. (Fallback allowed)
      [
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image_style',
            '#uri' => 'public://uploaded_file.png',
            '#style_name' => 'valid_style',
          ],
          '#url' => Url::fromRoute('<front>'),
        ], TRUE, TRUE, 'valid_style', '<front>',
      ],
    ];
  }

}
