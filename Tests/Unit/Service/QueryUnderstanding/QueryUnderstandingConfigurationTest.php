<?php

namespace Dla\DlaOpacNg\Tests\Unit\Service\QueryUnderstanding;

use Dla\DlaOpacNg\Service\QueryUnderstanding\QueryUnderstandingConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryUnderstandingConfigurationTest extends UnitTestCase
{
    private const VARIABLES = ['LLM_BASE_URL', 'LLM_MODEL', 'LLM_API_KEY', 'LLM_TIMEOUT', 'LLM_RATE_LIMIT'];

    /** @var array<string, string|false> */
    private array $originalValues = [];

    protected function setUp(): void
    {
        parent::setUp();
        // In der DDEV-Umgebung sind LLM_* über .ddev/.env gesetzt; jeder Test beginnt mit leerer Konfiguration.
        foreach (self::VARIABLES as $name) {
            $this->originalValues[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalValues as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function readsValuesFromEnvironment(): void
    {
        putenv('LLM_BASE_URL=http://llm:11434/v1/');
        putenv('LLM_MODEL=qwen3:4b-instruct-2507-q8_0');
        putenv('LLM_TIMEOUT=7.5');
        putenv('LLM_RATE_LIMIT= 0 ');

        $configuration = QueryUnderstandingConfiguration::fromEnvironment();

        self::assertTrue($configuration->isEnabled());
        self::assertSame('http://llm:11434/v1', $configuration->baseUrl);
        self::assertSame('qwen3:4b-instruct-2507-q8_0', $configuration->model);
        self::assertSame(7.5, $configuration->timeout);
        self::assertSame(0, $configuration->rateLimitPerMinute, '0 schaltet das Rate Limit bewusst ab');
    }

    /**
     * @test
     */
    public function usesSafeDefaultsForMissingOrMalformedValues(): void
    {
        putenv('LLM_RATE_LIMIT=ten');
        putenv('LLM_TIMEOUT=-1');

        $configuration = QueryUnderstandingConfiguration::fromEnvironment();

        self::assertFalse($configuration->isEnabled());
        self::assertSame(QueryUnderstandingConfiguration::DEFAULT_MODEL, $configuration->model);
        self::assertSame(QueryUnderstandingConfiguration::DEFAULT_RATE_LIMIT, $configuration->rateLimitPerMinute);
        self::assertSame(QueryUnderstandingConfiguration::DEFAULT_TIMEOUT, $configuration->timeout);
    }
}
