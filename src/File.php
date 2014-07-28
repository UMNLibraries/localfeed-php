<?php

namespace UmnLib\Core\LocalFeed;

use Symfony\Component\Finder\Finder;

class File
{
  protected $downloadDirectory;
  public function downloadDirectory()
  {
    return $this->downloadDirectory;
  }
  public function setDownloadDirectory($downloadDirectory)
  {
    if (!preg_match('/\/$/', $downloadDirectory)) {
      $downloadDirectory .= '/';
    }
    $this->downloadDirectory = $downloadDirectory;
  }

  public function __construct($downloadDirectory)
  {
    $this->setDownloadDirectory($downloadDirectory);
  }

  public function download($url)
  {
    $fileString = file_get_contents($url);
    $date = date("Ymd");
    $fileIndex = $this->generateFileIndex();
    $filename = $this->downloadDirectory() . "$date-$fileIndex.xml";
    $file = fopen($filename, 'w');
    if ($file == null) {
      throw new \RuntimeException("Could not open file '$filename'");
    }
    fwrite($file, $fileString);
  }

  protected function generateFileIndex()
  {
    $date = date("Ymd");
    $finder = new Finder();
    $files = $finder->name('/^' . $date . '-\d+\.xml$/')->in($this->downloadDirectory());
    $filenames = array();
    foreach($files as $file) {
      $filenames[] = $file->getRealPath();
    }

    $fileIndex = 1;
    foreach ($filenames as $filename) {
      $basename = basename($filename, '.xml');
      list($date, $index) = preg_split('/-/', $basename);
      if ($index >= $fileIndex) $fileIndex = $index + 1;
    }
    return $fileIndex;
  }
}
