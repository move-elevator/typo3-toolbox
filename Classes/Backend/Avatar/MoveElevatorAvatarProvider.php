<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Backend\Avatar;

use TYPO3\CMS\Backend\Backend\Avatar\AvatarProviderInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Image;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;

final readonly class MoveElevatorAvatarProvider implements AvatarProviderInterface
{
    private const string EMAIL_DOMAIN = '@move-elevator.de';
    private const string LOGO_PATH = 'EXT:typo3_toolbox/Resources/Public/Icons/me.svg';

    public function __construct(
        private SystemResourceFactory $systemResourceFactory,
        private SystemResourcePublisherInterface $resourcePublisher,
    ) {
    }

    /**
     * @param array<int|string, mixed> $backendUser
     */
    public function getImage(array $backendUser, mixed $size): ?Image
    {
        if ((int)($backendUser['avatar'] ?? 0) > 0) {
            return null;
        }

        $email = strtolower((string)($backendUser['email'] ?? ''));
        if ($email === '' || !str_ends_with($email, self::EMAIL_DOMAIN)) {
            return null;
        }

        $resource = $this->systemResourceFactory->createPublicResource(self::LOGO_PATH);

        return new Image(
            (string)$this->resourcePublisher->generateUri($resource, null),
            $size,
            $size,
        );
    }
}
