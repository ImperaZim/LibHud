<?php

declare(strict_types = 1);

namespace imperazim\hud\composer;

use pocketmine\player\Player;

/**
* Composes multiple HUD elements per player in indexed layers.
*
* Each layer has a priority (lower = rendered first in the stack).
* Layers are identified by string keys.
*
* Usage:
*   $composer = new HudComposer();
*   $composer->setLayer($player, 'scoreboard', fn() => ScoreBoardManager::sendToPlayer($player, $sb));
*   $composer->setLayer($player, 'bossbar', fn() => $bossBar->addPlayer($player));
*   $composer->removeLayer($player, 'bossbar');
*   $composer->refresh($player); // Re-applies all active layers
*/
final class HudComposer {

    /** @var array<int, array<string, array{priority: int, apply: \Closure, remove: \Closure|null}>> */
    private array $layers = [];

    /**
    * Sets a HUD layer for a player.
    *
    * @param Player $player Target player
    * @param string $key Unique layer identifier
    * @param \Closure $apply Apply callback: fn(): void
    * @param \Closure|null $remove Remove callback: fn(): void
    * @param int $priority Layer priority (lower = first)
    */
    public function setLayer(
        Player $player,
        string $key,
        \Closure $apply,
        ?\Closure $remove = null,
        int $priority = 0
    ): void {
        $id = $player->getId();
        $this->layers[$id][$key] = [
            'priority' => $priority,
            'apply' => $apply,
            'remove' => $remove,
        ];
    }

    /**
    * Removes a HUD layer from a player.
    *
    * @param Player $player Target player
    * @param string $key Layer identifier
    */
    public function removeLayer(Player $player, string $key): void {
        $id = $player->getId();
        if (isset($this->layers[$id][$key])) {
            $remove = $this->layers[$id][$key]['remove'];
            if ($remove !== null) {
                $remove();
            }
            unset($this->layers[$id][$key]);
            if (empty($this->layers[$id])) {
                unset($this->layers[$id]);
            }
        }
    }

    /**
    * Checks if a layer exists for a player.
    *
    * @param Player $player Target player
    * @param string $key Layer identifier
    */
    public function hasLayer(Player $player, string $key): bool {
        return isset($this->layers[$player->getId()][$key]);
    }

    /**
    * Gets all layer keys for a player.
    *
    * @param Player $player Target player
    * @return string[] Layer keys sorted by priority
    */
    public function getLayers(Player $player): array {
        $id = $player->getId();
        if (!isset($this->layers[$id])) {
            return [];
        }

        $layers = $this->layers[$id];
        uasort($layers, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return array_keys($layers);
    }

    /**
    * Re-applies all active layers for a player (sorted by priority).
    *
    * @param Player $player Target player
    */
    public function refresh(Player $player): void {
        $id = $player->getId();
        if (!isset($this->layers[$id]) || !$player->isConnected()) {
            return;
        }

        $layers = $this->layers[$id];
        uasort($layers, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($layers as $layer) {
            ($layer['apply'])();
        }
    }

    /**
    * Removes all layers for a player.
    *
    * @param Player $player Target player
    */
    public function clearAll(Player $player): void {
        $id = $player->getId();
        if (isset($this->layers[$id])) {
            foreach ($this->layers[$id] as $layer) {
                if ($layer['remove'] !== null) {
                    ($layer['remove'])();
                }
            }
            unset($this->layers[$id]);
        }
    }

    /**
    * Removes all layers for all players.
    */
    public function clearEverything(): void {
        foreach ($this->layers as $playerId => $layers) {
            foreach ($layers as $layer) {
                if ($layer['remove'] !== null) {
                    ($layer['remove'])();
                }
            }
        }
        $this->layers = [];
    }
}
