<?php

namespace UmnLib\Core\Tests;

use UmnLib\Core\LocalFeed\File;
use Symfony\Component\Finder\Finder;

class LocalFeedFileTest extends \PHPUnit_Framework_TestCase
{
  public function __construct()
  {
    $this->directory = dirname(__FILE__) . '/fixtures/download';
    $this->cleanup();
  }

  public function testDownload()
  {
    $lff = new File($this->directory);
    $this->assertInstanceOf('\UmnLib\Core\LocalFeed\File', $lff);
    $lff->download('http://www.nytimes.com/services/xml/rss/nyt/Health.xml');
    $filenames = $this->getFilenames();
    $this->assertGreaterThan(0, count($filenames));
    $this->cleanup();
  }

  public function getFilenames()
  {
    $finder = new Finder();
    $files = $finder->name('*.xml')->in($this->directory);
    $filenames = array();
    foreach($files as $file) {
      $filenames[] = $file->getRealPath();
    }
    return $filenames;
  }

  public function cleanup()
  {
    // Clean out any already-existing files, e.g. from previous test runs.
    $filenames = $this->getFilenames();
    foreach ($filenames as $filename) {
      unlink( $filename );
    }
  }
}
