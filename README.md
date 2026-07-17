# EchoPanel

EchoPanel is a web control panel for monitoring and managing Minecraft servers integrated with the [EchoCraft](https://github.com/phmbezerra/EchoCraft) plugin.

It receives server status, player counts, temporal reconstruction events, player traces and timeline snapshots through an authenticated API. It can also send remote restoration commands back to the Minecraft server.

## Features

- Server registration with an individual API key
- Online/offline status through heartbeat
- Player count and Minecraft version monitoring
- Recent reconstruction and player-trace events
- Visual timelines grouped by construction
- Version history with blocks, traces, actions and coordinates
- Remote restoration of temporal memories
- Command status tracking: pending, processing, completed or failed
- Individual event deletion and full activity cleanup
- Authentication and per-user server ownership

## How it works

```text
Minecraft Server
      │
      │ EchoCraft plugin
      ▼
Authenticated REST API
      │
      ▼
EchoPanel / Laravel
      │
      ├── stores heartbeat and activity data
      ├── builds visual timelines
      └── queues remote restoration commands
```

The EchoCraft plugin periodically checks the command queue. When a restoration is requested from the panel, the plugin claims the command, restores the selected memory and sends the result back to EchoPanel.

## Technologies

- PHP 8+
- Laravel
- Livewire
- Tailwind CSS
- SQLite
- Vite
- Pest / PHPUnit
- REST API

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- SQLite or another database supported by Laravel
- EchoCraft installed on a compatible Paper server

## Installation

Clone the repository:

```bash
git clone https://github.com/phmbezerra/EchoPanel.git
cd EchoPanel
```

Install dependencies:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure the database in `.env`. For SQLite:

```env
DB_CONNECTION=sqlite
```

Create the database file if necessary:

```powershell
New-Item -ItemType File -Path ".\database\database.sqlite" -Force
```

Run the migrations:

```bash
php artisan migrate
```

Start the development environment:

```bash
composer run dev
```

Alternatively, run Laravel and Vite separately:

```bash
php artisan serve
npm run dev
```

## Connecting EchoCraft

1. Register a Minecraft server in EchoPanel.
2. Open the server details page.
3. Copy the generated API key.
4. Configure EchoCraft on the Minecraft server:

```yaml
echopanel:
  enabled: true
  url: "http://your-echopanel-address/api/servers/heartbeat"
  token: "YOUR_API_KEY"
  interval-seconds: 30
```

5. Restart the Minecraft server.

When the connection is successful, EchoPanel will begin receiving heartbeat data and temporal activities.

> Never commit a real API key, `.env` file or local database to GitHub.

## Main integration flow

### Server monitoring

```text
EchoCraft
→ sends heartbeat
→ EchoPanel validates the API key
→ server status, version and player count are updated
```

### Temporal activities

```text
EchoCraft creates a snapshot
→ activity is sent to EchoPanel
→ the event is stored
→ timeline and activity cards are updated
```

### Remote restoration

```text
User clicks "Restore this version"
→ EchoPanel creates a pending command
→ EchoCraft claims the command
→ the memory is restored
→ EchoCraft reports success or failure
→ the panel updates the command status
```

## Security

Sensitive and local files are ignored by Git, including:

- `.env`
- SQLite database files
- API keys
- `vendor`
- `node_modules`
- runtime logs and caches

The API key shown on the server page must be treated as a secret.

## Related project

- [EchoCraft](https://github.com/phmbezerra/EchoCraft) — Minecraft temporal memory and reconstruction plugin

## Project status

EchoPanel is under active development and currently supports local development and integration with EchoCraft.

## Author

Developed by [Paulo Henrique](https://github.com/phmbezerra).
