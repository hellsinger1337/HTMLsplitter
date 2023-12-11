<?php
class HtmlSplitter
{
    private static function splitHtmlContent($htmlContent): array
    {
        return explode('<br>', $htmlContent);
    }

    private static function mergeHtmlStringsWithoutBr($html1, $html2): string
    {
        // Ищем позицию закрывающего тега </body> в первом HTML
        $index_body1 = strpos($html1, '</body>');
        if ($index_body1 !== false) {
            // Если нашли, вставляем второй HTML перед </body> первого HTML
            $combined_html = substr_replace($html1, $html2, $index_body1, 0);
        } else {
            // Если </body> не найден, просто объединяем оба HTML
            $combined_html = $html1 . $html2;
        }
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($combined_html, 'HTML-ENTITIES', 'UTF-8'));

        // Устанавливаем кодировку для сохранения HTML
        $dom->encoding = 'UTF-8';

        // Получаем HTML с учетом заданной кодировки
        return $dom->saveHTML();
    }

    private static function countWordsInHTML($html1): int
    {
        // Преобразование HTML-сущностей, чтобы они не мешали подсчету слов
        $html = html_entity_decode($html1, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Удаление HTML-тегов
        $text = strip_tags($html, 0);

        // Разделение текста на слова
        $words = preg_split('/\s+/', $text);

        // Фильтрация пустых слов, &nbsp; и слов, состоящих только из пробелов
        $filteredWords = array_filter($words, function ($word) {
            return !empty($word) && $word !== '&nbsp;' && !ctype_space($word);
        });

        return count($filteredWords);
    }

    private static function optimizeHtmlArrayByLength($htmlArray, $wordsCount): array
    {
        $result = [];
        $Length = count($htmlArray);
        while ($Length > 2) {
            $Length = count($htmlArray);
            $wordsCountIn2LastHtml = self::countWordsInHTML($htmlArray[$Length - 1]) + self::countWordsInHTML($htmlArray[$Length - 2]);

            if ($wordsCountIn2LastHtml <= $wordsCount) {
                $htmlArray[$Length - 2] = self::mergeHtmlStringsWithoutBr($htmlArray[$Length - 2], $htmlArray[$Length - 1]);
            } else {
                $result[] = $htmlArray[$Length - 1];
            }
            unset($htmlArray[$Length - 1]);
        }
        if (count($htmlArray) === 1) {
            $result[] = $htmlArray[0];
        }
        return array_reverse($result);
    }

    private static function saveHtmlFiles($htmlArray, $folderPath): void
    {
        // Проверяем, существует ли указанная папка
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true); // Создаем папку, если она не существует
        }

        // Перебираем массив HTML-строк
        foreach ($htmlArray as $index => $htmlContent) {
            $fileName = $folderPath . '/file' . $index . '.html'; // Генерируем уникальное имя файла

            // Записываем HTML-строку в файл
            file_put_contents($fileName, $htmlContent);
        }
    }

    /**
     * Divides HTML content into pages with no more than the specified number of words and save to a folder.
     *
     * @param string $inputFile   The path to the input HTML file.
     * @param string $folderPath  The path to the output folder.
     * @param int    $wordsCount   The maximum number of words per page.
     */
    public static function splitHtmlByWordsCountToFolder(string $inputFile, string $folderPath, int $wordsCount): void
    {
        $htmlContent = file_get_contents($inputFile);

        $outputFolder = 'out1';
        if (!file_exists($outputFolder)) {
            mkdir($outputFolder, 0777, true);
        }
        $htmlArray = self::splitHtmlContent($htmlContent);

        $htmlArray = self::optimizeHtmlArrayByLength($htmlArray, $wordsCount);
        self::saveHtmlFiles($htmlArray, $folderPath);
    }

    /**
     * Divides HTML content into pages with no more than the specified number of words and returns an array
     *
     * @param string $inputFile   The path to the input HTML file.
     * @param int    $wordsCount   The maximum number of words per page.
     *
     * @return array The array of HTML strings.
     */
    public static function splitHtmlByWordsCountToArray(string $inputFile, int $wordsCount): array
    {
        $htmlContent = file_get_contents($inputFile);

        $outputFolder = 'out1';
        if (!file_exists($outputFolder)) {
            mkdir($outputFolder, 0777, true);
        }
        $htmlArray = self::splitHtmlContent($htmlContent);

        return self::optimizeHtmlArrayByLength($htmlArray, $wordsCount);
    }
}

// Пример использования
ini_set('memory_limit', '4560M');
$inputFile = 'content.html';
$outputFolder = 'splitedHtml';
if (!file_exists($outputFolder)) {
    mkdir($outputFolder, 0777, true);
}
$wordsCount = 1000;
HtmlSplitter::splitHtmlByWordsCountToFolder($inputFile, $outputFolder, $wordsCount);
