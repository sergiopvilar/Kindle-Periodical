<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

include "vendor/autoload.php";

$content = array(
    // Array of sections
    array(
        'id' => '0',
        'name' => 'My first section',
        'content' => array(

            // Array of articles
            array(
                'title' => 'Title of my first article',
                'content' => 'content of my article' // HTML is allowed
            )
        )
    ),
    array(
        'id' => '1',
        'name' => 'My toher section',
        'content' => array(

            // Array of articles
            array(
                'title' => 'Title of my other article',
                'content' => 'content of my article'
            )
        )
    )
);

$ebook = new \Kindle\Periodical(array(

    "outputFolder" => "output",

    // Optional arguments:
    "shell" => false, // KindlePeriodical will use exec instead of shell_exec
    "debug" => true

));

$ebook->setMeta(array(
    'title' => 'My Kindle Periodical',
    'creator' => "sergiovilar",
    'publisher' => "sergiovilar",
    'subject' => 'Sample Periodical',
    'description' => 'Set of articles in one .mobi file'
));

// Generates the file
$content = $ebook->setContent($content);

// Return the file name
$file = $ebook->getFilename();

// Download the file
$ebook->downloadFile();

?>