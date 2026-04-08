<?php

declare(strict_types = 1);

namespace imperazim\hud\scoreboard;

use Closure;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

/**
* Automatically updates scoreboards on a timer with dynamic placeholders.
*
* Usage:
*   $updater = new PeriodicUpdate($plugin, intervalTicks: 20);
*   $updater->register($player, "stats", function(Player $p): ScoreBoard {
*       $sb = new ScoreBoard("§9Stats");
*       $sb->setLine(new ScoreLine(1, "§fOnline: §a" . count(Server::getInstance()->getOnlinePlayers())));
*       $sb->setLine(new ScoreLine(2, "§fTPS: §a" . Server::getInstance()->getTicksPerSecond()));
*       $sb->setLine(new ScoreLine(3, "§fPing: §a" . $p->getNetworkSession()->getPing() . "ms"));
*       return $sb;
*   });
*   // Later:
*   $updater->unregister($player, "stats");
*   $updater->stop();
*/
final class PeriodicUpdate {

    /** @var array<int, array<string, Closure>> Player ID => key => factory */
    private array $factories = [];

    private ?TaskHandler $taskHandler = null;

    /**
    * @param Plugin $plugin Plugin instance for scheduling
    * @param int $intervalTicks Update interval in ticks (20 = 1 second)
    */
    public function __construct(
        private Plugin $plugin,
        private int $intervalTicks = 20
    ) {
        $this->start();
    }

    /**
    * Registers a scoreboard updater for a player.
    *
    * @param Player $player Target player
    * @param string $key Unique identifier for this update
    * @param Closure $factory fn(Player): ScoreBoard — called each tick interval
    */
    public function register(Player $player, string $key, Closure $factory): void {
        $this->factories[$player->getId()][$key] = $factory;
    }

    /**
    * Unregisters a scoreboard updater.
    *
    * @param Player $player Target player
    * @param string $key Update identifier
    */
    public function unregister(Player $player, string $key): void {
        unset($this->factories[$player->getId()][$key]);
        if (empty($this->factories[$player->getId()])) {
            unset($this->factories[$player->getId()]);
        }
    }

    /**
    * Unregisters all updaters for a player.
    *
    * @param Player $player Target player
    */
    public function unregisterAll(Player $player): void {
        unset($this->factories[$player->getId()]);
    }

    /**
    * Checks if a player has a registered updater.
    *
    * @param Player $player Target player
    * @param string|null $key Specific key, or null to check any
    */
    public function has(Player $player, ?string $key = null): bool {
        if ($key !== null) {
            return isset($this->factories[$player->getId()][$key]);
        }
        return isset($this->factories[$player->getId()]);
    }

    /**
    * Starts the periodic update task.
    */
    public function start(): void {
        if ($this->taskHandler !== null) {
            return;
        }

        $this->taskHandler = $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                $this->tick();
            }),
            $this->intervalTicks
        );
    }

    /**
    * Stops the periodic update task.
    */
    public function stop(): void {
        if ($this->taskHandler !== null) {
            $this->taskHandler->cancel();
            $this->taskHandler = null;
        }
    }

    /**
    * Sets the update interval.
    *
    * @param int $ticks Interval in ticks
    */
    public function setInterval(int $ticks): void {
        $this->intervalTicks = max(1, $ticks);
        $this->stop();
        $this->start();
    }

    /**
    * Gets count of registered players.
    */
    public function getPlayerCount(): int {
        return count($this->factories);
    }

    private function tick(): void {
        $server = $this->plugin->getServer();
        $onlinePlayers = $server->getOnlinePlayers();

        // Build a map of runtime ID => Player for quick lookup
        $playerMap = [];
        foreach ($onlinePlayers as $p) {
            $playerMap[$p->getId()] = $p;
        }

        foreach ($this->factories as $playerId => $keys) {
            $player = $playerMap[$playerId] ?? null;
            if ($player === null || !$player->isConnected()) {
                unset($this->factories[$playerId]);
                continue;
            }

            foreach ($keys as $factory) {
                $scoreboard = $factory($player);
                if ($scoreboard instanceof ScoreBoard) {
                    ScoreBoardManager::sendToPlayer($player, $scoreboard);
                }
            }
        }
    }
}
