<?php

namespace survivalgames\arena;

use survivalgames\task\MapCopyTask;

use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\block\Air;
use pocketmine\entity\DroppedItem;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Double;

class Arena {	
	private static $arenas = [];	
	private $players = [];
	private $started = false;
	private $level;
	private $map;
	private $id;
	public $maxPlayers = 20;
	
	
	public function __construct(string $map) {
    	$async = new MapCopyTask($map);
    	Server::getInstance()->getScheduler()->scheduleAsyncTask($async);
    	Server::getInstance()->loadLevel($async->getResult());
    	$this->level = Server::getInstance()->getLevel($async->getResult());
    	$this->map = $map;
    	$this->refillAllChests();  
    	$this->id = count(self::$arenas);  		
    	array_push(self::$arenas, $this);
	}
	
	public function update() {
		foreach(array_keys($this->players) as $key) {
			$player = $this->players[$key]["obj"];
			if(!$player->isOnline()) {
				$this->removePlayer($player);
			}
		}	
	}
	
	public function isInArena(Player $player) {
		return isset($this->players[$player->getName()]);
	}
	
	public function startGame() {
		$this->started = true;	
	}
	
	public function endGame() {
		$this->started = false;
		foreach(array_keys($this->players) as $key) {
	    	$this->removePlayer($this->players[$key]["obj"], false);
		}
		Server::getInstance()->unloadLevel($this->level);
		$dir = join(DIRECTORY_SEPARATOR, ".", "worlds", "");
		unlink($dir . $this->map);
	}
	
	public function startDeathmatch() {
		
	}
	
	public function getPlayersLeft() {
		$players = [];
		foreach(array_keys($this->players) as $key) {
			array_push($players, $this->players[$key]);
		}
		return $players;
	}
	
	public function addPlayer(Player $player) {
		if(count($this->players) >= $this->maxPlayers) {
			if($player->hasPermission("sg.perks.join-full")) { // Players who have the correct permissions can join even when arenas are full.
				$m = count($this->players);
				$kick = array_keys($this->players)[rand(0, $m - 1)];
				$this->kickPlayer($this->players[$kick]["obj"], "Making space for " . $player->getName());
			}else{
				return false;
			}
		}
		if(!$player->isOnline()) {
			return false;
		}
		$this->players[$player->getName()]["obj"] = $player;
		$this->players[$player->getName()]["pos"] = new Vector3($player->getX(), $player->getY(), $player->getZ());
		$this->players[$player->getName()]["level"] = $player->getLevel();
		$this->players[$player->getName()]["inventory"] = $player->getInventory();
		return true;
	}
	
	public function removePlayer(Player $player, boolean $drop = true) {
		if(!$this->isInArena($player)) {
			return false;
		}
		$inv = $this->players[$player->getName()]["inventory"];
		for($slot = 0; $slot < $inv->getSize(); $slot++) {
			if($drop) {
				$e = new DroppedItem($this->level, new Compound("DroppedItem", array( // Drop items out of inventory
				 		new Enum("Pos", array(
							new Double(0, $player->x - 3 + rand(0, 6)),
							new Double(1, $player->y - 3 + rand(0, 6)),
							new Double(2, $player->z - 3 + rand(0, 6))
						)),
						new String("Thrower", $player->getName()),
						new Compound("Item", array(
							new Byte("Count", $player->getInventory()->getItem($slot)->getCount()),
							new Short("Damage", $player->getInventory()->getItem($slot)->getDamage()),
							new Short("id", $player->getInventory()->getItem($slot)->getID())
						)) 
				)));			
			}
			$player->getInventory->setItem($slot, $inv->getItem($slot));
		}
		$player->teleport($this->players[$player->getName()]["pos"]);
		$player->setLevel($this->players[$player->getName()]["level"]);
		unset($this->players[$player->getName()]);
		return true;
	}

	public function kickPlayer(Player $player, string $reason) {
		$player->sendChat("You've been kicked from the game. Reason: " + $reason + ".");
		$this->removePlayer($player);
	}
	
	public function refillChest(Chest $chest) {
		$replaceSlots = [];
		for($slot = 0; $slot < $chest->getSize(); $slot++) {
			$chest->setItem($slot, new Air()); // Clear chest first.
			$chance = rand(0, 5);
			if($chance == 0) {
				array_push($replaceSlots, $slot);
			}
		}
		if(count($replaceSlots) >= $chest->getSize() / 2) {
			array_slice($replaceSlots, $chest->getSize() / 2 - 1);
		}
		foreach($replaceSlots as $slot) {
			$item = null; // TODO: Decide items to put in chest.
			$chest->setItem($slot, $item);		
		}
		if($chest->isPaired()) { // Large chests
			$chest = $chest->getPair();
		    $replaceSlots = [];
			for($slot = 0; $slot < $chest->getSize(); $slot++) {
				$chest->setItem($slot, new Air());
				$chance = rand(0, 5);
				if($chance == 0) {
					array_push($replaceSlots, $slot);
				}
			}
			if(count($replaceSlots) >= $chest->getSize() / 2) {
				array_slice($replaceSlots, $chest->getSize() / 2 - 1);
			}
			foreach($replaceSlots as $slot) {
				$item = null;
				$chest->setItem($slot, $item);		
			}
		}
	}
    
	public function refillAllChests() {
		$tiles = $this->level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Chest) {
				$this->refillChests($t);
			}
		}
	}
	
	public function __destruct() {
			
	}
	
}