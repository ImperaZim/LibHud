<?php

declare(strict_types = 1);

namespace imperazim\hud\nametag;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

/**
* Manages custom nametags above players.
*
* Usage:
*   NameTagManager::set($player, "§a[VIP] §f{name} §c{health}HP");
*   NameTagManager::setFor($observer, $target, "§6[Enemy] §c{name}");
*   NameTagManager::reset($player);
*/
final class NameTagManager {

    /** @var array<int, string> playerId => format string (global) */
    private static array $globalTags = [];

    /** @var array<int, array<int, string>> targetId => [observerId => format] */
    private static array $perObserverTags = [];

    /**
    * Sets a global custom nametag format for a player (visible to all).
    *
    * Supported placeholders: {name}, {health}, {max_health}, {level}
    *
    * @param Player $target The player whose nametag to change
    * @param string $format Nametag format with placeholders
    */
    public static function set(Player $target, string $format): void {
        self::$globalTags[$target->getId()] = $format;
        self::refresh($target);
    }

    /**
    * Sets a per-observer custom nametag (only visible to specific player).
    *
    * @param Player $observer The player who will see the custom nametag
    * @param Player $target The player whose nametag to change
    * @param string $format Nametag format with placeholders
    */
    public static function setFor(Player $observer, Player $target, string $format): void {
        self::$perObserverTags[$target->getId()][$observer->getId()] = $format;
        self::sendTag($observer, $target, $format);
    }

    /**
    * Resets a player's nametag to default.
    *
    * @param Player $target The player to reset
    */
    public static function reset(Player $target): void {
        unset(self::$globalTags[$target->getId()], self::$perObserverTags[$target->getId()]);
        self::refreshDefault($target);
    }

    /**
    * Refreshes the nametag for all online observers.
    *
    * @param Player $target The player whose nametag to refresh
    */
    public static function refresh(Player $target): void {
        if (!isset(self::$globalTags[$target->getId()])) {
            self::refreshDefault($target);
            return;
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $observer) {
            if ($observer->getId() === $target->getId()) continue;

            $format = self::$perObserverTags[$target->getId()][$observer->getId()]
                ?? self::$globalTags[$target->getId()]
                ?? null;

            if ($format !== null) {
                self::sendTag($observer, $target, $format);
            }
        }
    }

    /**
    * Cleans up nametag data for a player. Call on PlayerQuitEvent.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        $id = $player->getId();
        unset(self::$globalTags[$id], self::$perObserverTags[$id]);

        // Also remove this player as observer from all targets
        foreach (self::$perObserverTags as $targetId => &$observers) {
            unset($observers[$id]);
            if (empty($observers)) {
                unset(self::$perObserverTags[$targetId]);
            }
        }
        unset($observers);
    }

    /**
    * Resolves placeholders in the format string.
    */
    private static function resolve(string $format, Player $target): string {
        return str_replace(
            ['{name}', '{health}', '{max_health}', '{level}'],
            [
                $target->getName(),
                (string) (int) $target->getHealth(),
                (string) (int) $target->getMaxHealth(),
                (string) $target->getXpManager()->getXpLevel(),
            ],
            $format
        );
    }

    /**
    * Sends a custom nametag packet to an observer.
    */
    private static function sendTag(Player $observer, Player $target, string $format): void {
        if (!$observer->isConnected() || !$target->isConnected()) return;

        $resolved = self::resolve($format, $target);
        $observer->getNetworkSession()->sendDataPacket(
            SetActorDataPacket::create(
                $target->getId(),
                [
                    EntityMetadataProperties::NAMETAG => new StringMetadataProperty($resolved),
                ],
                new PropertySyncData([], []),
                0
            )
        );
    }

    /**
    * Sends the original nametag to all observers.
    */
    private static function refreshDefault(Player $target): void {
        if (!$target->isConnected()) return;

        foreach (Server::getInstance()->getOnlinePlayers() as $observer) {
            if ($observer->getId() === $target->getId()) continue;
            if (!$observer->isConnected()) continue;

            $observer->getNetworkSession()->sendDataPacket(
                SetActorDataPacket::create(
                    $target->getId(),
                    [
                        EntityMetadataProperties::NAMETAG => new StringMetadataProperty($target->getNameTag()),
                    ],
                    new PropertySyncData([], []),
                    0
                )
            );
        }
    }
}
