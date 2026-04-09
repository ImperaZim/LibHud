<?php

declare(strict_types = 1);

namespace imperazim\hud\scoreboard;

use pocketmine\Server;
use pocketmine\player\Player;

/**
* Pre-built scoreboard templates with dynamic placeholders.
*
* Usage:
*   ScoreBoardTemplates::lobby($player, "§6MyServer");
*   ScoreBoardTemplates::gameStats($player, "§cBedWars", ["§7Kills" => "5", "§7Deaths" => "2"]);
*   ScoreBoardTemplates::playerInfo($player);
*/
final class ScoreBoardTemplates {

    /**
    * Lobby scoreboard template with server info.
    *
    * @param Player $player Target player
    * @param string $serverName Server display name
    * @param array<string, string> $extra Additional lines (label => value)
    */
    public static function lobby(Player $player, string $serverName = "§6Server"): void {
        $online = count(Server::getInstance()->getOnlinePlayers());
        $max = Server::getInstance()->getMaxPlayers();
        $tps = Server::getInstance()->getTicksPerSecond();

        $board = new ScoreBoard($serverName);
        $board->setLine(new ScoreLine(1, "§r"))
            ->setLine(new ScoreLine(2, "§fPlayer: §a" . $player->getName()))
            ->setLine(new ScoreLine(3, "§fOnline: §a{$online}§7/{$max}"))
            ->setLine(new ScoreLine(4, "§fTPS: §a{$tps}"))
            ->setLine(new ScoreLine(5, "§r§r"));

        ScoreBoardManager::sendToPlayer($player, $board);
    }

    /**
    * Game stats scoreboard template.
    *
    * @param Player $player Target player
    * @param string $title Game name
    * @param array<string, string> $stats Stat lines (label => value), max 12
    */
    public static function gameStats(Player $player, string $title, array $stats): void {
        $board = new ScoreBoard($title);
        $line = 1;

        $board->setLine(new ScoreLine($line++, "§r"));
        foreach ($stats as $label => $value) {
            if ($line > 14) break;
            $board->setLine(new ScoreLine($line++, "{$label}: §f{$value}"));
        }
        $board->setLine(new ScoreLine(min($line, 15), "§r§r"));

        ScoreBoardManager::sendToPlayer($player, $board);
    }

    /**
    * Player info scoreboard template.
    *
    * @param Player $player Target player
    * @param string $title Scoreboard title
    */
    public static function playerInfo(Player $player, string $title = "§ePlayer Info"): void {
        $board = new ScoreBoard($title);
        $board->setLine(new ScoreLine(1, "§r"))
            ->setLine(new ScoreLine(2, "§fName: §a" . $player->getName()))
            ->setLine(new ScoreLine(3, "§fHealth: §c" . (int) $player->getHealth() . "§7/" . (int) $player->getMaxHealth()))
            ->setLine(new ScoreLine(4, "§fLevel: §b" . $player->getXpManager()->getXpLevel()))
            ->setLine(new ScoreLine(5, "§fPing: §a" . $player->getNetworkSession()->getPing() . "ms"))
            ->setLine(new ScoreLine(6, "§fWorld: §a" . $player->getWorld()->getDisplayName()))
            ->setLine(new ScoreLine(7, "§r§r"));

        ScoreBoardManager::sendToPlayer($player, $board);
    }

    /**
    * Creates a scoreboard from a template array with placeholder resolution.
    *
    * Placeholders: {player}, {online}, {max}, {tps}, {health}, {max_health},
    *               {level}, {ping}, {world}, {x}, {y}, {z}
    *
    * @param Player $player Target player
    * @param string $title Scoreboard title (supports placeholders)
    * @param string[] $lines Lines (support placeholders), max 15
    */
    public static function fromTemplate(Player $player, string $title, array $lines): void {
        $title = self::resolve($player, $title);

        $board = new ScoreBoard($title);
        $score = 1;
        foreach ($lines as $line) {
            if ($score > 15) break;
            $board->setLine(new ScoreLine($score++, self::resolve($player, $line)));
        }

        ScoreBoardManager::sendToPlayer($player, $board);
    }

    /**
    * Resolves placeholders in a string.
    */
    private static function resolve(Player $player, string $text): string {
        $server = Server::getInstance();
        $pos = $player->getPosition();
        return str_replace(
            ['{player}', '{online}', '{max}', '{tps}', '{health}', '{max_health}', '{level}', '{ping}', '{world}', '{x}', '{y}', '{z}'],
            [
                $player->getName(),
                (string) count($server->getOnlinePlayers()),
                (string) $server->getMaxPlayers(),
                (string) $server->getTicksPerSecond(),
                (string) (int) $player->getHealth(),
                (string) (int) $player->getMaxHealth(),
                (string) $player->getXpManager()->getXpLevel(),
                (string) $player->getNetworkSession()->getPing(),
                $player->getWorld()->getDisplayName(),
                (string) (int) $pos->x,
                (string) (int) $pos->y,
                (string) (int) $pos->z,
            ],
            $text
        );
    }
}
