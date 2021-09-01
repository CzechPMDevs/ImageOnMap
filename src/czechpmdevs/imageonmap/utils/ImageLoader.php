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

use czechpmdevs\imageonmap\image\Image;
use InvalidStateException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\AssumptionFailedError;
use function imagecolorat;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecrop;
use function imagescale;
use function pack;
use function pathinfo;
use const PATHINFO_EXTENSION;

class ImageLoader {

	public static function loadImage(string $path, int $size = 1, int $xOffset = 0, int $yOffset = 0, bool $locked = false): Image {
		$suffix = pathinfo($path, PATHINFO_EXTENSION);
		if($suffix == "png") {
			$image = imagecreatefrompng($path);
		} else {
			$image = imagecreatefromjpeg($path);
		}

		if(!$image) {
			throw new InvalidStateException("Could not access target image file $path");
		}

		$image = imagescale($image, 128 * $size, 128 * $size);
		if(!$image) {
			throw new InvalidStateException("Could not rescale the image");
		}

		$image = imagecrop($image, [
			"x" => 128 * $xOffset,
			"y" => 128 * $yOffset,
			"width" => 128,
			"height" => 128
		]);
		if(!$image) {
			throw new InvalidStateException("Could not crop the image");
		}

		$colors = [];
		for($y = 0; $y < 128; ++$y) {
			for($x = 0; $x < 128; ++$x) {
				$color = imagecolorat($image, $x, $y);
				if($color === false) {
					throw new AssumptionFailedError("Could not read image pixel at $x:$y");
				}

				// TODO: Read alpha properly
				$color |= (0xff << 24);

				$colors[] = $color;
			}
		}

		$nbt = new CompoundTag();
		$nbt->setByteArray("colors", pack("L*", ...$colors));
		$nbt->setByte("locked", $locked ? 1 : 0);

		return Image::load($nbt);
	}
}