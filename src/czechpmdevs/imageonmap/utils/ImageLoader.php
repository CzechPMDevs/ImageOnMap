<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 - 2023 CzechPMDevs
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
use czechpmdevs\imageonmap\ImageOnMap;
use ErrorException;
use InvalidArgumentException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\AssumptionFailedError;
use function file_exists;
use function imagecolorat;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecrop;
use function imagescale;
use function pack;
use function pathinfo;
use function str_contains;
use const PATHINFO_EXTENSION;

class ImageLoader {
	public static function findFile(string $imageName): ?string {
		if(!str_contains($imageName, ".png") && !str_contains($imageName, ".jpg")) {
			if(file_exists(ImageOnMap::getInstance()->getDataFolder() . "images/$imageName.png")) {
				$imageName .= ".png";
			} elseif(file_exists(ImageOnMap::getInstance()->getDataFolder() . "images/$imageName.jpg")) {
				$imageName .= ".jpg";
			} else {
				return null;
			}
		}

		return file_exists(ImageOnMap::getInstance()->getDataFolder() . "images/$imageName") ? $imageName : null;
	}

	/**
	 * @throws PermissionDeniedException If the file could not be accessed
	 * @throws ImageLoadException If any other error occurs whilst loading an image
	 */
	public static function loadImage(string $path, int $xChunkCount = 1, int $yChunkCount = 1, int $xOffset = 0, int $yOffset = 0, bool $locked = false): Image {
		$suffix = pathinfo($path, PATHINFO_EXTENSION);
		try {
			if($suffix === "png") {
				$image = imagecreatefrompng($path);
			} else {
				$image = imagecreatefromjpeg($path);
			}
		}

		// Truly ErrorException could be thrown, even though it is not in php docs.
		// To reproduce this ErrorException to be thrown, try loading jpg image
		// with 'imagecreatefrompng' function.
		catch(ErrorException $e) {
			throw new ImageLoadException($e->getMessage(), $e->getCode(), $e);
		}

		if(!$image) {
			throw new PermissionDeniedException("Could not access target image file $path");
		}

		$image = imagescale($image, 128 * $xChunkCount, 128 * $yChunkCount);
		if(!$image) {
			throw new InvalidArgumentException("Could not rescale the image");
		}

		$image = imagecrop($image, [
			"x" => 128 * $xOffset,
			"y" => 128 * $yOffset,
			"width" => 128,
			"height" => 128
		]);
		if(!$image) {
			throw new InvalidArgumentException("Could not crop the image");
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