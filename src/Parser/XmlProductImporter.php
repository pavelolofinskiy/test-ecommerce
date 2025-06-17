<?php
namespace Src\Parser;

use DOMDocument;
use Src\Database\DB;
use PDO;
use Src\Cache\RedisCache;

/**
 * Класс для импорта товаров из XML-файла в базу данных.
 * Парсит XML, обновляет или вставляет товары, их параметры и очищает кеш фильтров.
 */
class XmlProductImporter
{
    private PDO $pdo;

    /**
     * Подключение к базе данных через PDO.
     */
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    /**
     * Импортирует товары из XML файла.
     *
     * @param string $xmlPath Путь к XML-файлу.
     * @return void
     */
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

            // Вставка или обновление товара
            $stmt = $this->pdo->prepare("
                INSERT INTO products (id, name, price, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price), description = VALUES(description)
            ");
            $stmt->execute([$id, $name, $price, $desc]);

            // Удаление старых параметров товара
            $this->pdo->prepare("DELETE FROM product_parameters WHERE product_id = ?")->execute([$id]);

            // Обработка параметров
            foreach ($offer->getElementsByTagName('param') as $param) {
                $paramName = trim($param->getAttribute('name'));
                $paramValue = trim($param->nodeValue);

                if (!$paramName || !$paramValue) continue;

                $paramSlug = $this->slugify($paramName);

                $parameterId = $this->getOrCreateParameter($paramName, $paramSlug);
                $valueId = $this->getOrCreateParameterValue($parameterId, $paramValue);

                // Связь товара с параметром
                $this->pdo->prepare("INSERT IGNORE INTO product_parameters (product_id, parameter_value_id) VALUES (?, ?)")
                    ->execute([$id, $valueId]);
            }
        }

        // Очистка кеша фильтров в Redis
        $cache = new RedisCache();
        $cache->delete('filters_with_counts');

        echo "Import completed successfully.\n";
    }

    /**
     * Получает значение первого дочернего элемента с заданным тегом.
     *
     * @param \DOMElement $element Элемент, в котором ищем тег.
     * @param string $tag Имя тега.
     * @return string|null Значение или null, если тег отсутствует.
     */
    private function getValue($element, string $tag): ?string
    {
        $tagElement = $element->getElementsByTagName($tag)->item(0);
        return $tagElement ? trim($tagElement->nodeValue) : null;
    }

    /**
     * Преобразует строку в slug: латиница, строчные буквы, дефисы.
     *
     * @param string $text Исходный текст.
     * @return string Сформированный slug.
     */
    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', transliterator_transliterate('Any-Latin; Latin-ASCII;', $text));
        return trim($text, '-');
    }

    /**
     * Получает ID параметра по slug или создаёт новый.
     *
     * @param string $name Название параметра.
     * @param string $slug Уникальный slug параметра.
     * @return int ID параметра.
     */
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

    /**
     * Получает ID значения параметра или создаёт новое.
     *
     * @param int $parameterId ID параметра.
     * @param string $value Значение параметра.
     * @return int ID значения параметра.
     */
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