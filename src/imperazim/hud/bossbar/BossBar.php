<?php

declare(strict_types = 1);

namespace imperazim\hud\bossbar;

use GlobalLogger;
use imperazim\hud\exception\HudException;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\AttributeFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use Throwable;

/**
* Class BossBar
* @package imperazim\hud\bossbar
*/
class BossBar {

  /** @var Player[] */
  private array $players = [];

  /** @var string */
  private string $title = "";

  /** @var string */
  private string $subTitle = "";

  /** @var int */
  private int $color = BossBarColor::PURPLE;

  /** @var int|null */
  protected ?int $actorId = null;

  /** @var AttributeMap */
  private AttributeMap $attributeMap;

  /** @var EntityMetadataCollection */
  protected EntityMetadataCollection $propertyManager;

  /**
  * BossBar constructor.
  * Initializes a new boss bar instance.
  */
  public function __construct() {
    $this->actorId = Entity::nextRuntimeId();
    $this->attributeMap = new AttributeMap();
    $this->getAttributeMap()->add(AttributeFactory::getInstance()->mustGet(Attribute::HEALTH)
      ->setMaxValue(100.0)
      ->setMinValue(0.0)
      ->setDefaultValue(100.0)
    );
    $this->propertyManager = new EntityMetadataCollection();
    $this->propertyManager->setLong(EntityMetadataProperties::FLAGS, 0
      ^ 1 << EntityMetadataFlags::SILENT
      ^ 1 << EntityMetadataFlags::INVISIBLE
      ^ 1 << EntityMetadataFlags::NO_AI
      ^ 1 << EntityMetadataFlags::FIRE_IMMUNE
    );
    $this->propertyManager->setShort(EntityMetadataProperties::MAX_AIR, 400);
    $this->propertyManager->setString(EntityMetadataProperties::NAMETAG, $this->getFullTitle());
    $this->propertyManager->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
    $this->propertyManager->setFloat(EntityMetadataProperties::SCALE, 0);
    $this->propertyManager->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
    $this->propertyManager->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
  }

  /**
  * @return Player[]
  */
  public function getPlayers(): array {
    return $this->players;
  }

  /**
  * Add a single player to this bar.
  * @param Player $player
  * @return static
  */
  public function addPlayer(Player $player): static {
    try {
      if (!isset($this->players[$player->getId()])) {
        $this->spawnBossEntity($player);
        $this->sendBossPacket([$player]);
        $this->players[$player->getId()] = $player;
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Add multiple players to this bar.
  * @param Player[] $players
  * @return static
  */
  public function addPlayers(array $players): static {
    try {
      foreach ($players as $player) {
        $this->addPlayer($player);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Remove a single player from this bar.
  * @param Player $player
  * @return static
  */
  public function removePlayer(Player $player): static {
    try {
      if (isset($this->players[$player->getId()])) {
        $this->sendRemoveBossPacket([$player]);
        $this->despawnBossEntity($player);
        unset($this->players[$player->getId()]);
      } else {
        GlobalLogger::get()->debug("Removed player that was not added to the boss bar (" . $this . ")");
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Remove multiple players from this bar.
  * @param Player[] $players
  * @return static
  */
  public function removePlayers(array $players): static {
    try {
      foreach ($players as $player) {
        $this->removePlayer($player);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Remove all players from this bar.
  * @return static
  */
  public function removeAllPlayers(): static {
    try {
      foreach ($this->getPlayers() as $player) {
        $this->removePlayer($player);
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Get the title of the boss bar.
  * @return string
  */
  public function getTitle(): string {
    return $this->title;
  }

  /**
  * Set the title of the boss bar.
  * @param string $title
  * @return static
  */
  public function setTitle(string $title = ""): static {
    try {
      $this->title = $title;
      $this->sendBossTextPacket($this->getPlayers());
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Get the subtitle of the boss bar.
  * @return string
  */
  public function getSubTitle(): string {
    return $this->subTitle;
  }

  /**
  * Set the subtitle of the boss bar.
  * @param string $subTitle
  * @return static
  */
  public function setSubTitle(string $subTitle = ""): static {
    try {
      $this->subTitle = $subTitle;
      $this->sendBossTextPacket($this->getPlayers());
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Get the full title, which includes the title and subtitle.
  * @return string
  */
  public function getFullTitle(): string {
    $text = $this->title;
    if (!empty($this->subTitle)) {
      $text .= "\n\n" . $this->subTitle;
    }
    return mb_convert_encoding($text, 'UTF-8');
  }

  /**
  * Set the percentage of the boss bar.
  * @param float $percentage 0-1
  * @return static
  */
  public function setPercentage(float $percentage): static {
    try {
      $percentage = min(1.0, max(0.0, $percentage));
      $this->getAttributeMap()->get(Attribute::HEALTH)->setValue($percentage * $this->getAttributeMap()->get(Attribute::HEALTH)->getMaxValue(), true, true);
      $this->sendBossHealthPacket($this->getPlayers());
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Get the current percentage of the boss bar.
  * @return float
  */
  public function getPercentage(): float {
    return $this->getAttributeMap()->get(Attribute::HEALTH)->getValue() / 100;
  }

  /**
  * Get the color of the boss bar.
  * @return int
  */
  public function getColor(): int {
    return $this->color;
  }

  /**
  * Set the color of the boss bar.
  * @param int $color
  * @return static
  */
  public function setColor(int $color): static {
    try {
      $this->color = $color;
      $this->sendBossPacket($this->getPlayers());
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Hide the boss bar from specific players.
  * @param Player[] $players
  */
  public function hideFrom(array $players): void {
    try {
      foreach ($players as $player) {
        if ($player->isConnected()) {
          $player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->actorId ?? $player->getId()));
        }
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Hide the boss bar from all registered players.
  */
  public function hideFromAll(): void {
    $this->hideFrom($this->getPlayers());
  }

  /**
  * Show the boss bar to specific players.
  * @param Player[] $players
  */
  public function showTo(array $players): void {
    $this->sendBossPacket($players);
  }

  /**
  * Show the boss bar to all registered players.
  */
  public function showToAll(): void {
    $this->showTo($this->getPlayers());
  }

  /**
  * Get the entity associated with the boss bar.
  * @return Entity|null
  */
  public function getEntity(): ?Entity {
    if ($this->actorId === null) return null;
    return Server::getInstance()->getWorldManager()->findEntity($this->actorId);
  }

  /**
  * Set the entity associated with the boss bar.
  * @param Entity|null $entity
  * @return static
  * @throws HudException
  */
  public function setEntity(?Entity $entity = null): static {
    try {
      if ($entity instanceof Entity && ($entity->isClosed() || $entity->isFlaggedForDespawn())) {
        throw new HudException("Entity #{$entity->getId()} cannot be used since it is not valid anymore (closed or flagged for despawn)");
      }

      if ($this->getEntity() instanceof Entity && !($entity instanceof Player)) {
        $this->getEntity()->flagForDespawn();
      } else {
        $pk = new RemoveActorPacket();
        $pk->actorUniqueId = $this->actorId;
        Server::getInstance()->broadcastPackets($this->getPlayers(), [$pk]);
      }

      if ($entity instanceof Entity) {
        $this->actorId = $entity->getId();
        $this->attributeMap = $entity->getAttributeMap();
        $this->getAttributeMap()->add($entity->getAttributeMap()->get(Attribute::HEALTH));
        $this->propertyManager = $entity->getNetworkProperties();
        if (!($entity instanceof Player)) $entity->despawnFromAll();
      } else {
        $this->actorId = Entity::nextRuntimeId();
      }

      $this->sendBossPacket($this->getPlayers());
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Reset the entity associated with the boss bar.
  * @param bool $removeEntity
  * @return static
  */
  public function resetEntity(bool $removeEntity = false): static {
    try {
      if ($removeEntity && $this->getEntity() instanceof Entity && !($this->getEntity() instanceof Player)) {
        $this->getEntity()->close();
      }
      $this->setEntity();
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
    return $this;
  }

  /**
  * Spawn the fake invisible entity on the client for this boss bar.
  * @param Player $player
  */
  protected function spawnBossEntity(Player $player): void {
    if (!$player->isConnected()) return;
    $pk = AddActorPacket::create(
      $this->actorId,
      $this->actorId,
      "minecraft:slime",
      $player->getPosition()->asVector3(),
      null,
      0, 0, 0, 0,
      [],
      $this->propertyManager->getAll(),
      new PropertySyncData([], []),
      []
    );
    $player->getNetworkSession()->sendDataPacket($pk);
  }

  /**
  * Despawn the fake entity from the client.
  * @param Player $player
  */
  protected function despawnBossEntity(Player $player): void {
    if (!$player->isConnected()) return;
    $pk = new RemoveActorPacket();
    $pk->actorUniqueId = $this->actorId;
    $player->getNetworkSession()->sendDataPacket($pk);
  }

  /**
  * Send the boss bar packet to players.
  * @param Player[] $players
  */
  protected function sendBossPacket(array $players): void {
    try {
      foreach ($players as $player) {
        if ($player->isConnected()) {
          $player->getNetworkSession()->sendDataPacket(BossEventPacket::show(
            $this->actorId ?? $player->getId(),
            $this->getFullTitle(),
            $this->getPercentage(),
            false,
            $this->getColor(),
            1
          ));
        }
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Send the remove boss bar packet to players.
  * @param Player[] $players
  */
  protected function sendRemoveBossPacket(array $players): void {
    try {
      foreach ($players as $player) {
        if ($player->isConnected()) {
          $player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->actorId ?? $player->getId()));
        }
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Send the boss bar text packet to players.
  * @param Player[] $players
  */
  protected function sendBossTextPacket(array $players): void {
    try {
      foreach ($players as $player) {
        if ($player->isConnected()) {
          $player->getNetworkSession()->sendDataPacket(BossEventPacket::title(
            $this->actorId ?? $player->getId(),
            $this->getFullTitle()
          ));
        }
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Send the boss bar health packet to players.
  * @param Player[] $players
  */
  protected function sendBossHealthPacket(array $players): void {
    try {
      foreach ($players as $player) {
        if ($player->isConnected()) {
          $player->getNetworkSession()->sendDataPacket(BossEventPacket::healthPercent(
            $this->actorId ?? $player->getId(),
            $this->getPercentage()
          ));
        }
      }
    } catch (Throwable $e) {
      GlobalLogger::get()->logException($e);
    }
  }

  /**
  * Convert the BossBar object to a string representation.
  * @return string
  */
  public function __toString(): string {
    return __CLASS__ . " ID: $this->actorId, Players: " . count($this->players) . ", Title: \"$this->title\", Subtitle: \"$this->subTitle\", Percentage: \"" . $this->getPercentage() . "\", Color: \"" . $this->color . "\"";
  }

  /**
  * Get the attribute map associated with the boss bar.
  * @return AttributeMap
  */
  public function getAttributeMap(): AttributeMap {
    return $this->attributeMap;
  }

  /**
  * Get the property manager associated with the boss bar.
  * @return EntityMetadataCollection
  */
  protected function getPropertyManager(): EntityMetadataCollection {
    return $this->propertyManager;
  }
}
