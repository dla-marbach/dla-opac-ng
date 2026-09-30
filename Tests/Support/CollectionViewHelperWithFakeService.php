<?php

namespace Dla\DlaOpacNg\Tests\Support;

use Dla\DlaOpacNg\ViewHelpers\CollectionViewHelper;

/**
 * Wird per XCLASS anstelle des echten CollectionViewHelper instanziiert (siehe FluidPartialTestCase):
 * die Fluid-Umgebung im Unit-Test kann Konstruktor-Abhängigkeiten nicht per DI auflösen.
 */
class CollectionViewHelperWithFakeService extends CollectionViewHelper
{
    public function __construct()
    {
        parent::__construct(new FakeCollectionService());
    }
}
