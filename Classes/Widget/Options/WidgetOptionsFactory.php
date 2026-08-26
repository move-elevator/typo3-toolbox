<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;
use MoveElevator\Typo3Toolbox\Widget\Welcome\CardInterface;
use MoveElevator\Typo3Toolbox\Widget\Welcome\CardLink;
use MoveElevator\Typo3Toolbox\Widget\Welcome\Channel;
use MoveElevator\Typo3Toolbox\Widget\Welcome\ChannelType;
use MoveElevator\Typo3Toolbox\Widget\Welcome\ContactCard;
use MoveElevator\Typo3Toolbox\Widget\Welcome\CustomCard;
use MoveElevator\Typo3Toolbox\Widget\Welcome\LinksCard;

/**
 * Builds typed, validated option objects from the raw option arrays that the
 * dashboard passes to each widget.
 *
 * All validation errors are raised as {@see InvalidWidgetOptionsException} and
 * carry the exact config path of the offending value.
 */
final class WidgetOptionsFactory
{
    private const int DEFAULT_WARNING_THRESHOLD_DAYS = 180;
    private const string DEFAULT_WINDOW_FROM = '-1 year';
    private const string DEFAULT_WINDOW_TO = '+4 years';
    private const string DEFAULT_EMOJI = '👋';
    private const string DEFAULT_BRANDING_LOGO = 'EXT:typo3_toolbox/Resources/Public/Icons/me.svg';
    private const string DEFAULT_BRANDING_CLAIM = 'move:elevator';
    private const string DEFAULT_BRANDING_URL = 'https://www.move-elevator.de/';

    /**
     * @param array<string, mixed> $options
     */
    public function createEndOfLifeOptions(array $options, \DateTimeImmutable $now): EndOfLifeOptions
    {
        $reader = new OptionsReader($options);

        $components = [];
        foreach ($reader->children('components') as $component) {
            $components[] = new ComponentRequest(
                product: $component->requireString('product'),
                version: $component->requireString('version'),
                eltsContract: $component->bool('eltsContract', false),
                label: $component->string('label'),
            );
        }

        $window = $reader->child('timeWindow');

        return new EndOfLifeOptions(
            components: $components,
            timeWindow: new TimeWindow(
                $this->resolveDate($now, $window, 'from', self::DEFAULT_WINDOW_FROM),
                $this->resolveDate($now, $window, 'to', self::DEFAULT_WINDOW_TO),
            ),
            warningThresholdDays: max(0, $reader->int('warningThresholdDays', self::DEFAULT_WARNING_THRESHOLD_DAYS)),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createWelcomeOptions(array $options): WelcomeOptions
    {
        $reader = new OptionsReader($options);
        $branding = $reader->child('branding');

        $cards = [];
        foreach ($reader->children('cards') as $card) {
            $cards[] = $this->createCard($card);
        }

        return new WelcomeOptions(
            emoji: $reader->string('emoji', self::DEFAULT_EMOJI),
            intro: $reader->string('intro'),
            branding: new Branding(
                enabled: $branding->bool('enabled', true),
                logo: $branding->string('logo', self::DEFAULT_BRANDING_LOGO) ?? self::DEFAULT_BRANDING_LOGO,
                claim: $branding->string('claim', self::DEFAULT_BRANDING_CLAIM) ?? self::DEFAULT_BRANDING_CLAIM,
                url: $branding->string('url', self::DEFAULT_BRANDING_URL) ?: null,
            ),
            cards: $cards,
        );
    }

    private function createCard(OptionsReader $card): CardInterface
    {
        $type = $card->requireString('type');

        return match ($type) {
            'contact' => new ContactCard(
                name: $card->requireString('name'),
                role: $card->string('role'),
                image: $card->string('image'),
                channels: $this->createChannels($card),
                title: $card->string('title'),
            ),
            'links' => new LinksCard(
                links: $this->createCardLinks($card),
                title: $card->string('title'),
            ),
            'custom' => new CustomCard(
                html: $card->requireString('html'),
                title: $card->string('title'),
            ),
            default => $card->fail('type', sprintf('unknown card type "%s"', $type)),
        };
    }

    /**
     * @return list<Channel>
     */
    private function createChannels(OptionsReader $card): array
    {
        $channels = [];
        foreach ($card->children('channels') as $channel) {
            $type = $channel->requireString('type');
            $channelType = ChannelType::tryFrom($type);
            if ($channelType === null) {
                $channel->fail('type', sprintf('unknown channel type "%s"', $type));
            }

            $channels[] = new Channel(
                type: $channelType,
                value: $channel->requireString('value'),
                label: $channel->string('label'),
            );
        }

        return $channels;
    }

    /**
     * @return list<CardLink>
     */
    private function createCardLinks(OptionsReader $card): array
    {
        $links = [];
        foreach ($card->children('links') as $link) {
            $present = $link->present(['url', 'module']);
            if (count($present) !== 1) {
                $link->failSelf('a link requires exactly one of "url" or "module"');
            }

            $links[] = new CardLink(
                label: $link->requireString('label'),
                url: $present[0] === 'url' ? $link->requireString('url') : null,
                module: $present[0] === 'module' ? $link->requireString('module') : null,
                parameters: $link->stringMap('params'),
                iconIdentifier: $link->string('icon'),
            );
        }

        return $links;
    }

    private function resolveDate(
        \DateTimeImmutable $now,
        OptionsReader $window,
        string $key,
        string $default,
    ): \DateTimeImmutable {
        $spec = $window->string($key, $default) ?? $default;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $spec) === 1) {
            $absolute = \DateTimeImmutable::createFromFormat('!Y-m-d', $spec);
            if ($absolute === false || $absolute->format('Y-m-d') !== $spec) {
                $window->fail($key, sprintf('"%s" is not a valid date', $spec));
            }

            return $absolute;
        }

        try {
            return $now->modify($spec);
        } catch (\DateMalformedStringException) {
            $window->fail($key, sprintf('"%s" is not a valid relative date', $spec));
        }
    }
}
