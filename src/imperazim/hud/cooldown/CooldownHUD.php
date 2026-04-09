<?php

declare(strict_types = 1);

namespace imperazim\hud\cooldown;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use imperazim\hud\bossbar\BossBar;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use RuntimeException;

/**
* Visual cooldown HUD using a temporary BossBar with decreasing progress.
*
* Usage:
*   CooldownHUD::init($plugin);
*   CooldownHUD::show($player, "ability.dash", "§cDash Cooldown", 5.0);
*   CooldownHUD::show($player, "item.potion", "§aPotion", 10.0, BossBarColor::GREEN);
*/
final class CooldownHUD {

    private static ?Plugin $plugin = null;

    /** @var array<string, array{bar: BossBar, handler: TaskHandler, total: float}> */
    private static array $activeBars = [];

    /**
    * Initializes the CooldownHUD with a plugin for scheduling.
    *
    * @param Plugin $plugin Plugin instance
    */
    public static function init(Plugin $plugin): void {
        self::$plugin = $plugin;
    }

    /**
    * Shows a cooldown bar to a player that decreases over time and auto-removes.
    *
    * @param Player $player Target player
    * @param string $key Unique cooldown identifier
    * @param string $title Display title on the bar
    * @param float $seconds Total cooldown duration in seconds
    * @param int $color BossBarColor constant (default PURPLE)
    * @param int $updateIntervalTicks How often to update the bar (default 2 = 0.1s)
    */
    public static function show(
        Player $player,
        string $key,
        string $title,
        float $seconds,
        int $color = BossBarColor::PURPLE,
        int $updateIntervalTicks = 2
    ): void {
        $id = $player->getName() . ':' . $key;

        if (isset(self::$activeBars[$id])) {
            self::hide($player, $key);
        }

        $bar = new BossBar();
        $bar->setTitle($title)
            ->setColor($color)
            ->setPercentage(1.0)
            ->addPlayer($player);

        $startTime = microtime(true);
        $totalSeconds = $seconds;

        $handler = self::getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function () use ($player, $key, $id, $bar, $startTime, $totalSeconds): void {
                if (!$player->isConnected() || !isset(self::$activeBars[$id])) {
                    self::hide($player, $key);
                    return;
                }

                $elapsed = microtime(true) - $startTime;
                $remaining = $totalSeconds - $elapsed;

                if ($remaining <= 0) {
                    self::hide($player, $key);
                    return;
                }

                $bar->setPercentage($remaining / $totalSeconds);
            }),
            $updateIntervalTicks
        );

        self::$activeBars[$id] = [
            'bar' => $bar,
            'handler' => $handler,
            'total' => $seconds,
        ];
    }

    /**
    * Hides and removes a cooldown bar.
    *
    * @param Player $player Target player
    * @param string $key Cooldown identifier
    */
    public static function hide(Player $player, string $key): void {
        $id = $player->getName() . ':' . $key;
        if (!isset(self::$activeBars[$id])) return;

        $data = self::$activeBars[$id];
        $data['bar']->removeAllPlayers();
        $data['handler']->cancel();
        unset(self::$activeBars[$id]);
    }

    /**
    * Checks if a cooldown bar is active for a player.
    *
    * @param Player $player Target player
    * @param string $key Cooldown identifier
    * @return bool
    */
    public static function isActive(Player $player, string $key): bool {
        return isset(self::$activeBars[$player->getName() . ':' . $key]);
    }

    /**
    * Cleans up all cooldown bars for a player. Call on PlayerQuitEvent.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        $prefix = $player->getName() . ':';
        foreach (array_keys(self::$activeBars) as $id) {
            if (str_starts_with($id, $prefix)) {
                $data = self::$activeBars[$id];
                $data['bar']->removeAllPlayers();
                $data['handler']->cancel();
                unset(self::$activeBars[$id]);
            }
        }
    }

    private static function getScheduler(): TaskScheduler {
        if (self::$plugin !== null) {
            return self::$plugin->getScheduler();
        }
        // Fallback: try to find an enabled plugin
        $plugins = Server::getInstance()->getPluginManager()->getPlugins();
        foreach ($plugins as $plugin) {
            if ($plugin->isEnabled()) {
                return $plugin->getScheduler();
            }
        }
        throw new RuntimeException("CooldownHUD: No plugin available. Call CooldownHUD::init(\$plugin) first.");
    }
}
