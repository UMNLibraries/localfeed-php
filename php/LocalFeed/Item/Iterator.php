<?php

require_once 'simplepie.inc';
require_once 'File/Find/Rule.php';

class LocalFeed_Item_Iterator implements Iterator
{
    protected $input_directory;
    protected $file_names = array();
    protected $strip_html_tags;
    protected $file_names_index = 0;
    protected $feed;
    protected $feed_items = array();

    protected $bootstrapped = false;
    protected $valid = true; // This is also a bootstrap...
    protected $current;
    protected $current_key;
    protected $current_file_name;
    
    function __construct( $params )
    {
        $file_names = array();
        if (array_key_exists('input_directory', $params)) {
            $input_directory = $params['input_directory'];
            $f = new File_Find_Rule();
            // TODO: Add more flexible file name matching!
            $file_names = $f->name('*.xml')->in($input_directory);
            $this->input_directory = $input_directory;
        } else {
            $file_names = $params['file_names'];
            foreach ($file_names as $file_name) {
                if (!file_exists( $file_name )) {
                    throw new Exception( "File '$file_name' does not exist" );
                }
            }
        }
        $this->file_names = $this->sort_file_names( $file_names );

        if (array_key_exists('strip_html_tags', $params)) {
            $this->strip_html_tags = $params['strip_html_tags'];
        }
    }

    protected function sort_file_names($file_names)
    {
        // natcasesort preserves keys. We need to break that:
        natcasesort( $file_names );
        $sorted_file_names = array();
        foreach ($file_names as $file_name) {
            $sorted_file_names[] = $file_name;
        }
        return $sorted_file_names;
    }
    
    function next_feed()
    {
        // There should be a more elegant way to do this with a loop
        // and SPL Iterator, but this will have to do for now.
        if ($this->file_names_index <= (count($this->file_names) - 1)) {
            $file_name = $this->file_names[ $this->file_names_index ];

            $simplepie_file = new SimplePie_File( $file_name );
            $feed = new SimplePie();
            $feed->set_file( $simplepie_file );
    
            // Must have php5-curl installed!! fsockopen fails 
            // with gzip decompression errors.
            //$feed->force_fsockopen(true);
    
            $feed->init();
    
            if ($feed->error()) {
                throw new Exception( $feed->error() );
            }

            // NYT content/descriptions always seem to have ads and images:
            $feed->strip_htmltags( $this->strip_html_tags );

            $this->feed = $feed;
            $this->feed_items = $feed->get_items();
            $this->file_names_index++;
            $this->current_file_name = $file_name;
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

        $this->file_names_index = 0;
        $this->next_feed();
 
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
            $this->next_item();
            
        }
        return $this->current;
    }

    public function current_file_name()
    {
        return $this->current_file_name;
    }
    
    protected function set_key( $key )
    {
        $this->current_key = $key;
    }

    function key()
    {
        return $this->current_key;
    }
    
    function next()
    {
        $this->next_item();
    }
    
    function next_item()
    {
        while (1) {
            while (1) {
                unset($item);
                $item = current($this->feed_items);
                if (get_class($item) == 'SimplePie_Item') {
                    $this->current = $item;
                    next($this->feed_items);
                    $this->set_key( $item->get_id() );
                    return true;
                }
                break;
            }
            if ($this->next_feed()) {
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
    
} // end class LocalFeed_Item_Iterator

?>
