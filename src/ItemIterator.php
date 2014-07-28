<?php

namespace UmnLib\Core\LocalFeed;

use Symfony\Component\Finder\Finder;

class ItemIterator implements \Iterator
{
  protected $inputDirectory;
  protected $filenames = array();
  protected $stripHtmlTags;
  protected $filenamesIndex = 0;
  protected $feed;
  protected $feedItems = array();

  protected $bootstrapped = false;
  protected $valid = true; // This is also a bootstrap...
  protected $current;
  protected $currentKey;
  protected $currentFilename;

  function __construct( $params )
  {
    $filenames = array();
    if (array_key_exists('inputDirectory', $params)) {
      $inputDirectory = $params['inputDirectory'];
      $finder = new Finder();
      // TODO: Add more flexible file name matching!
      $files = $finder->name('*.xml')->in($inputDirectory);
      foreach($files as $file) {
        $filenames[] = $file->getRealPath();
      }
      $this->inputDirectory = $inputDirectory;
    } else {
      $filenames = $params['filenames'];
      foreach ($filenames as $filename) {
        if (!file_exists($filename)) {
          throw new \InvalidArgumentException("File '$filename' does not exist");
        }
      }
    }
    $this->filenames = $this->sortFilenames($filenames);

    if (array_key_exists('stripHtmlTags', $params)) {
      $this->stripHtmlTags = $params['stripHtmlTags'];
    }
  }

  protected function sortFilenames($filenames)
  {
    // natcasesort preserves keys. We need to break that:
    natcasesort($filenames);
    $sortedFilenames = array();
    foreach ($filenames as $filename) {
      $sortedFilenames[] = $filename;
    }
    return $sortedFilenames;
  }

  function nextFeed()
  {
    // There should be a more elegant way to do this with a loop
    // and SPL Iterator, but this will have to do for now.
    if ($this->filenamesIndex <= (count($this->filenames) - 1)) {
      $filename = $this->filenames[ $this->filenamesIndex ];

      // Must instantiate SimplePie object first to avoid this error when running PHPUnit tests:
      // "Use of undefined constant SIMPLEPIE_FILE_SOURCE_NONE - assumed 'SIMPLEPIE_FILE_SOURCE_NONE'"
      $feed = new \SimplePie();
      $simplepieFile = new \SimplePie_File($filename);
      $feed->set_file( $simplepieFile );

      // Must have php5-curl installed!! fsockopen fails 
      // with gzip decompression errors.
      //$feed->force_fsockopen(true);

      $feed->init();

      if ($feed->error()) {
        throw new \RuntimeException($feed->error());
      }

      // NYT content/descriptions always seem to have ads and images:
      $feed->strip_htmltags($this->stripHtmlTags);

      $this->feed = $feed;
      $this->feedItems = $feed->get_items();
      $this->filenamesIndex++;
      $this->currentFilename = $filename;
      return true;
    } else {
      return false;
    }
  }

  function rewind()
  {
    // Can't find a way to reset XMLReader to the beginning
    // of a file, so just create a new reader, at least for now:
    // TODO: Does the above apply to SimplePie, thought???

    if ($this->feed) {
      unset($this->feed);
    }

    $this->filenamesIndex = 0;
    $this->nextFeed();

    $this->valid = true;
    $this->bootstrapped = false;
  }

  function current()
  {
    // Given Iterator's goofy method call order,
    // in which it calls current() before next(),
    // we have to bootstrap current so that it 
    // will have a value for the first loop iteration.
    if (!$this->bootstrapped) {
      $this->bootstrapped = true;
      $this->nextItem();

    }
    return $this->current;
  }

  public function currentFilename()
  {
    return $this->currentFilename;
  }

  protected function setKey( $key )
  {
    $this->currentKey = $key;
  }

  function key()
  {
    return $this->currentKey;
  }

  function next()
  {
    $this->nextItem();
  }

  function nextItem()
  {
    while (1) {
      while (1) {
        unset($item);
        $item = current($this->feedItems);
        if ($item instanceof \SimplePie_Item) {
          $this->current = $item;
          next($this->feedItems);
          $this->setKey( $item->get_id() );
          return true;
        }
        break;
      }
      if ($this->nextFeed()) {
        continue;
      } else {
        break;
      }
    }
    unset($this->current);
    // Only set valid to false when there are no
    // more records to read:
    $this->valid = false;
  }

  function valid()
  {
    return $this->valid;
  }
}
