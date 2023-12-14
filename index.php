
<?php
class HtmlSplitter
{
    //region splitByTags
    private static function extractBodyContent($html) {
        // Регулярное выражение для извлечения содержимого между тегами <body>...</body>
        $pattern = '/<body.*?>(.*?)<\/body>/is';

        // Используем preg_match, чтобы найти содержимое между тегами <body>...</body>
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        } else {
            return "";
        }
    }
    /**
     * Разделяет HTML-контент на строки по тегам.
     *
     * @param string $htmlString - HTML-контент.
     * @return array - Массив массивов, каждый из которых содержит информацию о теге и(если есть) тексте строки.
     */
    public static function splitByTags(string $htmlString):array {
        $htmlString = self::replaceSpecialCharsInAttributes($htmlString);
        // Используем регулярное выражение для разделения по HTML-тегам
        preg_match_all('/(<[^>]+>)([^<]*)|([^<]+)/', $htmlString, $matches, PREG_SET_ORDER);

        // Возвращаем результат
        return $matches;
    }
    public static function replaceSpecialCharsInAttributes($html) {
        // Создаем новый объект DOMDocument
        $dom = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);

        $html = mb_convert_encoding($html , 'HTML-ENTITIES', 'UTF-8');
        $html = preg_replace('/<([^a-zA-Z\/!])/', '@@@TEMP_LESS_THAN@@@', $html);
        // Загружаем HTML-код в DOMDocument с использованием флагов LIBXML_HTML_NOIMPLIED и LIBXML_HTML_NODEFDTD
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);

        // Создаем объект DOMXPath
        $xpath = new DOMXPath($dom);

        // Используем XPath для выбора всех атрибутов с текстовыми значениями
        $attributes = $xpath->query('//@*');
        // Обходим все атрибуты и заменяем символы < и > в значениях
        foreach ($attributes as $attribute) {
            $attribute->value = str_replace(['<', '>'], ['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], $attribute->value);
        }
        $comments = $xpath->query('//comment()');
        // Обходим все комментарии и заменяем символы < и > в их содержимом
        foreach ($comments as $comment) {
            $comment->nodeValue = str_replace(['<', '>'], ['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], $comment->nodeValue);
        }
        //Обходим все узлы с текстовым содержанием и заменяем символы < и > в тексте
        $textNodes = $xpath->query('//text()');
        foreach ($textNodes as $textNode) {
            $textNode->nodeValue = str_replace(['<', '>','&lt;', '&gt;'], ['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@','@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'],  $textNode->textContent);
        }
        // Обработка содержимого тегов <script> и <style>
        $scriptNodes = $xpath->query('//script');
        foreach ($scriptNodes as $scriptNode) {
            $scriptNode->nodeValue = str_replace(['<', '>'], ['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], $scriptNode->textContent);
        }

        $styleNodes = $xpath->query('//style');
        foreach ($styleNodes as $styleNode) {
            $styleNode->nodeValue = str_replace(['<', '>'], ['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], $styleNode->textContent);
        }

        $editedHtml = $dom->saveHTML();

        // Устанавливаем кодировку UTF-8 перед сохранением
        $editedHtml = mb_convert_encoding($editedHtml, 'UTF-8', 'HTML-ENTITIES');

        return $editedHtml;
    }

    private static function restoreSpecialCharsInAttributes($html) {
        $dom = new DOMDocument('1.0', 'UTF-8');

        // Временно отключаем вывод ошибок
        libxml_use_internal_errors(true);

        $html = mb_convert_encoding($html , 'HTML-ENTITIES', 'UTF-8');
        // Загружаем HTML-код в DOMDocument с использованием флагов LIBXML_HTML_NOIMPLIED и LIBXML_HTML_NODEFDTD
        $dom->loadHTML($html);

        libxml_use_internal_errors(false);
        // Создаем объект DOMXPath
        $xpath = new DOMXPath($dom);

        // Используем XPath для выбора всех атрибутов с текстовыми значениями
        $attributes = $xpath->query('//@*');

        // Обходим все атрибуты и восстанавливаем символы < и > в значениях
        foreach ($attributes as $attribute) {

            $attribute->value = str_replace(['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], ['<', '>'], $attribute->value);
        }
        $comments = $xpath->query('//comment()');
        // Обходим все комментарии и заменяем символы < и > в их содержимом
        foreach ($comments as $comment) {
            $comment->nodeValue = str_replace(['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], ['<', '>'], $comment->nodeValue);
        }

        $textNodes = $xpath->query('//text()');
        foreach ($textNodes as $textNode) {
            $textNode->nodeValue = str_replace(['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], ['&lt;', '&gt;'], $textNode->nodeValue);
        }

        // Обработка содержимого тегов <script> и <style>
        $scriptNodes = $xpath->query('//script');
        foreach ($scriptNodes as $scriptNode) {
            $scriptNode->nodeValue = str_replace(['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], ['<', '>'], $scriptNode->textContent);
        }

        $styleNodes = $xpath->query('//style');
        foreach ($styleNodes as $styleNode) {
            $styleNode->nodeValue = str_replace(['@@@TEMP_LESS_THAN@@@', '@@@TEMP_MORE_THAN@@@'], ['<', '>'],  $styleNode->textContent);
        }
        // Устанавливаем кодировку UTF-8 перед сохранением
        $editedHtml =self::extractBodyContent(mb_convert_encoding($dom->saveHTML() , 'UTF-8', 'HTML-ENTITIES'));
        return $editedHtml;
    }
    //endregion

    /**
     * Подсчитывает количество слов в HTML-контенте.
     *
     * @param string $html1 - HTML-контент.
     * @return int - Количество слов в HTML-контенте.
     */
    private static function countWordsInHtml(string $html1): int
    {
        // Преобразование HTML-сущностей для корректного подсчета слов
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
    private static function getFirstWords(&$inputString, $count): string {
        // Используем preg_match_all для поиска всех слов в строке, включая несколько пробелов между ними
        preg_match_all('/\S+\s*/', $inputString, $matches);

        // Получаем массив слов
        $words = $matches[0];

        // Переменная для хранения результата
        $result = '';

        // Переменная для отслеживания количества слов
        $wordCount = 0;

        // Проходим по каждому слову
        foreach ($words as $word) {
            // Добавляем слово к результату
            $result .= $word;

            // Проверяем, содержится ли в слове знак препинания
            $punctuation = strpbrk($word, '.!?');

            // Если знак препинания найден, прекращаем добавление слов
            if ($punctuation !== false) {
                break;
            }

            // Увеличиваем счетчик слов
            $wordCount++;

            // Если достигнуто необходимое количество слов, прекращаем добавление слов
            if ($wordCount == $count) {
                break;
            }
        }

        // Обновляем входную строку, удаляя использованные слова
        $inputString = substr($inputString, strlen($result));

        // Возвращаем результат
        return $result;
    }



    /**
     * Обрезает HTML-контент внутри тегов и по заданной длине.
     *
     * @param int $minPageLength - Минимальная длина страницы.
     * @param int $maxPageLength - Максимальная длина страницы.
     * @param array $lines - Массив строк HTML-контента.
     * @param bool $split - Флаг, указывающий на разделение на подстраницы.
     * @return array|string - Обрезанный HTML-контент или массив подстраниц.
     */
    private static function trimHtmlContentWithinTagsAndLength(array &$open_tags,int $minPageLength,int $maxPageLength,array &$lines,bool $split = false): array|string
    {
        $truncate = '';               // Итоговый обрезанный HTML-контент
        foreach ($open_tags as $open_tag) {
            $truncate = $open_tag . $truncate;
        }
        $total_length = 0;// Общая длина контента
        $indexCounter=0;
        // Проход по строкам HTML-контента
        foreach ($lines as $index =>$this_elem) {
            // Проверка наличия открывающего или закрывающего тега в текущей строке
            if (!empty($lines[$index][1])) {
                // Проверка, не является ли тег самозакрывающимся или пустым
                if (!preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $lines[$index][1])) {
                    // Проверка наличия закрывающего тега
                    if (substr($lines[$index][1],1,1)==='/') {
                        array_shift($open_tags);
                    } elseif (substr($lines[$index][1],1,1)!='!'){
                        // Открывающий тег
                        array_unshift($open_tags, $lines[$index][1]);
                    }
                }
            }
            $content_length = self::countWordsInHtml($lines[$index][2]);
            // Проверка, не превышена ли минимальная длина страницы
            if ($total_length + $content_length > $minPageLength) {
                if ($total_length + $content_length > $maxPageLength && substr($lines[$index][2],0,7)!='<script'&& substr($lines[$index][2],0,6)!='<style') {
                    $truncate .= $lines[$index][1] . self::getFirstWords($lines[$index][2], $maxPageLength - $total_length);

                    foreach ($open_tags as $open_tag) {
                        $tagWOattr = preg_replace("#(</?\w+)(?:\s(?:[^<>/]|/[^<>])*)?(/?>)#ui", '$1$2',$open_tag);
                        $truncate .= "</" . substr($tagWOattr,1,strlen($tagWOattr)-2) . ">";
                    }
                    $last_index = $indexCounter;
                    array_shift($open_tags);
                    // Возвращение результата в зависимости от флага split
                    if (true === $split) {
                        return array(
                            'truncate' => $truncate,
                            'last_index' => $last_index,
                        );
                    }
                    return $truncate;
                } else {
                    $truncate .= $lines[$index][1] . $lines[$index][2];
                    foreach ($open_tags as $open_tag) {
                        $tagWOattr = preg_replace("#(</?\w+)(?:\s(?:[^<>/]|/[^<>])*)?(/?>)#ui", '$1$2',$open_tag);
                        $truncate .= "</" . substr($tagWOattr,1,strlen($tagWOattr)-2) . ">";
                    }
                    $last_index = $indexCounter+1;
                    // Возвращение результата в зависимости от флага split
                    if (true === $split) {
                        return array(
                            'truncate' => $truncate,
                            'last_index' => $last_index,
                        );
                    }
                }
            }
            $indexCounter++;
            $total_length+=$content_length;
            $truncate .= $lines[$index][1] . $lines[$index][2];
        }

        // Возвращение результата в зависимости от флага split
        if (true === $split) {
            return array(
                'truncate'   => $truncate,
                'last_index' => count($lines),
            );
        }

        return $truncate;
    }

    /**
     * Обрезает HTML-контент до заданной длины, сохраняя структуру тегов.
     *
     * @param string $htmlContent - Исходный HTML-контент.
     * @param int $minPageLength - Минимальная длина страницы.
     * @param int $maxPageLength - Максимальная допустимая длина страницы.
     * @return string - Обрезанный HTML-контент.
     */
    public static function getTrimmedHtmlContentWithinTagsAndLength(string $htmlContent,int $minPageLength,int $maxPageLength):string
    {
        if (strlen(preg_replace('/<.*?>/', '', $htmlContent)) <= $maxPageLength) {
            return $htmlContent;
        }
        $lines = self::SplitByTags($htmlContent);
        $open_tags=[];
        return self::trimHtmlContentWithinTagsAndLength($open_tags,$minPageLength,$maxPageLength,$lines);
    }
    /**
     * Разбивает HTML-контент на массив страниц заданной длины.
     *
     * @param string $htmlContent - Исходный HTML-контент.
     * @param int $minPageLength - Минимальная длина страницы.
     * @param int $maxPageLength - Максимальная допустимая длина страницы.
     * @return array - Массив обрезанных HTML-страниц.
     */
    public static function splitHtmlToArray(string $htmlContent, int $minPageLength, int $maxPageLength): array {
        $splitted = [];

        if (strlen(preg_replace('/<.*?>/', '', $htmlContent)) <= $maxPageLength) {
            return array($htmlContent);
        }

        $lines = self::SplitByTags($htmlContent);
        $open_tags = [];
        while (count($lines)>0) {

            $cropped = self::trimHtmlContentWithinTagsAndLength($open_tags ,$minPageLength,$maxPageLength,$lines,true);
            $splitted[] = self::restoreSpecialCharsInAttributes($cropped['truncate']);
            $last_index = $cropped['last_index'];
            $lines = array_slice($lines,$last_index);
        }

        return $splitted;
    }
    /**
     * Разбивает HTML-контент на страницы заданной длины и сохраняет их в файлы в указанной папке.
     *
     * @param string $htmlContent - Исходный HTML-контент.
     * @param int $minPageLength - Желаемая длина страницы.
     * @param int $maxPageLength - Максимальная допустимая длина страницы.
     * @param string $outputFolder - Папка для сохранения файлов.
     * @return void
     */
    public static function splitHtmlToFolder(string $htmlContent,int $minPageLength,int $maxPageLength,string $outputFolder):void{
        if (!file_exists($outputFolder) && !is_dir($outputFolder)) {
            mkdir($outputFolder);
        }

        $splitted = self::splitHtmlToArray($htmlContent,$minPageLength,$maxPageLength);
        foreach ($splitted as $index => $htmlContent) {
            // Создаем имя файла на основе индекса
            $fileName = "page_" . ($index + 1) . ".html";

            // Создаем полный путь к файлу
            $filePath = $outputFolder . '/' . $fileName;
            // Сохраняем HTML в файл
            file_put_contents($filePath, $htmlContent);
        }
    }
}

// Пример использования
ini_set('memory_limit', '2560M');
$inputFile = 'content.html';
$outputFolder = 'ContestSplited';
$htmlContent = file_get_contents($inputFile);
$goodPageLength = 500;
$maxPageLength = $goodPageLength *1.2;

HtmlSplitter::splitHtmlToFolder($htmlContent,$goodPageLength,$maxPageLength,$outputFolder);