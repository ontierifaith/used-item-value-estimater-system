<?php
// functions.php

function fetchJumiaProductsData($searchTerm = "laptop") {
    $url = "https://www.jumia.co.ke/catalog/?q=" . urlencode($searchTerm);

    // Use file_get_contents with User-Agent (since CLI PHP cURL may fail)
    $opts = ["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 15]];
    $html = @file_get_contents($url, false, stream_context_create($opts));
    if (!$html) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $nodes = $xpath->query("//article[contains(@class,'prd') or contains(@class,'c-prd')]");
    $products = [];

    foreach ($nodes as $node) {
        $name = $xpath->evaluate("string(.//h3[contains(@class,'name')])", $node);
        $priceText = $xpath->evaluate("string(.//*[contains(@class,'prc')])", $node);
        $price = preg_replace("/[^\d]/", "", $priceText);
        $price = (float)$price;

        if ($name && $price > 0) {
            $products[] = ['name' => $name, 'price' => $price];
        }
    }

    return $products;
}
