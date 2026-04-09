<?php

declare(strict_types = 1);

namespace imperazim\hud\scoreboard;

use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use InvalidArgumentException;

/**
* Class ScoreLine
* @package imperazim\hud\scoreboard
*/
final class ScoreLine {

  public const TYPE_FAKE_PLAYER = ScorePacketEntry::TYPE_FAKE_PLAYER;

	public int $type;
	public int $score;
  public int $scoreboardId;
	public string $objectiveName = '';
  private string $customName;

  /**
  * ScoreLine constructor.
  * @param int $score
  * @param string $message
  */
  public function __construct(int $score = 1, string $message = "") {
    if ($score < 1 || $score > 15) {
      throw new InvalidArgumentException("Score must be between 1 and 15. Given: $score");
    }
    $this->score = $score;
    $this->scoreboardId = $score;
    $this->customName = $message;
    $this->type = self::TYPE_FAKE_PLAYER;
  }

  /**
  * Converts this ScoreLine into a ScorePacketEntry.
  * @return ScorePacketEntry
  */
  public function toEntry(): ScorePacketEntry {
    $entry = new ScorePacketEntry();
    $entry->type = $this->type;
    $entry->score = $this->score;
    $entry->scoreboardId = $this->scoreboardId;
    $entry->objectiveName = $this->objectiveName;
    $entry->customName = $this->customName;
    return $entry;
  }

  /**
  * Get the message text.
  * @return string
  */
  public function getMessage(): string {
    return $this->customName;
  }

  /**
  * Set the score.
  * @param int $score
  */
  public function setScore(int $score): void {
    if ($score < 1 || $score > 15) {
      throw new InvalidArgumentException("Score must be between 1 and 15. Given: $score");
    }
    $this->score = $score;
  }

  /**
  * Set the message.
  * @param string $message
  */
  public function setMessage(string $message): void {
    $this->customName = $message;
  }

  /**
  * Set the objective name.
  * @param string $objectiveName
  */
  public function setObjectiveName(string $objectiveName): void {
    $this->objectiveName = $objectiveName;
  }

  /**
  * Set the scoreboard ID.
  * @param int $scoreboardId
  */
  public function setScoreboardId(int $scoreboardId): void {
    $this->scoreboardId = $scoreboardId;
  }
}
