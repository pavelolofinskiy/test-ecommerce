<?php

use PHPUnit\Framework\TestCase;
use Src\Parser\XmlProductImporter;
use Src\Cache\RedisCache;


class XmlProductImporterTest extends TestCase
{
    public function testImportCallsExpectedDbAndCacheMethods()
    {
        // Мокаем RedisCache
        $redisMock = $this->createMock(RedisCache::class);
        $redisMock->expects($this->once())
            ->method('delete')
            ->with('filters_with_counts');

        // Мокаем PDO и PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(false);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('lastInsertId')->willReturn('123');

        // Временный XML
        $xml = <<<XML
<offers>
    <offer id="1">
        <name>Test Product</name>
        <price>99.99</price>
        <description>Test Description</description>
        <param name="Color">Red</param>
        <param name="Size">L</param>
    </offer>
</offers>
XML;

        $filePath = tempnam(sys_get_temp_dir(), 'xml');
        file_put_contents($filePath, $xml);

        // Анонимный класс с внедрением зависимостей через parent::__construct
        $importer = new class($pdoMock, $redisMock) extends XmlProductImporter {
            public function __construct($pdo, $cache)
            {
                parent::__construct($pdo, $cache);
            }

            public function import(string $xmlPath): void
            {
                ob_start();
                parent::import($xmlPath);
                ob_end_clean();
            }
        };

        $importer->import($filePath);
        unlink($filePath);

        $this->assertTrue(true); // если дошло до сюда — тест прошёл
    }
}