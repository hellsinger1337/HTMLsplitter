<?php
include "HtmlSplitter.php";
// Исходный HTML-контент
$htmlContent = file_get_contents("content.html");
$splitter = new HtmlSplitter($htmlContent,1000,1200);
$splitter->SplitToArray();
$splitter->writeToFolder("output");
