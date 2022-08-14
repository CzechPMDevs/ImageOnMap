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

namespace czechpmdevs\imageonmap\utils;

use czechpmdevs\imageonmap\ImageOnMap;
use pocketmine\color\Color;
use pocketmine\utils\AssumptionFailedError;
use function pack;
use function unpack;

class ColorSerializer {

    /**
     * @param string $bytes
     * @return Color[][]
     */
	public static function readColors(string $bytes): array {
		if(!($data = unpack("L*", $bytes))) {
			throw new AssumptionFailedError("Could not unpack Map image color data");
		}

		$colors = [];
		$i = 0;
		for($x = 0; $x < 128; ++$x) {
			for($y = 0; $y < 128; ++$y) {
				$color = $data[++$i] ?? null;
				if($color === null) {
					ImageOnMap::getInstance()->getLogger()->debug("Pixel at $x:$y is null");
				}

				$colors[$x][$y] = Color::fromARGB((int)$color);
			}
		}

		return $colors;
	}

    /**
     * @param Color[][] $colors
     * @return string
     */
	public static function writeColors(array $colors): string {
		$data = [];
		for($x = 0; $x < 128; ++$x) {
			for($y = 0; $y < 128; ++$y) {
				$data[] = $colors[$x][$y]->toARGB();
			}
		}

		return pack("L*", ...$data);
	}
}