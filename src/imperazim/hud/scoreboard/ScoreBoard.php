<?php

declare(strict_types = 1);

namespace imperazim\hud\scoreboard;

use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;

/**
* Class ScoreBoard
* @package imperazim\hud\scoreboard
*/
final class ScoreBoard {

  public const DISPLAY_SLOT_LIST = 'list';
  public const DISPLAY_SLOT_SIDEBAR = 'sidebar';
  public const DISPLAY_SLOT_BELOW_NAME = 'belowname';

  public string $displaySlot;
  public string $displayName;
  public string $objectiveName;
  public string $criteriaName;
  public int $sortOrder;
  public array $lines = [];

  /**
  * ScoreBoard constructor.
  * @param string $objectiveName
  */
  public function __construct(string $objectiveName) {
    $this->displaySlot = self::DISPLAY_SLOT_SIDEBAR;
    $this->displayName = $objectiveName;
    $this->objectiveName = $objectiveName;
    $this->criteriaName = "dummy";
    $this->sortOrder = 0;
  }

  /**
  * Get the display slot.
  * @return string
  */
  public function getDisplaySlot(): string {
    return $this->displaySlot;
  }

  /**
  * Set the display slot.
  * @param string $displaySlot
  */
  public function setDisplaySlot(string $displaySlot): self {
    $this->displaySlot = $displaySlot;
    return $this;
  }

  /**
  * Get the display name.
  * @return string
  */
  public function getDisplayName(): string {
    return $this->displayName;
  }

  /**
  * Set the display name.
  * @param string $displayName
  */
  public function setDisplayName(string $displayName): self {
    $this->displayName = $displayName;
    return $this;
  }

  /**
  * Get the objective name.
  * @return string
  */
  public function getObjectiveName(): string {
    return $this->objectiveName;
  }

  /**
  * Set the objective name.
  * @param string $objectiveName
  */
  public function setObjectiveName(string $objectiveName): self {
    $this->objectiveName = $objectiveName;
    return $this;
  }

  /**
  * Get the criteria name.
  * @return string
  */
  public function getCriteriaName(): string {
    return $this->criteriaName;
  }

  /**
  * Set the criteria name.
  * @param string $criteriaName
  */
  public function setCriteriaName(string $criteriaName): self {
    $this->criteriaName = $criteriaName;
    return $this;
  }

  /**
  * Get the sort order.
  * @return int
  */
  public function getSortOrder(): int {
    return $this->sortOrder;
  }

  /**
  * Set the sort order.
  * @param int $sortOrder
  */
  public function setSortOrder(int $sortOrder): self {
    $this->sortOrder = $sortOrder;
    return $this;
  }

  /**
  * Converts this ScoreBoard into a SetDisplayObjectivePacket.
  * @return SetDisplayObjectivePacket
  */
  public function toPacket(): SetDisplayObjectivePacket {
    $pk = new SetDisplayObjectivePacket();
    $pk->displaySlot = $this->displaySlot;
    $pk->objectiveName = $this->objectiveName;
    $pk->displayName = $this->displayName;
    $pk->criteriaName = $this->criteriaName;
    $pk->sortOrder = $this->sortOrder;
    return $pk;
  }

  /**
  * Get the lines as a SetScorePacket.
  * @return SetScorePacket
  */
  public function getLines(): SetScorePacket {
    $lines = new SetScorePacket();
    $lines->type = SetScorePacket::TYPE_CHANGE;
    $lines->entries = array_map(fn(ScoreLine $l) => $l->toEntry(), $this->lines);
    return $lines;
  }

  /**
  * Set a line in the scoreboard.
  * @param ScoreLine $line
  */
  public function setLine(ScoreLine $line): self {
    $line->setObjectiveName($this->objectiveName);
    $this->lines[] = $line;
    return $this;
  }

  /**
  * Set lines in the scoreboard.
  * @param ScoreLine[] $lines
  */
  public function setLines(array $lines): self {
    $this->lines = [];
    foreach ($lines as $line) {
      $line->setObjectiveName($this->objectiveName);
      $this->lines[] = $line;
    }
    return $this;
  }
}
