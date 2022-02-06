<?php

declare(strict_types=1);

namespace czechpmdevs\imageonmap;

use czechpmdevs\imageonmap\item\FilledMap;
use czechpmdevs\imageonmap\utils\PermissionDeniedException;
use pocketmine\block\Block;
use pocketmine\block\ItemFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\Position;
use function max;
use function min;

class ImagePlaceSession implements Listener {
	private Position $firstPosition;
	private Position $secondPosition;

	public function __construct(
		private Player $player,
		private string $imageFile,
		private ImageOnMap $plugin
	) {
	}

	public function run(): void {
		$this->player->sendMessage("§aBreak first ItemFrame.");
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
	}

	public function onBreak(BlockBreakEvent $event): void {
		$player = $event->getPlayer();
		if($player->getId() !== $this->player->getId()) {
			$player->sendMessage("{$player->getId()}:{$this->player->getId()}");
			return;
		}

		$event->cancel();

		if(!$event->getBlock()->isSameType(VanillaBlocks::ITEM_FRAME())) {
			$player->sendMessage("§6Block you want to place map on must be Item Frame.");
			return;
		}

		if(!isset($this->firstPosition)) {
			$player->sendMessage("§aFirst position set to {$event->getBlock()->getPosition()->getX()}, {$event->getBlock()->getPosition()->getY()}, {$event->getBlock()->getPosition()->getZ()}. Break second block.");
			$this->firstPosition = clone $event->getBlock()->getPosition();
			return;
		}

		if($this->firstPosition->getWorld()->getId() !== $event->getBlock()->getPosition()->getWorld()->getId()) {
			$player->sendMessage("§cSecond positions must be in same world as the first one!");
			return;
		}

		if(
			$this->firstPosition->getX() !== $event->getBlock()->getPosition()->getX() &&
			$this->firstPosition->getZ() !== $event->getBlock()->getPosition()->getZ()
		) {
			$player->sendMessage("§cImage could not be placed that way!");
			return;
		}

		$player->sendMessage("§aSecond position set to {$event->getBlock()->getPosition()->getX()}, {$event->getBlock()->getPosition()->getY()}, {$event->getBlock()->getPosition()->getZ()}");
		$this->secondPosition = clone $event->getBlock()->getPosition();

		$this->finish();
	}

	public function onChat(PlayerChatEvent $event): void {
		$player = $event->getPlayer();
		if($player->getId() !== $this->player->getId()) {
			return;
		}

		$event->cancel();

		if($event->getMessage() === "cancel") {
			$player->sendMessage("§aImage placing cancelled");
			$this->close();
			return;
		}

		$player->sendMessage("§cYou are now in 'image-place-mode'. Type §lcancel§r§c to cancel image placement.");
	}

	public function onQuit(PlayerQuitEvent $event): void {
		if($event->getPlayer()->getId() === $this->player->getId()) {
			$this->close();
		}
	}

	private function finish(): void {
		/** @var int $minX */
		$minX = min($this->firstPosition->getX(), $this->secondPosition->getX());
		/** @var int $maxX */
		$maxX = max($this->firstPosition->getX(), $this->secondPosition->getX());

		/** @var int $minY */
		$minY = min($this->firstPosition->getY(), $this->secondPosition->getY());
		/** @var int $maxY */
		$maxY = max($this->firstPosition->getY(), $this->secondPosition->getY());

		/** @var int $minZ */
		$minZ = min($this->firstPosition->getZ(), $this->secondPosition->getZ());
		/** @var int $maxZ */
		$maxZ = max($this->firstPosition->getZ(), $this->secondPosition->getZ());

		$world = $this->player->getPosition()->getWorld();

		$itemFrame = VanillaBlocks::ITEM_FRAME();
		if($minX === $maxX) {
			// West x East
			if($world->getBlock($this->firstPosition->add(1, 0, 0), true, false)->isSolid()) {
				$itemFrame->setFacing(Facing::WEST);
			} else {
				$itemFrame->setFacing(Facing::EAST);
			}
		} else {
			// North x South
			if($world->getBlock($this->firstPosition->add(0, 0, 1), true, false)->isSolid()) {
				$itemFrame->setFacing(Facing::NORTH);
			} else {
				$itemFrame->setFacing(Facing::SOUTH);
			}
		}

		$getItemFrame = function(int $x, int $y, int $z) use ($itemFrame, $world): ItemFrame {
			$block = $world->getBlockAt($x, $y, $z, true, false);
			if($block instanceof ItemFrame) {
				return $block;
			}

			$world->setBlockAt($x, $y, $z, $itemFrame);
			$block = $world->getBlockAt($x, $y, $z, true, false);
			if(!$block instanceof ItemFrame) {
				throw new AssumptionFailedError("Block must be item frame");
			}

			return $block;
		};

		/** @var ItemFrame $pattern */
		$pattern = $world->getBlock($this->secondPosition);

		/** @var Block[] $blocks */
		$blocks = [];

		try {
			$height = $maxY - $minY;
			if($minX === $maxX) {
				$width = $maxZ - $minZ;
				if($pattern->getFacing() === Facing::WEST) {
					for($x = 0; $x <= $width; ++$x) {
						for($y = 0; $y <= $height; ++$y) {
							$blocks[] = $getItemFrame($minX, $minY + $y, $minZ + $x)
								->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))
								->setHasMap(true);
						}
					}
				} else {
					for($x = 0; $x <= $width; ++$x) {
						for($y = 0; $y <= $height; ++$y) {
							$blocks[] = $getItemFrame($minX, $minY + $y, $maxZ - $x)
								->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))
								->setHasMap(true);
						}
					}
				}
			} else {
				$width = $maxX - $minX;
				if($pattern->getFacing() === Facing::SOUTH) {
					for($x = 0; $x <= $width; ++$x) {
						for($y = 0; $y <= $height; ++$y) {
							$blocks[] = $getItemFrame($minX + $x, $minY + $y, $minZ)
								->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))
								->setHasMap(true);
						}
					}
				} else {
					for($x = 0; $x <= $width; ++$x) {
						for($y = 0; $y <= $height; ++$y) {
							$blocks[] = $getItemFrame($maxX - $x, $minY + $y, $minZ)
								->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))
								->setHasMap(true);
						}
					}
				}
			}

			foreach($blocks as $block) {
				$world->setBlock($block->getPosition(), $block);
			}

			$this->player->sendMessage("§aPicture placed!");
		} catch(PermissionDeniedException) {
			$this->player->sendMessage("§cCould not access target file");
		}

		$this->close();
	}

	private function close(): void {
		HandlerListManager::global()->unregisterAll($this);
	}
}