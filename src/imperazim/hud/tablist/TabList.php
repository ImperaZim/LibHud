<?php

declare(strict_types = 1);

namespace imperazim\hud\tablist;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

/**
* Modifies the player list (TAB) header and footer.
*
* Note: Uses SetTitlePacket with Bedrock-specific subtypes for
* player list header (type 10) and footer (type 11).
*
* Usage:
*   TabList::send($player, "§6MyServer", "§7Online: §f42");
*   TabList::sendToAll($server->getOnlinePlayers(), "§bWelcome!", "§7play.myserver.com");
*   TabList::clear($player);
*/
final class TabList {

    /** SetTitlePacket type for player list header */
    private const TYPE_HEADER = 10;

    /** SetTitlePacket type for player list footer */
    private const TYPE_FOOTER = 11;

    /** @var array<int, array{header: string, footer: string}> Cache per player ID */
    private static array $cache = [];

    /**
    * Sets the TAB list header and footer for a player.
    *
    * @param Player $player Target player
    * @param string $header Header text (supports § color codes and \n)
    * @param string $footer Footer text
    */
    public static function send(Player $player, string $header, string $footer = ""): void {
        if (!$player->isConnected()) {
            return;
        }

        self::$cache[$player->getId()] = ['header' => $header, 'footer' => $footer];

        $session = $player->getNetworkSession();

        $headerPk = new SetTitlePacket();
        $headerPk->type = self::TYPE_HEADER;
        $headerPk->text = $header;
        $session->sendDataPacket($headerPk);

        $footerPk = new SetTitlePacket();
        $footerPk->type = self::TYPE_FOOTER;
        $footerPk->text = $footer;
        $session->sendDataPacket($footerPk);
    }

    /**
    * Sends header/footer to multiple players.
    *
    * @param Player[] $players Target players
    * @param string $header Header text
    * @param string $footer Footer text
    */
    public static function sendToAll(array $players, string $header, string $footer = ""): void {
        foreach ($players as $player) {
            self::send($player, $header, $footer);
        }
    }

    /**
    * Clears the TAB header and footer for a player.
    *
    * @param Player $player Target player
    */
    public static function clear(Player $player): void {
        self::send($player, "", "");
        unset(self::$cache[$player->getId()]);
    }

    /**
    * Gets the cached header for a player.
    *
    * @param Player $player Target player
    * @return string|null Header text or null if not set
    */
    public static function getHeader(Player $player): ?string {
        return self::$cache[$player->getId()]['header'] ?? null;
    }

    /**
    * Gets the cached footer for a player.
    *
    * @param Player $player Target player
    * @return string|null Footer text or null if not set
    */
    public static function getFooter(Player $player): ?string {
        return self::$cache[$player->getId()]['footer'] ?? null;
    }

    /**
    * Cleans up cache for disconnected player.
    *
    * @param Player $player Disconnected player
    */
    public static function cleanup(Player $player): void {
        unset(self::$cache[$player->getId()]);
    }
}
