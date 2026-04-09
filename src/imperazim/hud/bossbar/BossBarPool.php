<?php

declare(strict_types = 1);

namespace imperazim\hud\bossbar;

use pocketmine\player\Player;

/**
* Manages multiple BossBars without conflicts between plugins.
*
* Usage:
*   $bar = BossBarPool::create("quest_progress");
*   $bar->setTitle("Quest: Defeat 10 Mobs")->setPercentage(0.5)->setColor(BossBarColor::GREEN);
*   BossBarPool::show($player, "quest_progress");
*   BossBarPool::hide($player, "quest_progress");
*   BossBarPool::remove("quest_progress");
*/
final class BossBarPool {

    /** Maximum simultaneous boss bars per player (Bedrock client limit). */
    public const MAX_BARS_PER_PLAYER = 5;

    /** @var array<string, BossBar> id => BossBar instance */
    private static array $bars = [];

    /** @var array<int, array<string, bool>> playerId => [barId => true] */
    private static array $activeBars = [];

    /**
    * Creates and registers a new BossBar.
    *
    * @param string $id Unique identifier for this bar
    * @return BossBar The created bar
    */
    public static function create(string $id): BossBar {
        if (isset(self::$bars[$id])) {
            return self::$bars[$id];
        }

        $bar = new BossBar();
        self::$bars[$id] = $bar;
        return $bar;
    }

    /**
    * Gets a BossBar by id.
    *
    * @param string $id Bar identifier
    * @return BossBar|null
    */
    public static function get(string $id): ?BossBar {
        return self::$bars[$id] ?? null;
    }

    /**
    * Shows a boss bar to a player.
    *
    * @param Player $player Target player
    * @param string $id Bar identifier
    * @return bool False if bar not found or player at limit
    */
    public static function show(Player $player, string $id): bool {
        $bar = self::$bars[$id] ?? null;
        if ($bar === null) return false;

        $pid = $player->getId();
        $active = self::$activeBars[$pid] ?? [];

        if (isset($active[$id])) return true; // Already showing

        if (count($active) >= self::MAX_BARS_PER_PLAYER) {
            return false; // At Bedrock limit
        }

        $bar->addPlayer($player);
        self::$activeBars[$pid][$id] = true;
        return true;
    }

    /**
    * Hides a boss bar from a player.
    *
    * @param Player $player Target player
    * @param string $id Bar identifier
    */
    public static function hide(Player $player, string $id): void {
        $bar = self::$bars[$id] ?? null;
        if ($bar === null) return;

        $bar->removePlayer($player);
        unset(self::$activeBars[$player->getId()][$id]);
    }

    /**
    * Removes a boss bar entirely and despawns from all players.
    *
    * @param string $id Bar identifier
    */
    public static function remove(string $id): void {
        $bar = self::$bars[$id] ?? null;
        if ($bar === null) return;

        $bar->removeAllPlayers();

        // Clean up active bars references
        foreach (self::$activeBars as $pid => &$active) {
            unset($active[$id]);
            if (empty($active)) {
                unset(self::$activeBars[$pid]);
            }
        }
        unset($active);

        unset(self::$bars[$id]);
    }

    /**
    * Gets all active bar ids for a player.
    *
    * @param Player $player Target player
    * @return string[] Active bar ids
    */
    public static function getActive(Player $player): array {
        return array_keys(self::$activeBars[$player->getId()] ?? []);
    }

    /**
    * Gets the count of active bars for a player.
    *
    * @param Player $player Target player
    * @return int Number of active bars
    */
    public static function getActiveCount(Player $player): int {
        return count(self::$activeBars[$player->getId()] ?? []);
    }

    /**
    * Checks if a bar exists.
    *
    * @param string $id Bar identifier
    * @return bool
    */
    public static function exists(string $id): bool {
        return isset(self::$bars[$id]);
    }

    /**
    * Cleans up all boss bars for a player. Call on PlayerQuitEvent.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        $pid = $player->getId();
        $active = self::$activeBars[$pid] ?? [];

        foreach (array_keys($active) as $id) {
            $bar = self::$bars[$id] ?? null;
            if ($bar !== null) {
                $bar->removePlayer($player);
            }
        }

        unset(self::$activeBars[$pid]);
    }
}
