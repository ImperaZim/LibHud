# LibHud

<p align="center">
  <img src="https://img.shields.io/badge/PocketMine--MP-5.0.0+-blue?style=flat-square" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777bb4?style=flat-square" />
  <img src="https://img.shields.io/github/license/ImperaZim/LibHud?style=flat-square" />
  <img src="https://img.shields.io/github/issues/ImperaZim/LibHud?style=flat-square" />
  <img src="https://img.shields.io/github/stars/ImperaZim/LibHud?style=flat-square" />
</p>

---

> **LibHud** is a comprehensive HUD library for PocketMine-MP plugins, providing boss bars, scoreboards, nametags, cooldown indicators, toast notifications, titles, action bars, tab list customization, and a notification queue system. All HUD elements are managed per-player with automatic cleanup on disconnect.

---

## Technical Features

- Boss bars with title, subtitle, percentage, and color customization
- Animated boss bars with linear progress and ping-pong looping
- Per-player diverse boss bars showing different content to each player
- Boss bar pool for managing multiple named bars without plugin conflicts (respects Bedrock 5-bar limit)
- Scoreboards with up to 15 lines, multiple display slots, and fluent builder API
- Pre-built scoreboard templates with dynamic placeholder resolution
- Periodic scoreboard updates via scheduled tasks with configurable intervals
- Custom nametags with global and per-observer visibility
- Visual cooldown HUD using auto-decrementing boss bars
- Toast notifications (achievement-style popups)
- Title and subtitle screen messages with fade in/stay/fade out control
- Action bar messages (text above hotbar)
- Notification queue that serializes toasts, titles, and action bars with priority ordering
- Tab list (TAB) header and footer customization
- HUD layer composer for managing multiple HUD elements with priority-based ordering
- Automatic player cleanup on disconnect for all HUD components

---

## Installation & Requirements

- **PocketMine-MP** API 5.0.0+
- **PHP** 8.2+
- No external dependencies

**Installation:**
- As a library: place `imperazim/hud` in your `src/` and register the autoload.
- As a PHAR plugin: download the `.phar` and place it in `plugins/`.

---

## Basic Integration

LibHud registers itself as a listener and handles BossBar packet validation and player cleanup automatically:

```php
// In your plugin, initialize optional static managers:
public function onEnable(): void {
    CooldownHUD::init($this);
    NotificationQueue::init($this);
}
```

---

## Technical Examples

### 1. BossBar -- Basic Usage

Create a boss bar with title, subtitle, color, and percentage:

```php
use imperazim\hud\bossbar\BossBar;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

$bar = new BossBar();
$bar->setTitle("World Boss")
    ->setSubTitle("Defeat the Ender Dragon")
    ->setColor(BossBarColor::RED)
    ->setPercentage(0.75)
    ->addPlayer($player);

// Update progress as the boss takes damage
$bar->setPercentage(0.5);

// Temporarily hide/show
$bar->hideFromAll();
$bar->showToAll();

// Remove a player
$bar->removePlayer($player);

// Remove all players
$bar->removeAllPlayers();
```

---

### 2. AnimatedBossBar -- Progress Animations

Animate the bar percentage over time with an optional completion callback:

```php
use imperazim\hud\bossbar\AnimatedBossBar;

$bar = new AnimatedBossBar($plugin);
$bar->setTitle("Loading Map...")
    ->addPlayer($player);

// Animate from 0% to 100% over 5 seconds (100 ticks)
$bar->startAnimation(
    duration: 100,
    from: 0.0,
    to: 1.0,
    onComplete: function (AnimatedBossBar $bar): void {
        $bar->setTitle("Map Ready!");
        $bar->stopAnimation();
    }
);

// Ping-pong loop (bounces between min and max)
$bar->startLoop(cycleDuration: 60, from: 0.2, to: 0.8);

// Check state
$bar->isAnimating();       // bool
$bar->getAnimationProgress(); // 0.0 - 1.0

// Stop manually
$bar->stopAnimation();
```

---

### 3. DiverseBossBar -- Per-Player Content

Show different titles, colors, and percentages to each player on the same bar:

```php
use imperazim\hud\bossbar\DiverseBossBar;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

$bar = new DiverseBossBar();
$bar->setTitle("Default Title")
    ->addPlayer($playerA)
    ->addPlayer($playerB);

// Per-player title and subtitle
$bar->setTitleFor([$playerA], "Team Red Progress");
$bar->setTitleFor([$playerB], "Team Blue Progress");
$bar->setSubTitleFor([$playerA], "Keep fighting!");

// Per-player percentage and color
$bar->setPercentageFor([$playerA], 0.8);
$bar->setPercentageFor([$playerB], 0.3);
$bar->setColorFor([$playerA], BossBarColor::RED);
$bar->setColorFor([$playerB], BossBarColor::BLUE);

// Reset a specific player back to defaults
$bar->resetFor($playerA);
```

---

### 4. BossBarPool -- Named Bar Management

Manage multiple boss bars by string ID with Bedrock client limit enforcement (max 5 per player):

```php
use imperazim\hud\bossbar\BossBarPool;
use pocketmine\network\mcpe\protocol\types\BossBarColor;

// Create and configure named bars
$bar = BossBarPool::create("quest_progress");
$bar->setTitle("Quest: Defeat 10 Mobs")
    ->setPercentage(0.5)
    ->setColor(BossBarColor::GREEN);

BossBarPool::create("server_event")
    ->setTitle("Double XP Active!")
    ->setPercentage(1.0)
    ->setColor(BossBarColor::YELLOW);

// Show/hide bars per player
BossBarPool::show($player, "quest_progress");  // returns false if at 5-bar limit
BossBarPool::show($player, "server_event");
BossBarPool::hide($player, "quest_progress");

// Query state
BossBarPool::exists("quest_progress");         // bool
BossBarPool::getActive($player);               // ["server_event"]
BossBarPool::getActiveCount($player);          // 1

// Remove bar entirely (despawns from all players)
BossBarPool::remove("quest_progress");
```

---

### 5. ScoreBoard -- Sidebar Display

Build scoreboards with up to 15 lines using the fluent API:

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

// Send to player
ScoreBoardManager::sendToPlayer($player, $board);

// Update specific lines
ScoreBoardManager::clearLine($player, 3);

// Clear all lines
ScoreBoardManager::clearAllLines($player);

// Refresh entire scoreboard
ScoreBoardManager::updateToPlayer($player);

// Remove scoreboard
ScoreBoardManager::removeFromPlayer($player);

// Get current scoreboard
$current = ScoreBoardManager::getScoreBoardFromPlayer($player); // ScoreBoard|null
```

---

### 6. ScoreBoardTemplates -- Pre-Built Layouts

Ready-to-use scoreboard templates with automatic placeholder resolution:

```php
use imperazim\hud\scoreboard\ScoreBoardTemplates;

// Lobby template (player name, online count, TPS)
ScoreBoardTemplates::lobby($player, "My Server");

// Game stats template with custom key-value pairs
ScoreBoardTemplates::gameStats($player, "BedWars", [
    "Kills"    => "5",
    "Deaths"   => "2",
    "Beds"     => "1",
    "Coins"    => "350",
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

---

### 7. PeriodicUpdate -- Auto-Refreshing Scoreboards

Schedule automatic scoreboard updates at a configurable interval:

```php
use imperazim\hud\scoreboard\PeriodicUpdate;
use imperazim\hud\scoreboard\ScoreBoard;
use imperazim\hud\scoreboard\ScoreLine;
use pocketmine\Server;

// Update every second (20 ticks)
$updater = new PeriodicUpdate($plugin, intervalTicks: 20);

// Register a factory that rebuilds the scoreboard each tick
$updater->register($player, "live_stats", function (Player $p): ScoreBoard {
    $sb = new ScoreBoard("Live Stats");
    $sb->setLine(new ScoreLine(1, "Online: " . count(Server::getInstance()->getOnlinePlayers())));
    $sb->setLine(new ScoreLine(2, "TPS: " . Server::getInstance()->getTicksPerSecond()));
    $sb->setLine(new ScoreLine(3, "Ping: " . $p->getNetworkSession()->getPing() . "ms"));
    $sb->setLine(new ScoreLine(4, "Health: " . (int) $p->getHealth()));
    return $sb;
});

// Check registration
$updater->has($player, "live_stats"); // true

// Change interval to every 2 seconds
$updater->setInterval(40);

// Unregister specific updater
$updater->unregister($player, "live_stats");

// Unregister all for a player
$updater->unregisterAll($player);

// Stop and start the task
$updater->stop();
$updater->start();
```

---

### 8. CooldownHUD -- Visual Cooldown Indicators

Display a temporary boss bar that automatically decreases and removes itself:

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

// Check if a cooldown is active
if (CooldownHUD::isActive($player, "ability.dash")) {
    $player->sendMessage("Dash is still on cooldown!");
}

// Manually cancel a cooldown bar
CooldownHUD::hide($player, "ability.dash");
```

---

### 9. ToastManager -- Achievement-Style Popups

Send toast notifications (small popup in the top-right corner):

```php
use imperazim\hud\message\ToastManager;

// Send to one player
ToastManager::send($player, "Quest Complete!", "You earned 50 coins");

// Send to all online players
ToastManager::sendToAll(
    $server->getOnlinePlayers(),
    "Server Announcement",
    "Double XP event starts now!"
);
```

---

### 10. TitleManager -- Screen Titles

Send title and subtitle messages with customizable fade timing:

```php
use imperazim\hud\message\TitleManager;

// Full title with subtitle and timing (in ticks)
TitleManager::send(
    $player,
    "Welcome!",
    "Enjoy your stay",
    fadeIn: 10,  // 0.5s
    stay: 40,    // 2s
    fadeOut: 10   // 0.5s
);

// Subtitle only
TitleManager::sendSubtitle($player, "Watch out!");

// Broadcast to multiple players
TitleManager::sendToAll($server->getOnlinePlayers(), "Round Start!", "Get ready...");

// Clear current title
TitleManager::clear($player);

// Reset title settings (timing and text)
TitleManager::reset($player);
```

---

### 11. ActionBarManager -- Hotbar Text

Display text above the hotbar:

```php
use imperazim\hud\message\ActionBarManager;

// Send action bar message
ActionBarManager::send($player, "Health: 20/20 | Mana: 50/50");

// Broadcast to all players
ActionBarManager::sendToAll($server->getOnlinePlayers(), "Server restarting in 5 minutes");

// Clear action bar
ActionBarManager::clear($player);
```

---

### 12. TabList -- Player List Header and Footer

Customize the TAB player list header and footer:

```php
use imperazim\hud\tablist\TabList;

// Set header and footer for a player
TabList::send($player, "My Server\nWelcome!", "play.myserver.com\nOnline: 42");

// Broadcast to all
TabList::sendToAll($server->getOnlinePlayers(), "My Server", "play.myserver.com");

// Read cached values
$header = TabList::getHeader($player); // string|null
$footer = TabList::getFooter($player); // string|null

// Clear header and footer
TabList::clear($player);
```

---

### 13. NameTagManager -- Custom Nametags

Set custom nametags with placeholders, globally or per-observer:

```php
use imperazim\hud\nametag\NameTagManager;

// Set a global nametag visible to all players
// Placeholders: {name}, {health}, {max_health}, {level}
NameTagManager::set($player, "[VIP] {name}\n{health}/{max_health} HP");

// Set a per-observer nametag (only $observer sees this tag on $target)
NameTagManager::setFor($observer, $target, "[Enemy] {name}");

// Refresh nametag for all online observers
NameTagManager::refresh($player);

// Reset to default nametag
NameTagManager::reset($player);
```

---

### 14. NotificationQueue -- Ordered Notification Delivery

Queue notifications that display one at a time without overlap, sorted by priority:

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
$count = NotificationQueue::pending($player); // int

// Clear all queued notifications
NotificationQueue::clear($player);
```

---

### 15. HudComposer -- Layer-Based HUD Management

Compose multiple HUD elements per player with priority-based ordering:

```php
use imperazim\hud\composer\HudComposer;
use imperazim\hud\scoreboard\ScoreBoardManager;

$composer = new HudComposer();

// Add a scoreboard layer (priority 0 = applied first)
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

// Check and list layers
$composer->hasLayer($player, 'scoreboard'); // true
$composer->getLayers($player);              // ['scoreboard', 'bossbar']

// Re-apply all active layers in priority order
$composer->refresh($player);

// Remove a specific layer (calls its remove callback)
$composer->removeLayer($player, 'bossbar');

// Clear all layers for a player
$composer->clearAll($player);

// Clear all layers for all players
$composer->clearEverything();
```

---

## Class Reference

| Class | Namespace | Description |
|-------|-----------|-------------|
| `BossBar` | `bossbar` | Core boss bar with title, subtitle, percentage, and color |
| `AnimatedBossBar` | `bossbar` | Boss bar with automatic linear and ping-pong animations |
| `DiverseBossBar` | `bossbar` | Boss bar showing different content per player |
| `BossBarPool` | `bossbar` | Static registry for managing multiple named boss bars |
| `ScoreBoard` | `scoreboard` | Scoreboard data model with lines and display settings |
| `ScoreLine` | `scoreboard` | Individual scoreboard line (score 1-15) |
| `ScoreBoardManager` | `scoreboard` | Static manager for sending/removing scoreboards |
| `ScoreBoardTemplates` | `scoreboard` | Pre-built templates with placeholder resolution |
| `PeriodicUpdate` | `scoreboard` | Scheduled automatic scoreboard refresh |
| `CooldownHUD` | `cooldown` | Auto-decrementing boss bar for cooldown visualization |
| `ToastManager` | `message` | Achievement-style toast popup notifications |
| `TitleManager` | `message` | Title/subtitle screen messages with fade control |
| `ActionBarManager` | `message` | Action bar text above the hotbar |
| `TabList` | `tablist` | TAB player list header and footer |
| `NameTagManager` | `nametag` | Global and per-observer custom nametags |
| `NotificationQueue` | `notification` | Priority queue for sequential notification delivery |
| `HudComposer` | `composer` | Layer-based multi-HUD element manager |
| `HudException` | `exception` | Runtime exception for HUD-related errors |

---

## License & Contributing

MIT. Pull requests and suggestions are welcome!

---

## Useful Links

- [PocketMine-MP](https://pmmp.io/)
- [ImperaZim on GitHub](https://github.com/ImperaZim)

---

Questions? Open an issue or contribute on GitHub!
