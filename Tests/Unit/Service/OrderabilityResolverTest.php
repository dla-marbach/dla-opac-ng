<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service;

use Dla\DlaOpacNg\Service\OrderabilityResolver;
use PHPUnit\Framework\TestCase;

/**
 * Fälle A1-A4 und B1-B2 für den Bestellbutton bei Beiträgen ohne Exemplare.
 */
class OrderabilityResolverTest extends TestCase
{
    private const ARTICLE = [
        'categorySub' => 'Einzelbestandteil / unselbständiges Werk',
        'category' => 'Bibliotheksdokumente',
        'filterSource' => 'Bibliotheksmaterialien',
        'filterType_mv' => ['Gedrucktes'],
    ];

    private OrderabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderabilityResolver();
    }

    /**
     * @test
     */
    public function articleWithoutParentIsOrderable(): void
    {
        self::assertTrue($this->resolver->isOrderable(self::ARTICLE, []));
    }

    /**
     * @test
     */
    public function articleNotInHoldingsIsNotOrderable(): void
    {
        $fields = self::ARTICLE + ['callNumberBibliographic_mv' => ['kein Bestand']];

        self::assertFalse($this->resolver->isOrderable($fields, []));
    }

    /**
     * @test
     * @dataProvider notInHoldingsSignatures
     */
    public function articleNotInHoldingsIsNotOrderableForAllSignatureVariants(string $callNumber): void
    {
        $fields = self::ARTICLE + ['callNumberBibliographic_mv' => [$callNumber]];

        self::assertFalse($this->resolver->isOrderable($fields, []));
    }

    public static function notInHoldingsSignatures(): array
    {
        return [
            'kein Bestand' => ['kein Bestand'],
            'Kein Bestand' => ['Kein Bestand'],
            'in Klammern' => ['[kein Bestand]'],
            'nicht im Bestand' => ['nicht im Bestand des DLA'],
            'mit Präfix' => ['HNicht im Bestand des DLA'],
        ];
    }

    /**
     * @test
     */
    public function articleWithNotUsableNoteIsNotOrderable(): void
    {
        $fields = self::ARTICLE + ['usageRestrictionNote' => "nicht benutzbar\nnur bibliographischer Nachweis, nicht im Bestand des DLA!"];

        self::assertFalse($this->resolver->isOrderable($fields, []));
    }

    /**
     * @test
     */
    public function recordWithoutItemsThatIsNoArticleIsNotOrderable(): void
    {
        self::assertFalse($this->resolver->isOrderable(['categorySub' => 'Einzelne Einheit / nicht Teil eines Gesamtwerks'], []));
    }

    /**
     * @test
     */
    public function articleWithViewerLinkIsNotOrderable(): void
    {
        $fields = self::ARTICLE + ['digitalObject_hyperlink_mv' => ['https://digital.dla-marbach.de/viewer/image/AU01067549/']];

        self::assertFalse($this->resolver->isOrderable($fields, []));
    }

    /**
     * @test
     */
    public function articleWithOtherDigitalObjectLinkIsOrderable(): void
    {
        $fields = self::ARTICLE + ['digitalObject_hyperlink_mv' => ['https://doi.org/10.5209/rpub.103407']];

        self::assertTrue($this->resolver->isOrderable($fields, []));
    }

    /**
     * @test
     */
    public function electronicMediumArticleIsNotOrderable(): void
    {
        $fields = self::ARTICLE + ['filterMedium_mv' => ['E-Book', 'Beitrag']];

        self::assertFalse($this->resolver->isOrderable($fields, []));
    }

    /**
     * @test
     */
    public function articleWithNotOrderableParentIsNotOrderable(): void
    {
        $parent = ['filterMedium_mv' => ['Buch'], 'item_id_mv' => ['AU1'], 'item_usageRestriction_mv' => ['nicht benutzbar']];

        self::assertFalse($this->resolver->isOrderable(self::ARTICLE, [$parent]));
    }

    /**
     * @test
     */
    public function articleWithoutItemsInParentIsNotOrderable(): void
    {
        self::assertFalse($this->resolver->isOrderable(self::ARTICLE, [['filterMedium_mv' => ['Buch']]]));
    }

    /**
     * @test
     */
    public function articleInOrderableParentIsOrderable(): void
    {
        $parent = [
            'filterMedium_mv' => ['Buch'],
            'item_id_mv' => ['AU1', 'AU2'],
            'item_usageRestriction_mv' => ['nicht benutzbar', 'frei benutzbar'],
            'item_location_mv' => ['Magazin', 'Magazin'],
        ];

        self::assertTrue($this->resolver->isOrderable(self::ARTICLE, [$parent]));
    }

    /**
     * @test
     * @dataProvider continuingResourceMedia
     */
    public function retroArticleInJournalNewspaperOrSeriesIsOrderable(string $medium): void
    {
        self::assertTrue($this->resolver->isOrderable(self::ARTICLE, [['filterMedium_mv' => [$medium]]]));
    }

    public static function continuingResourceMedia(): array
    {
        return ['Zeitschrift' => ['Zeitschrift'], 'Zeitung' => ['Zeitung'], 'Reihe' => ['Reihe']];
    }

    /**
     * @test
     */
    public function articleInElectronicJournalIsNotOrderable(): void
    {
        $parent = ['filterMedium_mv' => ['Zeitschrift'], 'digitalObject_hyperlink_mv' => ['https://digital.dla-marbach.de/viewer/image/MM01846400/']];

        self::assertFalse($this->resolver->isOrderable(self::ARTICLE, [$parent]));
    }

    /**
     * @test
     */
    public function articleInElectronicJournalLibraryIsNotOrderable(): void
    {
        $parent = ['filterMedium_mv' => ['Zeitschrift'], 'filterSource' => 'Elektronische Zeitschriftenbibliothek'];

        self::assertFalse($this->resolver->isOrderable(self::ARTICLE, [$parent]));
    }
}
