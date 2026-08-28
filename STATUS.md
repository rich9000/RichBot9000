# richbot9000.com

**Apache:** `richbot9000.com`, `www.richbot9000.com`, `api.richbot9000.com` (HTTPS → this folder)  
**Document root:** `public/`  
**Last folder activity:** 2025-08-26

## Stack

- **Backend:** Laravel 11, PHP ^8.2, Laravel Octane (OpenSwoole)
- **Auth:** Sanctum
- **Integrations:** Stripe, Twilio, Ollama, FFmpeg, DataTables, OpenAI (incl. realtime WebSocket client)
- **Frontend:** Vite 5, Tailwind 3, Alpine.js

## Purpose

Flagship **RichBot** platform: AI assistants, tools, SMS/voice, chat, displays, merch, surveys, phone trees, cronbots, and related automation/communications features.

## Implementation status

**Mature production system** with **stale documentation**.

- Very large schema and API (`routes/api.php`, `routes/web.php`)
- `README.md` still references old “RMA” notes; see `STRUCTURE.md` for layout
- HTTP vhost mispoints to missing `admin.richbot9000.com/public` — HTTPS is correct

## Notes

- Admin subdomain is configured separately but **not deployed** under `/var/www/html/admin.richbot9000.com`
- Related tools: `mm.richbot9000.com`, `projman`, `/var/www/html/mcp.richbot9000.com`
