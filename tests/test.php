<?php

require '../vendor/autoload.php';

function loadCSV($filename)
{
    $rows = [];

    if (($handle = fopen($filename, "r")) !== false) {

        $line = 0;
        $cols = array();

        while (($raw = fgetcsv($handle, 1000, ",")) !== false) {

            if ($line === 0) {
                $cols = $raw;
            } else {
                $row = array();
                for ($i = 0; $i < count($raw); $i++) {
                    $row[$cols[$i]] = $raw[$i];
                }
                $rows[] = $row;

            }
            $line++;
        }
        fclose($handle);
    }
    return $rows;
}

$rows = loadCSV(__DIR__ . '/dataset.csv');

$combinator = new \RBM\ResultsCombinator\ResultsCombinator();

/*
$bench = [];
for ($i = 0; $i < 10; $i++) {
    $t1      = microtime(true);

  */
    $results = $combinator->combine(
        $rows,
        "order__id", [
            "order__fees"      => "id",
            "product__options" => "id",
        ],
        "__"
    );

print_r($results);
    /*
    $bench[] = microtime(true) - $t1;
}

echo array_sum($bench) / 100;
*/