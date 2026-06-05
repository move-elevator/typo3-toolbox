<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Backend\Avatar;

use TYPO3\CMS\Backend\Backend\Avatar\AvatarProviderInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Image;
use TYPO3\CMS\Core\Utility\PathUtility;

final class MoveElevatorAvatarProvider implements AvatarProviderInterface
{
    private const string EMAIL_DOMAIN = '@move-elevator.de';
    private const string LOGO_PATH = 'EXT:typo3_toolbox/Resources/Public/Icons/me.svg';

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

        return new Image(
            PathUtility::getPublicResourceWebPath(self::LOGO_PATH),
            $size,
            $size,
        );
    }
}
