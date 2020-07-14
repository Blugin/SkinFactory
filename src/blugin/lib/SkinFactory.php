<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\lib;

use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\VersionString;

class SkinFactory extends PluginBase{
    use SingletonTrait;

    /** @var SkinImage[] key => skin image */
    private $skinImages = [];
    /** @var string[] key => geometry data */
    private $geometryDatas = [];

    /** @var SkinData[] key => cached skin data instance */
    private $cachedSkinData = [];

    /**
     * @param string    $key
     * @param SkinImage $skinImage
     */
    public function registerImage(string $key, SkinImage $skinImage) : void{
        $this->skinImages[$key] = $skinImage;
    }

    /**
     * @param string $key
     *
     * @return SkinImage|null
     */
    public function getImage(string $key) : ?SkinImage{
        return $this->skinImages[$key] ?? null;
    }

    /**
     * @param string $key
     * @param string $geometryData
     */
    public function registerGeometry(string $key, string $geometryData) : void{
        $this->geometryDatas[$key] = $geometryData;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getGeometry(string $key) : ?string{
        return $this->geometryDatas[$key] ?? null;
    }

    /**
     * @param string $skinImageKey
     * @param string $geometryDataKey
     *
     * @return null|SkinData
     */
    public function get(string $skinImageKey, string $geometryDataKey) : ?SkinData{
        $skinImage = $this->skinImages[$skinImageKey] ?? null;
        if(!$skinImage)
            return null;

        $geometryData = $this->geometryDatas[$geometryDataKey] ?? null;
        if(!$geometryData)
            return null;

        $cacheKey = "$skinImageKey\\$geometryDataKey";
        if(!isset($this->cachedSkinData[$cacheKey])){
            $resourcePatch = json_encode(["geometry" => ["default" => self::getGeometryNameFromData(json_decode($geometryData, true))]]);
            $this->cachedSkinData[$cacheKey] = new SkinData($skinImageKey, $resourcePatch, $skinImage, [], null, $geometryData);
        }
        return clone $this->cachedSkinData[$cacheKey];
    }

    /**
     * @param array $data
     *
     * @return string
     *
     * @throw InvalidSkinException
     */
    public function getGeometryNameFromData(array $data) : string{
        $formatVersion = $data["format_version"] ?? null;
        $keys = array_keys($data);
        if($formatVersion === null){
            return $keys[0] ?? "undefined";
        }else{
            unset($data["format_version"]);
        }

        try{
            $version = new VersionString($formatVersion);
            if($version->compare(new VersionString("1.12.0")) === 1){ //format version < 1.12.0
                return $keys[0];
            }else{
                return $data["minecraft:geometry"][0]["description"]["identifier"];
            }
        }catch(\Exception $e){
            throw new InvalidSkinException("Invalid geometry data (format_version: $formatVersion)");
        }
    }
}
