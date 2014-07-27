#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'LocalFeed/File.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '2G');

error_reporting( E_ALL );

class LocalFeedFileTest extends UnitTestCase
{
    public function __construct()
    {
        $this->directory = getcwd() . '/download';
        $this->cleanup();
    }

    public function test_new()
    {
        $lff = new LocalFeed_File( $this->directory );
        $this->assertIsA( $lff, 'LocalFeed_File' );
        $this->lff = $lff;
    }

    public function test_download()
    {
        $this->lff->download( 'http://www.nytimes.com/services/xml/rss/nyt/Health.xml' );
        $f = new File_Find_Rule();
        $file_names = $f->name('*.xml')->in( $this->directory );

        $this->cleanup();
    }

    public function cleanup()
    {
        // Clean out any already-existing files, e.g. from previous test runs.
        $f = new File_Find_Rule();
        $file_names = $f->name('*.xml')->in( $this->directory );
        foreach ($file_names as $file_name) {
            unlink( $file_name );
        }
    }


} // end class LocalFeedFileTest
