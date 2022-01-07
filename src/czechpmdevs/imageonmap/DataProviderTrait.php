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

use czechpmdevs\imageonmap\image\Image;
use czechpmdevs\imageonmap\utils\ImageLoader;
use czechpmdevs\imageonmap\utils\PermissionDeniedException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use function array_key_exists;
use function basename;
use function crc32;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function hash_file;
use function substr;

trait DataProviderTrait {

	/** @var array<int, Image> */
	private array $cachedMaps = [];

	/**
	 * @internal
	 *
	 * @throws PermissionDeniedException When the file could not be accessed
	 */
	public function loadCachedMaps(string $path): void {
		$files = glob($path . "/map_*.dat");
		if(!$files) {
			return;
		}

		$serializer = new BigEndianNbtSerializer();
		foreach($files as $file) {
			$content = file_get_contents($file);
			if(!$content) {
				throw new PermissionDeniedException("Could not access file $file");
			}

			$this->cachedMaps[(int)substr(basename($file, ".dat"), 4)] = Image::load($serializer->read($content)->mustGetCompoundTag());
		}
	}

	/**
	 * @internal
	 *
	 * @throws PermissionDeniedException When the file could not be accessed
	 */
	public function saveCachedMaps(string $path): void {
		$serializer = new BigEndianNbtSerializer();
		foreach($this->cachedMaps as $id => $map) {
			if(!file_put_contents($file = "$path/map_$id.dat", $serializer->write(new TreeRoot($map->save())))) {
				throw new PermissionDeniedException("Could not access file $file");
			}
		}
	}

	/**
	 * @return int $id Returns id of the image loaded from file.
	 *
	 * @throws PermissionDeniedException When the file could not be accessed
	 */
	public function getImageFromFile(string $file, int $xChunkCount, int $yChunkCount, int $xOffset, int $yOffset): int {
		$id = crc32(hash_file("md5", $file) . "$xChunkCount:$yChunkCount:$xOffset:$yOffset");
		if(!array_key_exists($id, $this->cachedMaps)) {
			$this->cachedMaps[$id] = ImageLoader::loadImage($file, $xChunkCount, $yChunkCount, $xOffset, $yOffset);
		}

		return $id;
	}

	/**
	 * @internal
	 */
	public function getCachedMap(int $id): Image {
		return $this->cachedMaps[$id];
	}
}