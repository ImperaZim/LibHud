<?php

declare(strict_types = 1);

namespace imperazim\hud\notification;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use imperazim\hud\message\ToastManager;
use imperazim\hud\message\TitleManager;
use imperazim\hud\message\ActionBarManager;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use RuntimeException;

/**
* Queues notifications (toast, title, actionbar) one at a time without overlap.
*
* Usage:
*   NotificationQueue::init($plugin);
*   NotificationQueue::toast($player, "Quest Done!", "§7+50 coins", priority: 10);
*   NotificationQueue::title($player, "§6Level Up!", "§7You are now level 5");
*   NotificationQueue::actionBar($player, "§aHealth restored!");
*/
final class NotificationQueue {

    private static ?Plugin $plugin = null;

    /** @var array<string, list<array{type: string, data: array, priority: int, duration: int}>> */
    private static array $queues = [];

    /** @var array<string, bool> playerName => currently displaying */
    private static array $active = [];

    /**
    * Initializes the NotificationQueue with a plugin for scheduling.
    *
    * @param Plugin $plugin Plugin instance
    */
    public static function init(Plugin $plugin): void {
        self::$plugin = $plugin;
    }

    /**
    * Queues a toast notification.
    *
    * @param Player $player Target player
    * @param string $title Toast title
    * @param string $body Toast body
    * @param int $priority Higher = shown first (default 0)
    * @param int $durationTicks Display duration in ticks before next (default 60 = 3s)
    */
    public static function toast(Player $player, string $title, string $body, int $priority = 0, int $durationTicks = 60): void {
        self::enqueue($player, 'toast', ['title' => $title, 'body' => $body], $priority, $durationTicks);
    }

    /**
    * Queues a title notification.
    *
    * @param Player $player Target player
    * @param string $title Title text
    * @param string $subtitle Subtitle text
    * @param int $priority Higher = shown first (default 0)
    * @param int $durationTicks Display duration in ticks (default 60)
    */
    public static function title(Player $player, string $title, string $subtitle = "", int $priority = 0, int $durationTicks = 60): void {
        self::enqueue($player, 'title', ['title' => $title, 'subtitle' => $subtitle], $priority, $durationTicks);
    }

    /**
    * Queues an action bar notification.
    *
    * @param Player $player Target player
    * @param string $message Action bar text
    * @param int $priority Higher = shown first (default 0)
    * @param int $durationTicks Display duration in ticks (default 40)
    */
    public static function actionBar(Player $player, string $message, int $priority = 0, int $durationTicks = 40): void {
        self::enqueue($player, 'actionbar', ['message' => $message], $priority, $durationTicks);
    }

    /**
    * Clears all queued notifications for a player.
    *
    * @param Player $player Target player
    */
    public static function clear(Player $player): void {
        unset(self::$queues[$player->getName()], self::$active[$player->getName()]);
    }

    /**
    * Cleans up queue for a player on quit.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        self::clear($player);
    }

    /**
    * Gets the number of pending notifications for a player.
    *
    * @param Player $player Target player
    * @return int Queue size
    */
    public static function pending(Player $player): int {
        return count(self::$queues[$player->getName()] ?? []);
    }

    private static function enqueue(Player $player, string $type, array $data, int $priority, int $durationTicks): void {
        $name = $player->getName();
        self::$queues[$name][] = [
            'type' => $type,
            'data' => $data,
            'priority' => $priority,
            'duration' => $durationTicks,
        ];

        usort(self::$queues[$name], fn($a, $b) => $b['priority'] <=> $a['priority']);

        if (!isset(self::$active[$name]) || !self::$active[$name]) {
            self::processNext($player);
        }
    }

    private static function processNext(Player $player): void {
        $name = $player->getName();

        if (empty(self::$queues[$name]) || !$player->isConnected()) {
            self::$active[$name] = false;
            return;
        }

        self::$active[$name] = true;
        $notification = array_shift(self::$queues[$name]);

        match ($notification['type']) {
            'toast' => ToastManager::send($player, $notification['data']['title'], $notification['data']['body']),
            'title' => TitleManager::send($player, $notification['data']['title'], $notification['data']['subtitle']),
            'actionbar' => ActionBarManager::send($player, $notification['data']['message']),
            default => null,
        };

        self::getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($player, $name): void {
                if ($player->isConnected() && isset(self::$queues[$name])) {
                    self::processNext($player);
                } else {
                    self::$active[$name] = false;
                }
            }),
            $notification['duration']
        );
    }

    private static function getScheduler(): TaskScheduler {
        if (self::$plugin !== null) {
            return self::$plugin->getScheduler();
        }
        $plugins = Server::getInstance()->getPluginManager()->getPlugins();
        foreach ($plugins as $plugin) {
            if ($plugin->isEnabled()) {
                return $plugin->getScheduler();
            }
        }
        throw new RuntimeException("NotificationQueue: No plugin available. Call NotificationQueue::init(\$plugin) first.");
    }
}
