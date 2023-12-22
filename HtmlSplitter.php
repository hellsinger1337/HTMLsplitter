<?php

class HtmlSplitter
{
    private $dom;
    private $pages;
    private $minWordsPerPage;
    private $maxWordsPerPage;
    //region setFunctions
    public function setHtml($htmlContent)
    {
        $this->dom=new DOMDocument();
        @$this->dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
    }

    public function setMinWordsPerPage($wordsCount){
        $this->minWordsPerPage=$wordsCount;
    }

    public function setMaxWordsPerPage($wordsCount){
        $this->maxWordsPerPage=$wordsCount;
    }
    //endregion
    public function __construct($html='', $minWordsPerPage = 300,$maxWordsPerPage=500){
        $this->dom=new DOMDocument();
        @$this->dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $this->pages=[];
        $this->maxWordsPerPage=$maxWordsPerPage;
        $this->minWordsPerPage=$minWordsPerPage;

    }
    private function countWordsInHtml($html1): int
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
    private  function countWordsInNode($node): int{
        $html = $node->nodeValue;

        return $this->countWordsInHtml($html);
    }
    private function getFirstWords(&$inputString, $count,$minCount): string {
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
            if ($punctuation !== false && $wordCount>=$minCount-1) {
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
    public function SplitToArray():void{
        while($this->countWordsInHtml($this->dom->saveHTML())>0){

            $targetDoc = new DOMDocument();
            $counter =0;
            $this->getFirstPage($this->dom->documentElement, $targetDoc,$targetDoc, $counter, $this->maxWordsPerPage,$this->minWordsPerPage);
            $this->pages[]=str_replace(['<body>','</body>'],'',html_entity_decode($targetDoc->saveHTML()));
        }
    }
    private function getFirstPage(DOMNode &$node, DOMDocument $targetDoc,DOMNode $targetNode,&$counter, $maxNodes,$minNodes) {
        if ($counter >= $minNodes) {
            return;
        }
        $child = $node->firstChild;
        while($child) {
            $wordsInThis=0;
            if($child->nodeType===XML_TEXT_NODE)
                $wordsInThis= $this->countWordsInNode($child);

            // Если достигнуто максимальное количество, прерываем выполнение
            if ($counter+$wordsInThis >= $maxNodes) {
                $importedNode = $targetDoc->importNode($child, false);
                $childText = $child->nodeValue;
                $importedNode->nodeValue = $this->getFirstWords($childText,$maxNodes-$counter ,$minNodes-$counter);
                $child->nodeValue = $childText;
                $targetNode->appendChild($importedNode);
                $counter = $maxNodes;
                return;
            }
            $counter+=$wordsInThis;
            // Копируем узел в целевой документ
            $importedNode = $targetDoc->importNode($child, false);
            $targetNode->appendChild($importedNode);

            // Увеличиваем счетчик скопированных узлов

            // Рекурсивно копируем дочерние узлы
            $this->getFirstPage($child,$targetDoc, $importedNode, $counter, $maxNodes,$minNodes);

            $child1 = $child->nextSibling;
            if(!$child->childNodes->length)
                $child->parentNode->removeChild($child);
            $child =$child1;
            if ($counter >= $minNodes) {
                return;
            }
        }
    }

    public function writeToFolder($folderPath) {
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        foreach ($this->pages as $index => $pageContent) {
            $filename = sprintf("page_%d.html", $index + 1); // нумерация страниц начинается с 1
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $filename;

            // Запись содержимого страницы в файл
            file_put_contents($filePath, $pageContent);
        }
    }
}