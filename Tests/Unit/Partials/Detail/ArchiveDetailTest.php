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
    private const IMAGE_SLIDESHOW = 'BI00005210';
    private const MANUSCRIPT_VIEWER = 'HS00242238';
    private const MANUSCRIPT_MIXED = 'HS01306880';

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

    /**
     * @test
     */
    public function imageDetailsRenderSlideshowWithUniqueIdInBothTabs(): void
    {
        // Details enthält über Order/Main auch den Reiter "Zugang" - beide zeigen das digitale Objekt
        $html = $this->renderDetailPartial('Display/Detail/ImagesAndObjects/Details', self::IMAGE_SLIDESHOW);

        self::assertSame(2, substr_count($html, 'data-dla-slideshow'));
        self::assertStringContainsString('id="renderlinks-' . self::IMAGE_SLIDESHOW . '-slideshow"', $html);
        self::assertStringContainsString('id="renderlinks-access-' . self::IMAGE_SLIDESHOW . '-slideshow"', $html);
        self::assertStringContainsString('<div class="dla-slideshow-caption">7270-2</div>', $html);

        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $duplicates = array_keys(array_filter(array_count_values($matches[1]), static fn (int $count): bool => $count > 1));
        self::assertSame([], array_values(array_filter($duplicates, static fn (string $id): bool => str_starts_with($id, 'renderlinks'))));
    }

    /**
     * @test
     */
    public function manuscriptViewerDigitisationShowsImageWithSignatureAndViewerAction(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Manuscripts/Details', self::MANUSCRIPT_VIEWER);

        // Vorschaubild oben springt zum Abschnitt "Digitales Objekt"
        self::assertStringContainsString('href="#digitales-objekt"', $html);
        self::assertStringContainsString('alt="To the digital object: Digitised copy"', $html);
        self::assertStringContainsString('id="digitales-objekt"', $html);
        // Reiter "Details" und "Zugang" zeigen das Digitalisat als Bild statt als Textlink
        self::assertSame(2, substr_count($html, 'class="dla-slideshow"'));
        self::assertSame(2, substr_count($html, '<div class="dla-slideshow-caption">' . self::MANUSCRIPT_VIEWER . '</div>'));
        self::assertSame(2, substr_count($html, 'class="dla-slideshow-action"'));
        self::assertStringContainsString('Open in viewer', $html);
        self::assertStringContainsString('src="https://digital.dla-marbach.de/viewer/api/v1/records/HS00242238/files/images/HS_00242238_0001.tif/full/!480,480/0/default.jpg"', $html);
        self::assertStringNotContainsString('access-denied-link', $html);
    }

    /**
     * @test
     */
    public function manuscriptWithImageAndViewerDigitisationOutsideCampusShowsOnlyRestrictionNotice(): void
    {
        $html = $this->renderDetailPartial('Display/Detail/Manuscripts/Details', self::MANUSCRIPT_MIXED);

        self::assertStringNotContainsString('data-dla-slideshow', $html);
        self::assertStringNotContainsString('href="#digitales-objekt"', $html);
        self::assertStringContainsString('class="access-denied"', $html);
        self::assertStringContainsString("<span class='access-denied-link'>D20240226-818 (jpg)</span>", $html);
        self::assertStringContainsString("<span class='access-denied-link'>HS01306880 </span>", $html);
    }

    /**
     * @test
     */
    public function manuscriptWithImageAndViewerDigitisationInsideCampusSharesOneSlideshow(): void
    {
        $previousRanges = getenv('campusRanges');
        $previousRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        putenv('campusRanges=10.0.0.0/8');
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        try {
            $html = $this->renderDetailPartial('Display/Detail/Manuscripts/Details', self::MANUSCRIPT_MIXED);
        } finally {
            putenv($previousRanges === false ? 'campusRanges' : 'campusRanges=' . $previousRanges);
            if ($previousRemoteAddr === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousRemoteAddr;
            }
        }

        self::assertStringContainsString('alt="To the digital object: Image, Digitised copy"', $html);
        self::assertSame(2, substr_count($html, 'class="dla-slideshow"'));
        // je Reiter zwei Slides, nur das Digitalisat im Viewer hat eine Aktionszeile
        self::assertSame(4, substr_count($html, 'class="dla-slideshow-slide"'));
        self::assertSame(2, substr_count($html, 'class="dla-slideshow-action"'));
        self::assertStringContainsString('<div class="dla-slideshow-caption">D20240226-818</div>', $html);
        self::assertStringContainsString('<div class="dla-slideshow-caption">HS01306880</div>', $html);
        self::assertStringNotContainsString('access-denied', $html);
    }
}
