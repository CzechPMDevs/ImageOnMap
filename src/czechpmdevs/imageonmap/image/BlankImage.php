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

namespace czechpmdevs\imageonmap\image;

use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\MapImage;

class BlankImage extends Image {

	private static BlankImage $blankImage;

	/**
	 * @internal
	 */
	public function getPacket(int $id): ClientboundMapItemDataPacket {
		$pk = new ClientboundMapItemDataPacket();
		$pk->mapId = $id;
		$pk->dimensionId = $this->dimension;
		$pk->isLocked = false;
		$pk->scale = 1;
		$pk->xOffset = $pk->yOffset = 0;
		$pk->colors = new MapImage($this->colors);
		$pk->origin = new BlockPosition(0, 0, 0);

		return $pk;
	}

	public static function get(): BlankImage {
		if(isset(self::$blankImage)) {
			return self::$blankImage;
		}

		$image = new BlankImage();

		$image->colors = [];
		for($x = 0; $x < 128; ++$x) {
			for($y = 0; $y < 128; ++$y) {
				$image->colors[$x][$y] = new Color(0, 0, 0, 0);
			}
		}

		return self::$blankImage = $image;
	}
}