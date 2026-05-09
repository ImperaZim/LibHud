# LibHud

## Description
_A comprehensive HUD library for PocketMine-MP that provides boss bars, scoreboards, nametags, cooldown indicators, toast notifications, titles, action bars, tab list customization, notification queuing, and layer-based HUD composition -- all managed per-player with automatic cleanup on disconnect._

## Features
- **Boss Bars:** Fully customizable boss bars with title, subtitle, percentage, color, and entity binding.
- **Animated Boss Bars:** Automatic linear progress and ping-pong looping animations with completion callbacks.
- **Diverse Boss Bars:** Per-player differentiated titles, subtitles, percentages, and colors on a single bar instance.
- **Boss Bar Pool:** Named bar registry with Bedrock client limit enforcement (max 5 bars per player).
- **Scoreboards:** Up to 15 lines with fluent builder API, multiple display slots, and sort order control.
- **Scoreboard Templates:** Pre-built layouts (lobby, game stats, player info) with dynamic placeholder resolution.
- **Periodic Scoreboard Updates:** Scheduled automatic refresh at configurable intervals using factory callbacks.
- **Cooldown HUD:** Visual cooldown indicators using auto-decrementing boss bars that self-remove on expiry.
- **Toast Notifications:** Achievement-style popup messages sent via protocol packets.
- **Title Messages:** Full-screen title and subtitle messages with configurable fade in, stay, and fade out timing.
- **Action Bar Messages:** Text displayed above the hotbar for quick status updates.
- **Tab List Customization:** Player list header and footer modification with per-player caching.
- **Custom Nametags:** Global and per-observer nametag formats with placeholder resolution.
- **Notification Queue:** Priority-ordered sequential delivery of toasts, titles, and action bars without overlap.
- **HUD Composer:** Layer-based multi-element HUD management with priority ordering and apply/remove callbacks.
- **Automatic Cleanup:** All HUD components clean up player data on disconnect via LibHud's event listener.

## How to Use
Include or autoload LibHud in your PocketMine-MP plugin. No external dependencies are required. LibHud registers itself as a listener and handles BossBar packet validation and player cleanup automatically. For components that require scheduling, initialize them in your plugin's `onEnable`:

```php
use imperazim\hud\cooldown\CooldownHUD;
use imperazim\hud\notification\NotificationQueue;

public function onEnable(): void {
    CooldownHUD::init($this);
    NotificationQueue::init($this);
}
```

## Components

### `imperazim\hud`
#### Highlights
- Added `LibHud` class in `imperazim\hud` namespace.
#### Usage
LibHud is the main plugin class that handles BossBar packet validation and automatic player cleanup on disconnect. It registers itself as an event listener and cleans up TabList, ScoreBoardManager, NameTagManager, BossBarPool, CooldownHUD, and NotificationQueue caches when a player quits:

```php
use imperazim\hud\LibHud;

// LibHud is loaded as a plugin. No manual instantiation needed.
// It automatically:
// - Validates incoming BossEventPacket types
// - Cancels unexpected BossEventPacket types from clients
// - Cleans up all HUD caches on PlayerQuitEvent
```

### `imperazim\hud\bossbar`
#### Highlights
- Added `BossBar` class in `imperazim\hud\bossbar` namespace.
- Added `AnimatedBossBar` class in `imperazim\hud\bossbar` namespace.
- Added `DiverseBossBar` class in `imperazim\hud\bossbar` namespace.
- Added `BossBarPool` class in `imperazim\hud\bossbar` namespace.
#### Usage
**BossBar** -- Core boss bar with title, subtitle, percentage, color, and entity support:

```php
use imperazim\hud\bossbar\BossBar;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

$bar = new BossBar();
$bar->setTitle("World Boss")
    ->setSubTitle("Defeat the Ender Dragon")
    ->setColor(BossBarColor::RED)
    ->setPercentage(0.75)
    ->addPlayer($player);

// Update progress
$bar->setPercentage(0.5);

// Hide and show
$bar->hideFromAll();
$bar->showToAll();

// Bind to an entity
$bar->setEntity($entity);
$bar->resetEntity(removeEntity: true);

// Remove players
$bar->removePlayer($player);
$bar->removeAllPlayers();
```

**AnimatedBossBar** -- Boss bar with automatic animated progress:

```php
use imperazim\hud\bossbar\AnimatedBossBar;

$bar = new AnimatedBossBar($plugin);
$bar->setTitle("Loading Map...")->addPlayer($player);

// Animate from 0% to 100% over 5 seconds (100 ticks)
$bar->startAnimation(
    duration: 100,
    from: 0.0,
    to: 1.0,
    onComplete: function(AnimatedBossBar $bar): void {
        $bar->setTitle("Map Ready!");
        $bar->stopAnimation();
    }
);

// Ping-pong loop between min and max
$bar->startLoop(cycleDuration: 60, from: 0.2, to: 0.8);

// Query state
$bar->isAnimating();          // bool
$bar->getAnimationProgress(); // 0.0 - 1.0

// Stop animation
$bar->stopAnimation();
```

**DiverseBossBar** -- Per-player differentiated boss bar content:

```php
use imperazim\hud\bossbar\DiverseBossBar;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

$bar = new DiverseBossBar();
$bar->setTitle("Default Title")
    ->addPlayer($playerA)
    ->addPlayer($playerB);

// Per-player customization
$bar->setTitleFor([$playerA], "Team Red Progress");
$bar->setTitleFor([$playerB], "Team Blue Progress");
$bar->setSubTitleFor([$playerA], "Keep fighting!");

$bar->setPercentageFor([$playerA], 0.8);
$bar->setPercentageFor([$playerB], 0.3);
$bar->setColorFor([$playerA], BossBarColor::RED);
$bar->setColorFor([$playerB], BossBarColor::BLUE);

// Query per-player values
$title = $bar->getTitleFor($playerA);
$percentage = $bar->getPercentageFor($playerA);
$color = $bar->getColorFor($playerA);

// Reset a player back to defaults
$bar->resetFor($playerA);
$bar->resetForAll();
```

**BossBarPool** -- Named bar management with Bedrock limit enforcement:

```php
use imperazim\hud\bossbar\BossBarPool;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

// Create and configure named bars
$bar = BossBarPool::create("quest_progress");
$bar->setTitle("Quest: Defeat 10 Mobs")
    ->setPercentage(0.5)
    ->setColor(BossBarColor::GREEN);

// Show/hide per player (returns false if at 5-bar limit)
BossBarPool::show($player, "quest_progress");
BossBarPool::hide($player, "quest_progress");

// Query state
BossBarPool::exists("quest_progress");    // bool
BossBarPool::get("quest_progress");       // BossBar|null
BossBarPool::getActive($player);          // ["quest_progress"]
BossBarPool::getActiveCount($player);     // int

// Remove bar entirely (despawns from all players)
BossBarPool::remove("quest_progress");
```

### `imperazim\hud\scoreboard`
#### Highlights
- Added `ScoreBoard` class in `imperazim\hud\scoreboard` namespace.
- Added `ScoreLine` class in `imperazim\hud\scoreboard` namespace.
- Added `ScoreBoardManager` class in `imperazim\hud\scoreboard` namespace.
- Added `ScoreBoardTemplates` class in `imperazim\hud\scoreboard` namespace.
- Added `PeriodicUpdate` class in `imperazim\hud\scoreboard` namespace.
#### Usage
**ScoreBoard and ScoreLine** -- Build scoreboards with up to 15 lines:

```php
use imperazim\hud\scoreboard\ScoreBoard;
use imperazim\hud\scoreboard\ScoreLine;
use imperazim\hud\scoreboard\ScoreBoardManager;

$board = new ScoreBoard("My Server");
$board->setLine(new ScoreLine(1, ""))
      ->setLine(new ScoreLine(2, "Player: " . $player->getName()))
      ->setLine(new ScoreLine(3, "Kills: 15"))
      ->setLine(new ScoreLine(4, "Deaths: 3"))
      ->setLine(new ScoreLine(5, "K/D: 5.00"))
      ->setLine(new ScoreLine(6, " "));

// Display slots: DISPLAY_SLOT_SIDEBAR (default), DISPLAY_SLOT_LIST, DISPLAY_SLOT_BELOW_NAME
$board->setDisplaySlot(ScoreBoard::DISPLAY_SLOT_SIDEBAR);
$board->setSortOrder(0);

// Send to player
ScoreBoardManager::sendToPlayer($player, $board);

// Update, clear lines, or remove
ScoreBoardManager::clearLine($player, 3);
ScoreBoardManager::clearAllLines($player);
ScoreBoardManager::updateToPlayer($player);
ScoreBoardManager::removeFromPlayer($player);

// Get current scoreboard
$current = ScoreBoardManager::getScoreBoardFromPlayer($player);
```

**ScoreBoardTemplates** -- Pre-built layouts with dynamic placeholders:

```php
use imperazim\hud\scoreboard\ScoreBoardTemplates;

// Lobby template (player name, online count, TPS)
ScoreBoardTemplates::lobby($player, "My Server");

// Game stats template
ScoreBoardTemplates::gameStats($player, "BedWars", [
    "Kills"  => "5",
    "Deaths" => "2",
    "Beds"   => "1",
    "Coins"  => "350",
]);

// Player info template (name, health, level, ping, world)
ScoreBoardTemplates::playerInfo($player, "Player Info");

// Custom template with placeholders:
// {player}, {online}, {max}, {tps}, {health}, {max_health},
// {level}, {ping}, {world}, {x}, {y}, {z}
ScoreBoardTemplates::fromTemplate($player, "{player}'s Stats", [
    "",
    "Health: {health}/{max_health}",
    "Level: {level}",
    "Position: {x}, {y}, {z}",
    "World: {world}",
    "Ping: {ping}ms",
    "Online: {online}/{max}",
    " ",
]);
```

**PeriodicUpdate** -- Auto-refreshing scoreboards on a timer:

```php
use imperazim\hud\scoreboard\PeriodicUpdate;
use imperazim\hud\scoreboard\ScoreBoard;
use imperazim\hud\scoreboard\ScoreLine;
use pocketmine\Server;

$updater = new PeriodicUpdate($plugin, intervalTicks: 20);

$updater->register($player, "live_stats", function(Player $p): ScoreBoard {
    $sb = new ScoreBoard("Live Stats");
    $sb->setLine(new ScoreLine(1, "Online: " . count(Server::getInstance()->getOnlinePlayers())));
    $sb->setLine(new ScoreLine(2, "TPS: " . Server::getInstance()->getTicksPerSecond()));
    $sb->setLine(new ScoreLine(3, "Ping: " . $p->getNetworkSession()->getPing() . "ms"));
    return $sb;
});

// Check, change interval, unregister, or stop
$updater->has($player, "live_stats");
$updater->setInterval(40);
$updater->unregister($player, "live_stats");
$updater->unregisterAll($player);
$updater->stop();
```

### `imperazim\hud\cooldown`
#### Highlights
- Added `CooldownHUD` class in `imperazim\hud\cooldown` namespace.
#### Usage
Visual cooldown indicators using auto-decrementing boss bars:

```php
use imperazim\hud\cooldown\CooldownHUD;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

// Initialize once in onEnable()
CooldownHUD::init($plugin);

// Show a 5-second cooldown bar
CooldownHUD::show($player, "ability.dash", "Dash Cooldown", 5.0);

// Show with custom color and update interval
CooldownHUD::show(
    $player,
    "item.potion",
    "Potion Cooldown",
    seconds: 10.0,
    color: BossBarColor::GREEN,
    updateIntervalTicks: 2
);

// Check if active
if (CooldownHUD::isActive($player, "ability.dash")) {
    $player->sendMessage("Dash is still on cooldown!");
}

// Manually hide
CooldownHUD::hide($player, "ability.dash");
```

### `imperazim\hud\message`
#### Highlights
- Added `ToastManager` class in `imperazim\hud\message` namespace.
- Added `TitleManager` class in `imperazim\hud\message` namespace.
- Added `ActionBarManager` class in `imperazim\hud\message` namespace.
#### Usage
**ToastManager** -- Achievement-style popup notifications:

```php
use imperazim\hud\message\ToastManager;

// Send to one player
ToastManager::send($player, "Quest Complete!", "You earned 50 coins");

// Send to all online players
ToastManager::sendToAll($server->getOnlinePlayers(), "Server Announcement", "Double XP event starts now!");
```

**TitleManager** -- Screen title and subtitle messages with fade control:

```php
use imperazim\hud\message\TitleManager;

// Full title with subtitle and timing (in ticks)
TitleManager::send($player, "Welcome!", "Enjoy your stay", fadeIn: 10, stay: 40, fadeOut: 10);

// Subtitle only
TitleManager::sendSubtitle($player, "Watch out!");

// Broadcast to all
TitleManager::sendToAll($server->getOnlinePlayers(), "Round Start!", "Get ready...");

// Clear and reset
TitleManager::clear($player);
TitleManager::reset($player);
```

**ActionBarManager** -- Text above the hotbar:

```php
use imperazim\hud\message\ActionBarManager;

// Send action bar message
ActionBarManager::send($player, "Health: 20/20 | Mana: 50/50");

// Broadcast to all
ActionBarManager::sendToAll($server->getOnlinePlayers(), "Server restarting in 5 minutes");

// Clear
ActionBarManager::clear($player);
```

### `imperazim\hud\tablist`
#### Highlights
- Added `TabList` class in `imperazim\hud\tablist` namespace.
#### Usage
Customize the TAB player list header and footer:

```php
use imperazim\hud\tablist\TabList;

// Set header and footer
TabList::send($player, "My Server\nWelcome!", "play.myserver.com\nOnline: 42");

// Broadcast to all
TabList::sendToAll($server->getOnlinePlayers(), "My Server", "play.myserver.com");

// Read cached values
$header = TabList::getHeader($player); // string|null
$footer = TabList::getFooter($player); // string|null

// Clear
TabList::clear($player);
```

### `imperazim\hud\nametag`
#### Highlights
- Added `NameTagManager` class in `imperazim\hud\nametag` namespace.
#### Usage
Custom nametags with global and per-observer visibility:

```php
use imperazim\hud\nametag\NameTagManager;

// Set a global nametag visible to all players
// Placeholders: {name}, {health}, {max_health}, {level}
NameTagManager::set($player, "[VIP] {name}\n{health}/{max_health} HP");

// Set a per-observer nametag (only $observer sees this on $target)
NameTagManager::setFor($observer, $target, "[Enemy] {name}");

// Refresh for all observers
NameTagManager::refresh($player);

// Reset to default
NameTagManager::reset($player);
```

### `imperazim\hud\notification`
#### Highlights
- Added `NotificationQueue` class in `imperazim\hud\notification` namespace.
#### Usage
Priority-ordered sequential notification delivery without overlap:

```php
use imperazim\hud\notification\NotificationQueue;

// Initialize once in onEnable()
NotificationQueue::init($plugin);

// Queue toast (higher priority = shown first)
NotificationQueue::toast($player, "Level Up!", "You reached level 10", priority: 10);

// Queue title
NotificationQueue::title($player, "Wave 3", "Prepare yourself!", priority: 5, durationTicks: 60);

// Queue action bar
NotificationQueue::actionBar($player, "Health restored!", priority: 0, durationTicks: 40);

// Check pending count
$count = NotificationQueue::pending($player);

// Clear all queued notifications
NotificationQueue::clear($player);
```

### `imperazim\hud\composer`
#### Highlights
- Added `HudComposer` class in `imperazim\hud\composer` namespace.
#### Usage
Layer-based multi-element HUD management with priority ordering:

```php
use imperazim\hud\composer\HudComposer;
use imperazim\hud\scoreboard\ScoreBoardManager;

$composer = new HudComposer();

// Add a scoreboard layer (lower priority = applied first)
$composer->setLayer(
    $player,
    'scoreboard',
    apply: fn() => ScoreBoardManager::sendToPlayer($player, $scoreboard),
    remove: fn() => ScoreBoardManager::removeFromPlayer($player),
    priority: 0
);

// Add a boss bar layer
$composer->setLayer(
    $player,
    'bossbar',
    apply: fn() => $bossBar->addPlayer($player),
    remove: fn() => $bossBar->removePlayer($player),
    priority: 1
);

// Query layers
$composer->hasLayer($player, 'scoreboard'); // true
$composer->getLayers($player);              // ['scoreboard', 'bossbar']

// Re-apply all layers in priority order
$composer->refresh($player);

// Remove a specific layer (calls its remove callback)
$composer->removeLayer($player, 'bossbar');

// Clear all layers for a player or for everyone
$composer->clearAll($player);
$composer->clearEverything();
```

### `imperazim\hud\exception`
#### Highlights
- Added `HudException` class in `imperazim\hud\exception` namespace.
#### Usage
Runtime exception for HUD-related errors, extending `RuntimeException`:

```php
use imperazim\hud\exception\HudException;

try {
    $bar->setEntity($closedEntity);
} catch (HudException $e) {
    $logger->warning("HUD error: " . $e->getMessage());
}
```

## Licensing information
This project is licensed under MIT. Please see the [LICENSE](/LICENSE) file for details.
