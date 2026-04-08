<?php

declare(strict_types = 1);

namespace imperazim\hud;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use imperazim\hud\tablist\TabList;
use imperazim\hud\scoreboard\ScoreBoardManager;

/**
* Main library for HUD components (BossBar & ScoreBoard).
* Handles BossBar packet validation and player cleanup.
*/
final class LibHud extends PluginBase implements Listener {

    protected function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
    * Handles data packet receive event.
    * @param DataPacketReceiveEvent $e
    */
    public function onDataPacketReceiveEvent(DataPacketReceiveEvent $e): void {
        $pk = $e->getPacket();
        if ($pk instanceof BossEventPacket) {
            switch ($pk->eventType) {
                case BossEventPacket::TYPE_REGISTER_PLAYER:
                case BossEventPacket::TYPE_UNREGISTER_PLAYER:
                    $this->getLogger()->debug("Got BossEventPacket " . ($pk->eventType === BossEventPacket::TYPE_REGISTER_PLAYER ? "" : "un") . "register by client for player id " . $pk->playerActorUniqueId);
                    break;
                default:
                    $e->cancel();
                    $player = $e->getOrigin()->getPlayer();
                    $playerName = $player !== null ? $player->getName() : 'unknown';
                    $this->getLogger()->warning("Cancelled unexpected BossEventPacket type {$pk->eventType} from {$playerName}");
            }
        }
    }

    /**
    * Cleans up HUD caches when a player disconnects.
    * @param PlayerQuitEvent $event
    */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        TabList::cleanup($player);
        ScoreBoardManager::cleanup($player);
    }
}
