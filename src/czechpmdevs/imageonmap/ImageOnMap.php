<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace czechpmdevs\imageonmap;

use czechpmdevs\imageonmap\command\ImageCommand;
use czechpmdevs\imageonmap\image\BlankImage;
use czechpmdevs\imageonmap\item\FilledMap;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\plugin\PluginBase;
use function array_key_exists;
use function mkdir;

class ImageOnMap extends PluginBase implements Listener {
	use DataProviderTrait;

	private static ImageOnMap $instance;

	protected function onLoad(): void {
		self::$instance = $this;
	}

	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		@mkdir($this->getDataFolder() . "data");
		@mkdir($this->getDataFolder() . "images");

		$this->loadCachedMaps($this->getDataFolder() . "data");

		$this->getServer()->getCommandMap()->register("imageonmap", new ImageCommand());

		ItemFactory::getInstance()->register(new FilledMap(new ItemIdentifier(ItemIds::FILLED_MAP, 0)));
	}

	protected function onDisable(): void {
		$this->saveCachedMaps($this->getDataFolder() . "data");
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
		$packet = $event->getPacket();
		if(!$packet instanceof MapInfoRequestPacket) {
			return;
		}

		if(!array_key_exists($packet->mapId, $this->cachedMaps)) {
			$event->getOrigin()->sendDataPacket(BlankImage::get()->getPacket($packet->mapId));
			$this->getLogger()->debug("Unknown map id $packet->mapId received from {$event->getOrigin()->getDisplayName()}");
			return;
		}

		$event->getOrigin()->sendDataPacket($this->getCachedMap($packet->mapId)->getPacket($packet->mapId));
	}

	/**
	 * @internal
	 */
	public static function getInstance(): ImageOnMap {
		return self::$instance;
	}
}