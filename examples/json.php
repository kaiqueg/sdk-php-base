<?php

include_once "config.php";

$path = __DIR__ . "/output.json";

if (file_exists($path)) {
    unlink($path);
}

$data = [
    [
        "id" => 1,
        "name" => "Foo",
    ],
    [
        "id" => 2,
        "name" => "Bar",
    ],
];

echo "<h1>BLANK LOAD</h1>";
$emptyJson = new \SdkBase\Utils\Json();
$emptyJson->setPath($path);
try {
    $emptyJson->load();
} catch (\SdkBase\Exceptions\Validation\FileNotFoundException $e) {
    echo "<br/>Right, file not found as expected.";
}

echo "<hr/><h1>SAVE</h1>";
$json = new \SdkBase\Utils\Json();
$json->setPath($path);
$json->setData($data);
$json->save();
echo "<br/>FILE SAVED";

echo "<hr/><h1>LOAD</h1>";
$json2 = new \SdkBase\Utils\Json();
$json2->setPath($path);
$json2->load();
$loadedData = $json2->getData();
echo "<br/>FILE LOADED: <pre>";
var_dump($loadedData);
echo "</pre>";
echo "<hr/><h1>SAVE (override)</h1>";
$newData = array_merge($loadedData, [
    [
        "id" => 3,
        "name" => "FooBar",
    ],
    [
        "id" => 4,
        "name" => "Foo",
    ],
    [
        "id" => 5,
        "name" => "Bar",
    ],
]);
$json2->setData($newData);
$json2->save();
echo "<br/>FILE SAVED";

echo "<hr/><h1>RE-LOAD</h1>";
$json3 = new \SdkBase\Utils\Json();
$json3->setPath($path);
$json3->load();
$loadedData = $json3->getData();
echo "<br/>FILE LOADED: <pre>";
var_dump($loadedData);
echo "</pre>";

echo "<hr/><h1>SEARCH ITEM WHERE id=4:</h1><pre>";
$result = $json3->searchItem([
    "id" => 4,
]);
var_dump($result);
echo "</pre>";

echo "<hr/><h1>SEARCH ALL WHERE name=Foo:</h1><pre>";
$result = $json3->searchAll([
    "name" => "Foo",
]);
var_dump($result);
echo "</pre>";

echo "<hr/><h1>SEARCH ALL WHERE name=Bar:</h1><pre>";
$result = $json3->searchAll([
    "name" => "Bar",
]);
var_dump($result);
echo "</pre>";

echo "<hr/><h1>SEARCH ITEM WHERE name=FooBar:</h1><pre>";
$result = $json3->searchItem([
    "name" => "FooBar",
]);
var_dump($result);
echo "</pre>";

echo "<hr/><h1>SEARCH ITEM WHERE name=FooBar + id=4:</h1><pre>";
$result = $json3->searchItem([
    "id" => 4,
    "name" => "FooBar",
]);
var_dump($result);
echo "</pre>";