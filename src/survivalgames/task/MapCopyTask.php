<?php

namespace survivalgames\task;

use pocketmine\scheduler\AsyncTask;

class MapCopyTask extends AsyncTask {
	private $map;
	private $new;
	
	public function __construct(string $map) {
		$dir = join(DIRECTORY_SEPARATOR, ".", "worlds", "");
		if(!file_exists($dir . $map)) {
			$this->map = $dir . $map;
		}
		$i = 0;
		while(file_exists(($this->new = $map . $i))) {
			$i++;
		}
	}
	
	public function onRun() {
		$this->recurseCopy($dir . $this->map, $dir . $this->new);
		$this->setResult($this->new);
	}
	
	public function recurseCopy($src, $dst) { 
   		$dir = opendir($src); 
   		$s = DIRECTORY_SEPARATOR;
    	@mkdir($dst); 
    	while(false !== ( $file = readdir($dir)) ) { 
        	if(( $file != "." ) && ( $file != ".." )) { 
            	if(is_dir($src . $s . $file) ) { 
               		$this->recurseCopy($src . $s . $file, $dst . $s . $file); 
           	    }
        	}else{
                copy($src . $s . $file, $dst . $s . $file); 
            } 
        }
        closedir($dir); 
    }
     
}
