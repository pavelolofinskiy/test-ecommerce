<?php
namespace Src\Parser;

use DOMDocument;
use Src\Database\DB;
use PDO;
use Src\Cache\RedisCache;

class XmlProductImporter
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function import(string $xmlPath): void
    {
        echo "Loading XML...\n";
        $dom = new DOMDocument();
        $dom->load($xmlPath);

        $offers = $dom->getElementsByTagName('offer');
        echo "Found {$offers->length} offers.\n";

        foreach ($offers as $offer) {
            $id = $offer->getAttribute('id');
            $name = $this->getValue($offer, 'name');
            $priceRaw = $this->getValue($offer, 'price');
            $price = str_replace(',', '.', $priceRaw);

            $desc = $this->getValue($offer, 'description');

            if (!$id || !$name || !$price) {
                echo "Skipping product with missing fields: ID=$id, Name=$name, Price=$price\n";
                continue;
            }

            // Вставити або оновити продукт
            $stmt = $this->pdo->prepare("
                INSERT INTO products (id, name, price, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), description = VALUES(description)
            ");
            $stmt->execute([$id, $name, $price, $desc]);

            // Видалимо старі параметри
            $this->pdo->prepare("DELETE FROM product_parameters WHERE product_id = ?")->execute([$id]);

            // Обробка параметрів
            foreach ($offer->getElementsByTagName('param') as $param) {
                $paramName = trim($param->getAttribute('name'));
                $paramValue = trim($param->nodeValue);

                if (!$paramName || !$paramValue) continue;

                $paramSlug = $this->slugify($paramName);

                $parameterId = $this->getOrCreateParameter($paramName, $paramSlug);
                $valueId = $this->getOrCreateParameterValue($parameterId, $paramValue);

                // Зв'язок товару з параметром
                $this->pdo->prepare("INSERT IGNORE INTO product_parameters (product_id, parameter_value_id) VALUES (?, ?)")
                    ->execute([$id, $valueId]);
            }
        }

        $cache = new RedisCache();
        $cache->delete('filters_with_counts');

        echo "Import completed successfully.\n";
    }

    private function getValue($element, string $tag): ?string
    {
        $tagElement = $element->getElementsByTagName($tag)->item(0);
        return $tagElement ? trim($tagElement->nodeValue) : null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', transliterator_transliterate('Any-Latin; Latin-ASCII;', $text));
        return trim($text, '-');
    }

    private function getOrCreateParameter(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM parameters WHERE slug = ?");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        if ($id) return $id;

        $stmt = $this->pdo->prepare("INSERT INTO parameters (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);

        return (int)$this->pdo->lastInsertId();
    }

    private function getOrCreateParameterValue(int $parameterId, string $value): int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM parameter_values WHERE parameter_id = ? AND value = ?");
        $stmt->execute([$parameterId, $value]);
        $id = $stmt->fetchColumn();

        if ($id) return $id;

        $stmt = $this->pdo->prepare("INSERT INTO parameter_values (parameter_id, value) VALUES (?, ?)");
        $stmt->execute([$parameterId, $value]);

        return (int)$this->pdo->lastInsertId();
    }
}