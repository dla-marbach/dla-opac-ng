<?php

namespace Dla\DlaOpacNg\Tests\Unit\Partials\Detail;

use Dla\DlaOpacNg\Tests\Support\FakeCollectionService;
use Dla\DlaOpacNg\Tests\Support\FluidPartialTestCase;

/**
 * Display/Detail/Inventory/*, Manuscripts/* und ImagesAndObjects/* (Bestände, Handschriften, Bilder und
 * Objekte). Der Bestandsbaum (dla:collection, Datenbank) wird über FakeCollectionService ersetzt.
 */
class ArchiveDetailTest extends FluidPartialTestCase
{
    private const INVENTORY = 'BF00025111';
    private const MANUSCRIPT = 'HS00067821';
    private const IMAGE = 'BI00028331';

    /**
     * @test
     */
    public function inventoryDetailsShowMasterDataAndSubinventoryCount(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Inventory/Details', self::INVENTORY));

        self::assertStringContainsString('COTTA:Briefe - [Bestand des Cotta-Archivs, Archiv]', $text);
        self::assertStringContainsString('Type of Holding Archiv , Bestand des Cotta-Archivs', $text);
        self::assertStringContainsString('Signature COTTA:Briefe Extent 583 Kästen', $text);
        self::assertStringContainsString('Titles In diesem Bestand 39424 Einzelobjekte', $text);
        self::assertStringNotContainsString('Unterbestände', $text);
    }

    /**
     * @test
     */
    public function inventoryDetailsShowCollectionBreadcrumbWhenAssigned(): void
    {
        FakeCollectionService::$parents[self::INVENTORY] = [[
            'record_id' => 'ROOT',
            'uid' => '1',
            'displayTree' => 'Handschriften',
            'child' => ['record_id' => self::INVENTORY, 'uid' => '7', 'displayTree' => 'COTTA:Briefe'],
        ]];

        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Inventory/Details', self::INVENTORY));

        self::assertStringContainsString('Handschriften -> COTTA:Briefe', $text);
    }

    /**
     * @test
     */
    public function inventoryAccessAndRightColumn(): void
    {
        $access = self::visibleText($this->renderDetailPartial('Display/Detail/Inventory/Access', self::INVENTORY));
        $rightColumn = self::visibleText($this->renderDetailPartial('Display/Detail/Inventory/RightColumn', self::INVENTORY));

        self::assertStringContainsString('Not orderable User Advice Am Standort', $access);
        self::assertStringContainsString('Category feingeordnet (568 Kästen) geordnet (15 Kästen)', $access);
        self::assertStringContainsString('From the same inventor COTTA:Verträge - [Besta…', $rightColumn);
    }

    /**
     * @test
     */
    public function manuscriptDetailsShowLetterMetadataAndHoldings(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/Manuscripts/Details', self::MANUSCRIPT));

        self::assertStringContainsString('Heusinger, Gerda an Zuckmayer, Carl [Briefe]', $text);
        self::assertStringContainsString('From Heusinger, Gerda [Verfasser/in] To Zuckmayer, Carl (1896-1977) [Adressat/in]', $text);
        self::assertStringContainsString('Period of Origin 1956 Place of Origin Osnabrück Extent, Accomp', $text);
        self::assertStringContainsString('Holdings Call Number A:Zuckmayer, Carl Access Number HS.1995.0001', $text);
    }

    /**
     * @test
     */
    public function manuscriptAccessAndRightColumn(): void
    {
        $access = self::visibleText($this->renderDetailPartial('Display/Detail/Manuscripts/Access', self::MANUSCRIPT));
        $rightColumn = self::visibleText($this->renderDetailPartial('Display/Detail/Manuscripts/RightColumn', self::MANUSCRIPT));

        self::assertStringContainsString('Order now', $access);
        self::assertStringContainsString('User Advice Nur mit Einverständnis der beteiligten Personen ausleihbar!', $access);
        self::assertStringContainsString('From the same inventor A:Zuckmayer, Carl - [Be…', $rightColumn);
    }

    /**
     * @test
     */
    public function imageDetailsShowObjectMetadataAndRelatedManuscript(): void
    {
        $text = self::visibleText($this->renderDetailPartial('Display/Detail/ImagesAndObjects/Details', self::IMAGE));

        self::assertStringContainsString('Porträt Emma Haaser [Photographie] Unbekannt 1915 oder etwas früher', $text);
        self::assertStringContainsString('Object type Photographie Title Porträt Emma Haaser', $text);
        self::assertStringContainsString('Dimension [in cm] 10,0 (Höhe) x 7,2 (Breite)', $text);
        self::assertStringContainsString('Manuscripts Haaser, Emma an Wagner, Christian [Briefe]', $text);
    }

    /**
     * @test
     */
    public function imageAccessAndRightColumn(): void
    {
        $access = self::visibleText($this->renderDetailPartial('Display/Detail/ImagesAndObjects/Access', self::IMAGE));
        $rightColumn = self::visibleText($this->renderDetailPartial('Display/Detail/ImagesAndObjects/RightColumn', self::IMAGE));

        self::assertStringContainsString('User Advice bedingt benutzbar', $access);
        self::assertStringContainsString('From the same artist Proklamation des preußischen…', $rightColumn);
        self::assertStringContainsString('Similar motives No hits found', $rightColumn);
    }
}
