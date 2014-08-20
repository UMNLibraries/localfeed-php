<?php

namespace UmnLib\Core\LocalFeed;

use Symfony\Component\Finder\Finder;

class File
{
  protected $fileSet;
  public function fileSet()
  {
    return $this->fileSet;
  }
  public function setFileSet($fileSet)
  {
    // TODO: Add validation!
    $this->fileSet = $fileSet;
  }

  public function __construct($fileSet)
  {
    $this->setFileSet($fileSet);
  }

  public function download($url)
  {
    $fileString = file_get_contents( $url );
    $filename = $this->fileSet()->add();
    file_put_contents($filename, $fileString);
    return $filename;
  }
}
