<?php

require_once 'File/Find/Rule.php';

class LocalFeed_File
{
    protected $download_directory;
    public function download_directory()
    {
        return $this->download_directory;
    }
    public function set_download_directory( $download_directory )
    {
        if (!preg_match('/\/$/', $download_directory)) {
            $download_directory .= '/';
        }
        $this->download_directory = $download_directory;
    }

    public function __construct( $download_directory )
    {
        $this->set_download_directory( $download_directory );
    }

    public function download( $url )
    {
        $file_string = file_get_contents( $url );
        $date = date("Ymd");
        $file_index = $this->generate_file_index();
        $file_name =
            $this->download_directory() . "$date-$file_index.xml";
        $file = fopen($file_name, 'w' );
        if ( $file == null ) {
          throw new Exception( "Could not open file '$file_name'" );
        }
        fwrite($file, $file_string);
    }

    protected function generate_file_index()
    {
        $f = new File_Find_Rule();
        $date = date("Ymd");
        $file_names = $f->name('/^' . $date . '-\d+\.xml$/')
                   ->in( $this->download_directory() );
        $file_index = 1;
        foreach ($file_names as $file_name) {
            $basename = basename($file_name, '.xml');
            list($date, $index) = preg_split('/-/', $basename);
            if ($index >= $file_index) $file_index = $index + 1;
        }
        return $file_index;
    }

} // end class LocalFeed_File

?>
