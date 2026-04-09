<?php

declare(strict_types = 1);

namespace imperazim\hud\bossbar;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use Closure;

/**
* BossBar with automatic animated progress.
*
* Usage:
*   $bar = new AnimatedBossBar($plugin);
*   $bar->setTitle("§eLoading...");
*   $bar->addPlayer($player);
*   $bar->startAnimation(duration: 100, from: 0.0, to: 1.0); // 5 seconds (100 ticks)
*   // Later:
*   $bar->stopAnimation();
*/
final class AnimatedBossBar extends BossBar {

    private ?TaskHandler $taskHandler = null;
    private float $fromPercentage = 0.0;
    private float $toPercentage = 1.0;
    private int $elapsed = 0;
    private int $duration = 100;

    public function __construct(
        private Plugin $plugin
    ) {
        parent::__construct();
    }

    /**
    * Starts a progress animation.
    *
    * @param int $duration Duration in ticks (20 ticks = 1 second)
    * @param float $from Starting percentage (0.0 - 1.0)
    * @param float $to Ending percentage (0.0 - 1.0)
    * @param \Closure|null $onComplete Callback when animation finishes: fn(AnimatedBossBar): void
    */
    public function startAnimation(
        int $duration = 100,
        float $from = 0.0,
        float $to = 1.0,
        ?Closure $onComplete = null
    ): void {
        $this->stopAnimation();

        $this->fromPercentage = max(0.0, min(1.0, $from));
        $this->toPercentage = max(0.0, min(1.0, $to));
        $this->duration = max(1, $duration);
        $this->elapsed = 0;

        $this->setPercentage($this->fromPercentage);

        $this->taskHandler = $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function () use ($onComplete): void {
                $this->elapsed++;

                $progress = min(1.0, $this->elapsed / $this->duration);
                $current = $this->fromPercentage + ($this->toPercentage - $this->fromPercentage) * $progress;
                $this->setPercentage($current);

                if ($this->elapsed >= $this->duration) {
                    $this->stopAnimation();
                    if ($onComplete !== null) {
                        $onComplete($this);
                    }
                }
            }),
            1
        );
    }

    /**
    * Starts a looping animation (ping-pong between from and to).
    *
    * @param int $cycleDuration Duration per cycle in ticks
    * @param float $from Min percentage
    * @param float $to Max percentage
    */
    public function startLoop(
        int $cycleDuration = 60,
        float $from = 0.0,
        float $to = 1.0
    ): void {
        $this->stopAnimation();

        $this->fromPercentage = max(0.0, min(1.0, $from));
        $this->toPercentage = max(0.0, min(1.0, $to));
        $this->duration = max(1, $cycleDuration);
        $this->elapsed = 0;

        $forward = true;

        $this->taskHandler = $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function () use (&$forward): void {
                $this->elapsed++;

                if ($this->elapsed >= $this->duration) {
                    $this->elapsed = 0;
                    $forward = !$forward;
                }

                $progress = $this->elapsed / $this->duration;
                if (!$forward) {
                    $progress = 1.0 - $progress;
                }

                $current = $this->fromPercentage + ($this->toPercentage - $this->fromPercentage) * $progress;
                $this->setPercentage($current);
            }),
            1
        );
    }

    /**
    * Stops the current animation.
    */
    public function stopAnimation(): void {
        if ($this->taskHandler !== null) {
            $this->taskHandler->cancel();
            $this->taskHandler = null;
        }
    }

    /**
    * Checks if an animation is running.
    */
    public function isAnimating(): bool {
        return $this->taskHandler !== null;
    }

    /**
    * Gets the animation progress (0.0 - 1.0).
    */
    public function getAnimationProgress(): float {
        return min(1.0, $this->elapsed / $this->duration);
    }
}
