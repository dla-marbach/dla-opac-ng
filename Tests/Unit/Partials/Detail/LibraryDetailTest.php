<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;
use Dla\DlaOpacNg\Tests\Support\SolrFixture;
use Solarium\QueryType\Select\Result\Document;

/**
 * Display/Detail/Library/Details, Access und RightColumn: ein Aufsatz ohne Exemplare (AK01600951) und
 * ein Buch mit drei Exemplaren inkl. Provenienz und Digitalisat (AK00983699). Namen der Provenienz-Personen
 * und die "Vom selben Urheber/Bestand"-Listen kommen aus Unterabfragen (dla:fromSolr).
 */
class LibraryDetailTest extends FluidPartialTestCase
{
    private const ARTICLE = 'AK01600951';
    private const BOOK_WITH_ITEMS = 'AK00983699';

    /**
     * @test
     */
    public function rendersArticleDetailsWithoutItems(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/Details', self::ARTICLE));

        self::assertStringContainsString('Media type Gedrucktes Medium Beitrag', $text);
        self::assertStringContainsString('Creator Stolterfoht, Ulf (1963-) [Verfasser/Urheber]', $text);
        self::assertStringContainsString('Featured in Jahrbuch / Deutsche Akademie für Sprache und Dichtung. - 2013/14 (2015)', $text);
        self::assertStringNotContainsString('Exemplar Provenienz', $text);
    }

    /**
     * @test
     */
    public function rendersBookDetailsWithWorkAndDigitalObject(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/Details', self::BOOK_WITH_ITEMS));

        self::assertStringContainsString('Critik der Urtheilskraft Kant, Immanuel (1724-1804)', $text);
        self::assertStringContainsString('Title Critik der Urtheilskraft / von Immanuel Kant', $text);
        self::assertStringContainsString('Work Kant, Immanuel (1724-1804). Kritik der Urteilskraft (1790)', $text);
        self::assertStringContainsString('Digital object Digitalisat: Zugang', $text);
    }

    /**
     * @test
     */
    public function accessListsItemsWithoutProvenanceBeforeProvenanceItems(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/Access', self::BOOK_WITH_ITEMS));

        self::assertStringContainsString('Exemplar Provenienz Signatur / Details', $text);
        self::assertStringContainsString('Signature Schiller-Bibl. I/Kant Access Number 32347 (Archiv)', $text);
        self::assertStringContainsString('Previous owner Schiller, Friedrich von (1759-1805) Schiller, Ernst von (1796-1841)', $text);
        self::assertStringContainsString('Signature Kowa2 Access Number G89.2131 Previous owner Kowalewski, Arnold Christian (1873-1945)', $text);

        $withoutProvenance = strpos($text, 'Signature R ');
        $schiller = strpos($text, 'Signature Schiller-Bibl. I/Kant');
        self::assertNotFalse($withoutProvenance);
        self::assertNotFalse($schiller);
        self::assertLessThan($schiller, $withoutProvenance);
    }

    /**
     * @test
     */
    public function accessOfArticleOffersOnlyOrdering(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/Access', self::ARTICLE));

        self::assertSame('Order now', $text);
    }

    /**
     * Fälle A1-A4 und B1-B2 des Bestellbuttons bei Beiträgen (Cassetten detail-article-*, detail-ebook-article-*).
     *
     * @test
     * @dataProvider articleOrderButtons
     */
    public function accessOfArticleShowsOrderButtonOnlyWhenOrderable(string $id, bool $orderable): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/Access', $id));

        self::assertSame($orderable, str_contains($text, 'Order now'), $text);
        self::assertSame(!$orderable, str_contains($text, 'Not orderable'), $text);
    }

    public static function articleOrderButtons(): array
    {
        return [
            'A1 Überordnung nicht ausleihbar' => ['AK01823692', false],
            'A2 ohne Überordnung' => ['AK01726262', true],
            'A3 kein Bestand' => ['AK00423511', false],
            'A4 Retro-Beitrag, Überordnung Zeitschrift' => ['AK00944927', true],
            'B1 E-Beitrag, Überordnung mit Digitalem Objekt' => ['AK01846433', false],
            'B1 Beitrag, Überordnung Zeitschrift und digitalisiertes Heft' => ['AK01827413', false],
            'B1 E-Beitrag mit MM-Satz' => ['AK01846411', false],
            'B2 E-Beitrag ohne Überordnung mit MM-Satz' => ['AK01890275', false],
        ];
    }

    /**
     * @test
     */
    public function accessOfArticleWithConditionalUsageNoteShowsOrangeIndicator(): void
    {
        $variables = SolrFixture::detailVariables('AK01726262');
        $variables['document'] = new Document(array_merge($variables['document']->getFields(), ['usageRestrictionNote' => 'bedingt benutzbar']));
        $html = $this->renderPartial('Display/Detail/Library/Access', $variables);

        self::assertStringContainsString('Order now', self::visibleText($html));
        self::assertStringContainsString('#df661e', $html);
        self::assertStringNotContainsString('#00df27', $html);
    }

    /**
     * @test
     */
    public function rightColumnListsRelatedRecordsFromSameAuthorAndInventory(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Library/RightColumn', self::BOOK_WITH_ITEMS));

        self::assertStringContainsString('From the same author Von der Macht des…', $text);
        self::assertStringContainsString('From the same inventor G:Schiller-Bibliothek I…', $text);
        self::assertSame(2, substr_count($text, 'Show all'));
    }
}
