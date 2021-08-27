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

namespace czechpmdevs\imageonmap\utils;

use pocketmine\color\Color;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

class Image {

	private int $dimension = DimensionIds::OVERWORLD;
	private bool $isLocked = false;

	private ClientboundMapItemDataPacket $packetCache;

	/** @var Color[][] */
	private array $colors;

	private function __construct() {}

	/**
	 * @internal
	 */
	public function getPacket(int $id): ClientboundMapItemDataPacket {
		if(isset($this->packetCache)) {
			return $this->packetCache;
		}

		$pk = new ClientboundMapItemDataPacket();
		$pk->mapId = $id;
		$pk->dimensionId = $this->dimension;
		$pk->isLocked = false;
		$pk->scale = 1;
		$pk->xOffset = $pk->yOffset = 0;
		$pk->width = $pk->height = 128;
		$pk->colors = $this->colors;

		return $this->packetCache = $pk;
	}

	/**
	 * @internal
	 */
	public static function load(CompoundTag $nbt): Image {
		$mapImage = new Image();
		$mapImage->dimension = $nbt->getByte("dimension", DimensionIds::OVERWORLD);
		$mapImage->isLocked = $nbt->getByte("locked", 0) == 1;
		$mapImage->colors = ColorSerializer::readColors($nbt->getByteArray("colors"));

		return $mapImage;
	}

	/**
	 * @internal
	 */
	public function save(): CompoundTag {
		$nbt = new CompoundTag();
		$nbt->setByte("dimension", $this->dimension);
		$nbt->setByte("locked", $this->isLocked ? 1 : 0);
		$nbt->setByteArray("colors", ColorSerializer::writeColors($this->colors));

		return $nbt;
	}
}