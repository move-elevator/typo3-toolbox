<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Tests\Unit\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\Options\InvalidWidgetOptionsException;
use MoveElevator\Typo3Toolbox\Widget\Options\WidgetOptionsFactory;
use MoveElevator\Typo3Toolbox\Widget\Welcome\ContactCard;
use MoveElevator\Typo3Toolbox\Widget\Welcome\CustomCard;
use MoveElevator\Typo3Toolbox\Widget\Welcome\LinksCard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WelcomeOptionsTest extends UnitTestCase
{
    private WidgetOptionsFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new WidgetOptionsFactory();
    }

    #[Test]
    public function rendersWithZeroConfiguration(): void
    {
        $options = $this->factory->createWelcomeOptions([]);

        self::assertSame([], $options->cards);
        self::assertNull($options->intro);
        self::assertTrue($options->branding->enabled);
        self::assertNotSame('', $options->branding->logo);
        self::assertNotSame('', $options->branding->claim);
        self::assertSame('https://www.move-elevator.de/', $options->branding->url);
    }

    #[Test]
    public function brandingCanBeOverriddenAndDisabled(): void
    {
        $options = $this->factory->createWelcomeOptions([
            'branding' => [
                'enabled' => false,
                'logo' => 'EXT:acme/Resources/Public/logo.svg',
                'claim' => 'Acme',
                'url' => 'https://acme.example',
            ],
        ]);

        self::assertFalse($options->branding->enabled);
        self::assertSame('EXT:acme/Resources/Public/logo.svg', $options->branding->logo);
        self::assertSame('Acme', $options->branding->claim);
        self::assertSame('https://acme.example', $options->branding->url);
    }

    /**
     * An empty url keeps the claim as plain text instead of rendering a dead link.
     */
    #[Test]
    public function brandingLinkCanBeRemoved(): void
    {
        $options = $this->factory->createWelcomeOptions(['branding' => ['url' => '']]);

        self::assertNull($options->branding->url);
    }

    #[Test]
    public function hydratesContactCardAndBuildsChannelHrefs(): void
    {
        $options = $this->factory->createWelcomeOptions([
            'cards' => [
                [
                    'type' => 'contact',
                    'title' => 'Your contact',
                    'name' => 'Jane Doe',
                    'role' => 'Project lead',
                    'channels' => [
                        ['type' => 'email', 'value' => 'jane@example.com'],
                        ['type' => 'phone', 'value' => '+49 30 123 456'],
                    ],
                ],
            ],
        ]);

        $card = $options->cards[0];
        self::assertInstanceOf(ContactCard::class, $card);
        self::assertSame('Jane Doe', $card->name);
        self::assertSame('Project lead', $card->role);
        self::assertSame('mailto:jane@example.com', $card->channels[0]->getHref());
        // Spaces are not valid in a tel: URI and would break the link.
        self::assertSame('tel:+4930123456', $card->channels[1]->getHref());
    }

    #[Test]
    public function hydratesLinksCardWithUrlAndModuleTargets(): void
    {
        $options = $this->factory->createWelcomeOptions([
            'cards' => [
                [
                    'type' => 'links',
                    'links' => [
                        ['label' => 'Styleguide', 'url' => 'https://example.com/styleguide'],
                        ['label' => 'List view', 'module' => 'records', 'params' => ['id' => 1]],
                    ],
                ],
            ],
        ]);

        $card = $options->cards[0];
        self::assertInstanceOf(LinksCard::class, $card);
        self::assertSame('https://example.com/styleguide', $card->links[0]->url);
        self::assertNull($card->links[0]->module);
        self::assertSame('records', $card->links[1]->module);
        self::assertSame(['id' => '1'], $card->links[1]->parameters);
    }

    #[Test]
    public function hydratesCustomCard(): void
    {
        $options = $this->factory->createWelcomeOptions([
            'cards' => [['type' => 'custom', 'html' => '<p>Deployment freeze</p>']],
        ]);

        $card = $options->cards[0];
        self::assertInstanceOf(CustomCard::class, $card);
        self::assertSame('<p>Deployment freeze</p>', $card->html);
    }

    #[Test]
    public function unknownCardTypeFailsWithItsConfigPath(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('cards.0.type: unknown card type "fax"');

        $this->factory->createWelcomeOptions(['cards' => [['type' => 'fax']]]);
    }

    #[Test]
    public function unknownChannelTypeFailsWithItsConfigPath(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('cards.0.channels.1.type: unknown channel type "fax"');

        $this->factory->createWelcomeOptions([
            'cards' => [
                [
                    'type' => 'contact',
                    'name' => 'Jane Doe',
                    'channels' => [
                        ['type' => 'email', 'value' => 'jane@example.com'],
                        ['type' => 'fax', 'value' => '123'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function linkNeedsExactlyOneTarget(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('cards.0.links.0: a link requires exactly one of "url" or "module"');

        $this->factory->createWelcomeOptions([
            'cards' => [
                [
                    'type' => 'links',
                    'links' => [['label' => 'Broken', 'url' => 'https://example.com', 'module' => 'records']],
                ],
            ],
        ]);
    }

    #[Test]
    public function contactCardRequiresAName(): void
    {
        $this->expectException(InvalidWidgetOptionsException::class);
        $this->expectExceptionMessage('cards.0.name: is required and must not be empty');

        $this->factory->createWelcomeOptions(['cards' => [['type' => 'contact']]]);
    }
}
