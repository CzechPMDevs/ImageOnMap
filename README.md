<h1>ImageOnMap</h1> 
<a href="https://poggit.pmmp.io/ci/CzechPMDevs/ImageOnMap/ImageOnMap">  
    <img src="https://poggit.pmmp.io/ci.shield/CzechPMDevs/ImageOnMap/ImageOnMap?style=flat-square">  
</a>  
<a href="https://discord.gg/uwBf2jS">  
    <img src="https://img.shields.io/discord/365202594932719616.svg?style=flat-square">  
</a>  
<a href="https://github.com/CzechPMDevs/ImageOnMap/releases">  
    <img src="https://img.shields.io/github/release/CzechPMDevs/ImageOnMap.svg?style=flat-square">  
</a>  
<a href="https://github.com/CzechPMDevs/ImageOnMap/releases">  
    <img src="https://img.shields.io/github/downloads/CzechPMDevs/ImageOnMap/total.svg?style=flat-square">  
</a>
<a href="https://github.com/CzechPMDevs/ImageOnMap/blob/master/LICENSE">  
    <img src="https://img.shields.io/github/license/CzechPMDevs/ImageOnMap.svg?style=flat-square">  
</a>  
<a href="https://poggit.pmmp.io/p/ImageOnMap">  
    <img src="https://poggit.pmmp.io/shield.downloads/ImageOnMap?style=flat-square">  
</a>
<br><br>  
✔️ Simple usage, without external convertors
<br>  
✔️ Supporting both .png and .jpg image formats
<br>  
✔️ Image is automatically resized to fit item frame
<br>
✔️ Supports last PocketMine API version
<br>

## Commands

- Plugin implements command `/image` with aliases `/img` and `/iom`.
- To use this command, **permission** `imageonmap.command` **is needed**.
- This command can be used only in game.
  <br>
- There are implemented these subcommands:

| **Sub-Command** | **Description** |
|---|---|
| /img help | Shows all the available subcommands |
| /img list | Displays all the available images found in `/plugin_data/ImageOnMap/images/*` path. |
| /img obtain `<image>` `[<xChunkCount> <yChunkCount> <x> <y>]` | Obtains specific image (or it's specific part) from file as map item.<br><br>Chunk count argument represents to how many parts should be the image split. X and Y coordinates represents which part of that chunked image will be given to player's inventory.<br><br>Aliases: /img o |
| /img place `<image>` | Places the whole images on to item frames in selected area.<br><br>To place an image properly, first execute the command (`/img p image`). Afterwards, break the first corner of the target position and then break the block to select second position. The image will be placed automatically.<br><br>Aliases: /img p |

## API

- Obtaining plugin instance

```php
$api = \czechpmdevs\imageonmap\ImageOnMap::getInstance();
```

- Loading image from file

```php
// This method caches the map and returns its id. Afterwards the id can be used to obtain map item.
$id = $api->getImageFromFile(
	file: "path/to/image.png",
	xChunkCount: 1,
	yChunkCount: 1,
	xOffset: 0,
	yOffset: 0
);
```

- Obtaining Map item, assigning id & giving it to player

```php
/** @var \czechpmdevs\imageonmap\item\FilledMap $map */
$map = (FilledMap::get())->setMapId($id);
/** @var \pocketmine\player\Player $player */
$player->getInventory()->addItem($map);
```