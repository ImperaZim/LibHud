<?php

declare(strict_types = 1);

namespace imperazim\hud;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\BossEventPacket;

/**
* Main library for HUD components (BossBar & ScoreBoard).
* Handles BossBar packet validation.
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
                    $e->getOrigin()->getPlayer()->kick("Invalid packet received", false);
            }
        }
    }
}
