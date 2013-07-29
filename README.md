Kindle Periodical
=================

Php class that helps to build kindle periodicals

**Important:** You need to add [KindleGen](http://www.amazon.com/gp/feature.html?ie=UTF8&docId=1000765211) to your path to run KindlePeriodical

## Usage

First you need to create an array with the content of the periodical:

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
	
With this, you can now generate your .mobi file with KindlePeriodical:

     $ebook = new KindlePeriodical(array(
        
        "outputFolder" => "out",
        
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

    // Remove the file and directory
    $ebook->deleteFile();
