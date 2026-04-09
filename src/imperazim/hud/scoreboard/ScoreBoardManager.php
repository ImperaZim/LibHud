<?php

declare(strict_types = 1);

namespace imperazim\hud\scoreboard;

use GlobalLogger;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use Throwable;

/**
* Class ScoreBoardManager
* @package imperazim\hud\scoreboard
*/
final class ScoreBoardManager {

  /** @var Array<string, ScoreBoard> */
  public static array $scoreboards = [];

  /**
  * Send a scoreboard for a player.
  * @param Player $player
  * @param ScoreBoard $scoreboard
  */
  public static function sendToPlayer(Player $player, ScoreBoard $scoreboard): void {
    try {
      if (isset(self::$scoreboards[$player->getName()])) {
        self::removeFromPlayer($player);
      }
      self::$scoreboards[$player->getName()] = $scoreboard;
      $player->getNetworkSession()->sendDataPacket($scoreboard->toPacket());

      $lines = $scoreboard->getLines();
      $player->getNetworkSession()->sendDataPacket($lines);
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Removes the scoreboard of a player.
  * @param Player $player
  */
  public static function removeFromPlayer(Player $player): void {
    try {
      if (isset(self::$scoreboards[$player->getName()])) {
        $scoreboard = self::$scoreboards[$player->getName()];
        $objectiveName = $scoreboard->getObjectiveName();
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
        unset(self::$scoreboards[$player->getName()]);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Clears a specific line on the scoreboard for a player.
  * @param Player $player
  * @param int $score
  */
  public static function clearLine(Player $player, int $score): void {
    try {
      if (isset(self::$scoreboards[$player->getName()])) {
        $scoreboard = self::$scoreboards[$player->getName()];
        $entry = new ScoreLine($score, "");
        $entry->objectiveName = $scoreboard->getObjectiveName();

        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_REMOVE;
        $pk->entries = [$entry->toEntry()];
        $player->getNetworkSession()->sendDataPacket($pk);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Clears all lines on the scoreboard for a player.
  * @param Player $player
  */
  public static function clearAllLines(Player $player): void {
    try {
      if (isset(self::$scoreboards[$player->getName()])) {
        $scoreboard = self::$scoreboards[$player->getName()];
        $entries = [];
        for ($i = 1; $i <= 15; $i++) {
          $entry = new ScoreLine($i, "");
          $entry->objectiveName = $scoreboard->getObjectiveName();
          $entries[] = $entry->toEntry();
        }
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_REMOVE;
        $pk->entries = $entries;
        $player->getNetworkSession()->sendDataPacket($pk);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Updates the scoreboard for a player.
  * @param Player $player
  */
  public static function updateToPlayer(Player $player): void {
    try {
      if (isset(self::$scoreboards[$player->getName()])) {
        $scoreboard = self::$scoreboards[$player->getName()];
        self::sendToPlayer($player, $scoreboard);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Cleans up the scoreboard entry for a player (call on quit).
  * @param Player $player
  */
  public static function cleanup(Player $player): void {
    unset(self::$scoreboards[$player->getName()]);
  }

  /**
  * Get's ScoreBoard from a player.
  * @return ScoreBoard|null
  */
  public static function getScoreBoardFromPlayer(Player $player): ?ScoreBoard {
    if (isset(self::$scoreboards[$player->getName()])) {
      return self::$scoreboards[$player->getName()];
    }
    return null;
  }
}
