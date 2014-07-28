<?php

// TODO: Don't think I'm using LocalFeed_Item_Itertor anywhere. Verify, then remove this test and the class it tests!!!

namespace UmnLib\Core\Tests;

use UmnLib\Core\LocalFeed\ItemIterator;
use Symfony\Component\Finder\Finder;

class LocalFeedItemIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterators()
    {
      $directory = dirname(__FILE__) . '/fixtures/permanent';
      $finder = new Finder();
      $files = $finder->name('*.xml')->in($directory);
      $filenames = array();
      foreach($files as $file) {
        $filenames[] = $file->getRealPath();
      }

      $filename = $filenames[0];
      $lfiSingleFile = new ItemIterator(array(
        'filenames' => array($filename),
      ));
      $this->assertInstanceOf('\UmnLib\Core\LocalFeed\ItemIterator', $lfiSingleFile);

      $lfiMultiFiles = new ItemIterator(array(
        'filenames' => $filenames,
        // Since this is just a sanity re-check of the above, add
        // 'stripHtmlTags' to make the test more useful:
        'stripHtmlTags' => array('br','span','a','img'),
      ));
      $this->assertInstanceOf('\UmnLib\Core\LocalFeed\ItemIterator', $lfiMultiFiles);

      $lfiDirectory = new ItemIterator(array(
        'inputDirectory' => $directory,
      ));
      $this->assertInstanceOf('\UmnLib\Core\LocalFeed\ItemIterator', $lfiDirectory);

      $this->runIterator($lfiSingleFile, 26);
      $this->runIterator($lfiMultiFiles, 67);
      $this->runIterator($lfiDirectory, 67);
    }

    public function runIterator($lfi, $count)
    {
        $lfi->rewind();
        $items = array();

        while ($lfi->valid()) {
            $item = $lfi->current();

            // Sanity check that deserializing the record to a 
            // PHP array was successful:
            $this->assertInstanceOf('SimplePie_Item', $item);

            // Ensure that this is a unique record, i.e. to ensure
            // that the iterator is correctly advancing through the records:
            $id = $item->get_id();
            $key = $lfi->key();
            $this->assertEquals($id, $key);
            $this->assertFalse(array_key_exists($key, $items));

            $items[$key] = $item;
            $lfi->next();
        }
        $this->assertEquals($count, count($items));
    }
}
