<?php

namespace sg\arena;

use sg\task\MapCopyTask;

use pocketmine\Server;
use pocketmine\tile\Chest;

class Arena {
	private static $arenas = [];
	private $level;
	private $id;
	
	public function __construct(string $map) {
    	if(Server::getInstance()->isLevelGenerated($map)) {
    		$async = new MapCopyTask($map);
    		Server::getInstance()->getScheduler()->scheduleAsyncTask($async);
    		Server::getInstance()->loadLevel($async->getResult());
    		$this->level = Server::getInstance()->getLevel($async->getResult());
    		$this->refillAllChests();
    	}
	}
	
	public function refillChest(Chest $chest) {
		
	}
	
	public function __destruct() {
			
	}
	
}