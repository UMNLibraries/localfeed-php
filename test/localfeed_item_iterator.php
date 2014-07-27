#!/usr/bin/php -q
<?php

// TODO: Don't think I'm using LocalFeed_Item_Itertor anywhere. Verify, then remove this test and the class it tests!!!

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'LocalFeed/Item/Iterator.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '512M');

error_reporting( E_ALL );

class LocalFeedItemIteratorTest extends UnitTestCase
{
    public function __construct()
    {
        $f = new File_Find_Rule();
        $this->directory = getcwd() . '/permanent';
        $this->file_names = $f->name('*.xml')->in( $this->directory );
    }

    public function test_new()
    {
        $file_name = $this->file_names[0];
        $lfi_single_file = new LocalFeed_Item_Iterator(array(
            'file_names' => array( $file_name ),
        ));
        $this->assertIsA( $lfi_single_file, 'LocalFeed_Item_Iterator' );
        $this->lfi_single_file = $lfi_single_file;

        $lfi_multi_files = new LocalFeed_Item_Iterator(array(
            'file_names' => $this->file_names,
            // Since this is just a sanity re-check of the above, add
            // 'strip_html_tags' to make the test more useful:
            'strip_html_tags' => array('br','span','a','img'),
        ));
        $this->assertIsA( $lfi_multi_files, 'LocalFeed_Item_Iterator' );
        $this->lfi_multi_files = $lfi_multi_files;

        $lfi_directory = new LocalFeed_Item_Iterator(array(
            'input_directory' => $this->directory,
        ));
        $this->assertIsA( $lfi_directory, 'LocalFeed_Item_Iterator' );
        $this->lfi_directory = $lfi_directory;
    }

    public function test_single_file()
    {
        $this->run_iterator($this->lfi_single_file, 18);
    }

    public function test_multi_files()
    {
        $this->run_iterator($this->lfi_multi_files, 67);
    }

    public function test_directory()
    {
        $this->run_iterator($this->lfi_directory, 67);
    }

    public function run_iterator($lfi, $count)
    {
        $lfi->rewind();
        $items = array();

        while ($lfi->valid()) {
            $item = $lfi->current();

            // Sanity check that deserializing the record to a 
            // PHP array was successful:
            $this->assertIsA( $item, 'SimplePie_Item' );

            // Ensure that this is a unique record, i.e. to ensure
            // that the iterator is correctly advancing through the records:
            $id = $item->get_id();
            $key = $lfi->key();
            $this->assertEqual($id, $key);
            $this->assertFalse( array_key_exists($key, $items) );

            $items[$key] = $item;
            $lfi->next();
        }
        $this->assertTrue(count($items) == $count);
    }

} // end class LocalFeedItemIteratorTest
