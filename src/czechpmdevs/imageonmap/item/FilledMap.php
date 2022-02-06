<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 - 2022 CzechPMDevs
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

namespace czechpmdevs\imageonmap\item;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\AssumptionFailedError;

class FilledMap extends Item {

	private int $uuid;

	public function setMapId(int $uuid): self {
		$this->uuid = $uuid;
		return $this;
	}

	protected function serializeCompoundTag(CompoundTag $tag): void {
		parent::serializeCompoundTag($tag);
		$tag->setLong("map_uuid", $this->uuid);
	}

	protected function deserializeCompoundTag(CompoundTag $tag): void {
		parent::deserializeCompoundTag($tag);
		$this->uuid = $tag->getLong("map_uuid");
	}

	public static function get(): FilledMap {
		$item = ItemFactory::getInstance()->get(ItemIds::FILLED_MAP);
		if(!$item instanceof FilledMap) {
			throw new AssumptionFailedError("Item is not registered properly");
		}

		return $item;
	}
}