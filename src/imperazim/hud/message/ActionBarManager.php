<?php

declare(strict_types = 1);

namespace imperazim\hud\message;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\TextPacket;

/**
* Manages action bar messages (text above the hotbar).
*
* Usage:
*   ActionBarManager::send($player, "§aHealth: §c20/20");
*   ActionBarManager::sendToAll($server->getOnlinePlayers(), "§eServer restarting in 5 minutes");
*/
final class ActionBarManager {

    /**
    * Sends an action bar message to a player.
    *
    * @param Player $player Target player
    * @param string $message Message text (supports § color codes)
    */
    public static function send(Player $player, string $message): void {
        if (!$player->isConnected()) {
            return;
        }

        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_JUKEBOX_POPUP;
        $pk->message = $message;
        $pk->needsTranslation = false;
        $pk->parameters = [];
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
    * Sends an action bar message to multiple players.
    *
    * @param Player[] $players Target players
    * @param string $message Message text
    */
    public static function sendToAll(array $players, string $message): void {
        foreach ($players as $player) {
            self::send($player, $message);
        }
    }

    /**
    * Clears the action bar for a player.
    *
    * @param Player $player Target player
    */
    public static function clear(Player $player): void {
        self::send($player, "");
    }
}
