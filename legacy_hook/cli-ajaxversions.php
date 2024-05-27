<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

// TODO: REMOVE THIS
require __DIR__ . '/vendor/autoload.php';

use App\DocumentationVersions;
use App\ResponseEmitter;
use GuzzleHttp\Psr7\ServerRequest;

// Fake it till you make it
$_REQUEST['url'] = $_GET['url'] = 'https://docs.typo3.org/m/typo3/tutorial-getting-started/12.4/en-us/Index.html';
$GLOBALS['_SERVER']['DOCUMENT_ROOT'] = '/tmp/fake-docs.typo3.org/';

$versions = [
    '9.4',
    '10.5',
    '11.5',
    '12.4',
    'main',
];
$languages = [
    'de-de' => 'GERMAN',
    'en-us' => 'DEFAULT',
    'ru-ru' => 'RUSSIAN',
    'fr-fr' => 'FRENCH',
    'de-AT' => 'GERMAN (AUSTRIAN)',
    'de-CH' => 'GERMAN (SWISS)',
];
$subdirectories = [
    'Concepts',
    'singlehtml',
];

$struct = [];
foreach ($versions as $version) {
    $struct[$version] = [];
    foreach ($languages as $language => $languageName) {
        mkdir($GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/m/typo3/tutorial-getting-started/' . $version . '/' . $language . '/', 0777, true);
        touch($GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/m/typo3/tutorial-getting-started/' . $version . '/' . $language . '/Index.html');

        foreach ($subdirectories as $subdirectory) {
            mkdir($GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/m/typo3/tutorial-getting-started/' . $version . '/' . $language . '/' . $subdirectory, 0777, true);
            touch($GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/m/typo3/tutorial-getting-started/' . $version . '/' . $language . '/' . $subdirectory . '/Index.html');
        }
    }
}

print_r($struct);

$response = (new DocumentationVersions(ServerRequest::fromGlobals()))->getVersions();

print_r($response);
