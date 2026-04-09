<?php

declare(strict_types = 1);

namespace imperazim\hud\message;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;

/**
* Sends achievement-style toast notifications (icon + title + body).
*
* Usage:
*   ToastManager::send($player, "§aQuest Complete!", "§7You earned 50 coins");
*   ToastManager::sendToAll($server->getOnlinePlayers(), "§eServer", "§7Restarting in 5 minutes");
*/
final class ToastManager {

    /**
    * Sends a toast notification to a player.
    *
    * @param Player $player Target player
    * @param string $title Toast title text
    * @param string $body Toast body text
    */
    public static function send(Player $player, string $title, string $body): void {
        if (!$player->isConnected()) {
            return;
        }

        $player->getNetworkSession()->sendDataPacket(
            ToastRequestPacket::create($title, $body)
        );
    }

    /**
    * Sends a toast notification to multiple players.
    *
    * @param Player[] $players Target players
    * @param string $title Toast title text
    * @param string $body Toast body text
    */
    public static function sendToAll(array $players, string $title, string $body): void {
        foreach ($players as $player) {
            self::send($player, $title, $body);
        }
    }
}
