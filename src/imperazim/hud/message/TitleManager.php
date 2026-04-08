<?php

declare(strict_types = 1);

namespace imperazim\hud\message;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

/**
* API for sending Title & Subtitle screen messages with fade in/out.
*
* Usage:
*   TitleManager::send($player, "§6Welcome!", "§7Enjoy your stay", fadeIn: 10, stay: 40, fadeOut: 10);
*   TitleManager::sendSubtitle($player, "§cWatch out!");
*   TitleManager::clear($player);
*/
final class TitleManager {

    /**
    * Sends a title and optional subtitle to a player.
    *
    * @param Player $player Target player
    * @param string $title Title text
    * @param string $subtitle Subtitle text (empty = no subtitle)
    * @param int $fadeIn Fade in duration in ticks (default 10)
    * @param int $stay Stay duration in ticks (default 40)
    * @param int $fadeOut Fade out duration in ticks (default 10)
    */
    public static function send(
        Player $player,
        string $title,
        string $subtitle = "",
        int $fadeIn = 10,
        int $stay = 40,
        int $fadeOut = 10
    ): void {
        if (!$player->isConnected()) {
            return;
        }

        $session = $player->getNetworkSession();

        // Set timing
        $timePk = new SetTitlePacket();
        $timePk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
        $timePk->fadeInTime = $fadeIn;
        $timePk->stayTime = $stay;
        $timePk->fadeOutTime = $fadeOut;
        $timePk->text = "";
        $session->sendDataPacket($timePk);

        // Set subtitle first (must be before title)
        if ($subtitle !== "") {
            $subPk = new SetTitlePacket();
            $subPk->type = SetTitlePacket::TYPE_SET_SUBTITLE;
            $subPk->text = $subtitle;
            $session->sendDataPacket($subPk);
        }

        // Set title (triggers display)
        $titlePk = new SetTitlePacket();
        $titlePk->type = SetTitlePacket::TYPE_SET_TITLE;
        $titlePk->text = $title;
        $session->sendDataPacket($titlePk);
    }

    /**
    * Sends only a subtitle (requires a title to be active or sends empty title).
    *
    * @param Player $player Target player
    * @param string $subtitle Subtitle text
    */
    public static function sendSubtitle(Player $player, string $subtitle): void {
        self::send($player, "", $subtitle);
    }

    /**
    * Sends a title and subtitle to multiple players.
    *
    * @param Player[] $players Target players
    * @param string $title Title text
    * @param string $subtitle Subtitle text
    * @param int $fadeIn Fade in ticks
    * @param int $stay Stay ticks
    * @param int $fadeOut Fade out ticks
    */
    public static function sendToAll(
        array $players,
        string $title,
        string $subtitle = "",
        int $fadeIn = 10,
        int $stay = 40,
        int $fadeOut = 10
    ): void {
        foreach ($players as $player) {
            self::send($player, $title, $subtitle, $fadeIn, $stay, $fadeOut);
        }
    }

    /**
    * Clears the title display for a player.
    *
    * @param Player $player Target player
    */
    public static function clear(Player $player): void {
        if (!$player->isConnected()) {
            return;
        }

        $pk = new SetTitlePacket();
        $pk->type = SetTitlePacket::TYPE_CLEAR_TITLE;
        $pk->text = "";
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    /**
    * Resets title settings (timing, text) for a player.
    *
    * @param Player $player Target player
    */
    public static function reset(Player $player): void {
        if (!$player->isConnected()) {
            return;
        }

        $pk = new SetTitlePacket();
        $pk->type = SetTitlePacket::TYPE_RESET_TITLE;
        $pk->text = "";
        $player->getNetworkSession()->sendDataPacket($pk);
    }
}
