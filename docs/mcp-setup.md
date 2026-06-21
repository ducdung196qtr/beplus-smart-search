# MCP Setup — plugin.local Site Editor

Connect Cursor MCP to the local WordPress site **`plugin.local`**, including the Site Editor template:

```
http://plugin.local/wp-admin/site-editor.php?canvas=edit&p=%2Fwp_template%2Ftwentytwentyfive%2F%2Farchive-product
```

Config file: [`wp-content/.cursor/mcp.json`](../../../.cursor/mcp.json)

---

## MCP servers configured

| Server | Purpose | Use for Site Editor |
|--------|---------|---------------------|
| **playwright** | Browser automation (navigate, click, type, snapshot) | **Primary** — open Site Editor, interact with canvas UI |
| **wordpress-plugin-local** | WordPress Abilities via MCP Adapter (REST) | Posts, settings, abilities — not direct canvas DOM editing |

For **Site Editor canvas work**, use **Playwright MCP**. Ask the agent to navigate to the Site Editor URL, log in if needed, and interact with the editor.

For **WordPress data/API** (posts, options, abilities), use **wordpress-plugin-local** after plugins are activated and credentials are set.

---

## Prerequisites

- **Node.js 18+** (installed: use `node --version`)
- **Cursor** with MCP enabled: **Settings → Tools & MCP**
- **WordPress 7.0** at `http://plugin.local` (REST: `/wp-json/` returns 200)
- **Pretty permalinks** (not Plain) — required for `/wp-json/mcp/...`

---

## Step 1 — Activate WordPress MCP plugins

Plugins copied to `wp-content/plugins/`:

| Plugin | Path |
|--------|------|
| Abilities API | `plugins/abilities-api/` |
| MCP Adapter | `plugins/mcp-adapter/` |

1. Open `http://plugin.local/wp-admin/plugins.php`
2. Activate **Abilities API**
3. Activate **MCP Adapter**

Verify MCP endpoint (should **not** be 404):

```text
http://plugin.local/wp-json/mcp/mcp-adapter-default-server
```

Unauthenticated requests may return 401 — that is expected. A 404 means the plugin is not active.

---

## Step 2 — Create Application Password

WordPress MCP HTTP transport uses [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/).

1. Log in to `http://plugin.local/wp-admin/`
2. Go to **Users → Profile** (admin user)
3. Scroll to **Application Passwords**
4. Name: `Cursor MCP`
5. Click **Add New Application Password**
6. Copy the generated password (spaces are OK)

---

## Step 3 — Update Cursor MCP config

Edit [`wp-content/.cursor/mcp.json`](../../../.cursor/mcp.json):

```json
"wordpress-plugin-local": {
  "env": {
    "WP_API_URL": "http://plugin.local/wp-json/mcp/mcp-adapter-default-server",
    "WP_API_USERNAME": "your-admin-username",
    "WP_API_PASSWORD": "your-application-password-here"
  }
}
```

Template: [`.cursor/mcp.env.example`](../../../.cursor/mcp.env.example)

**Security:** Do not commit real passwords to git. Add `.cursor/mcp.json` to `.gitignore` if it contains credentials, or use environment variables.

---

## Step 4 — Enable MCP in Cursor

1. **Cursor → Settings → Tools & MCP**
2. Confirm **playwright** and **wordpress-plugin-local** appear
3. Toggle both **ON**
4. If servers show errors, click refresh or restart Cursor

First-time Playwright may download browsers:

```bash
npx playwright install chromium
```

---

## Step 5 — Connect to Site Editor (Playwright)

Ask the agent (with Playwright MCP enabled):

```text
Open http://plugin.local/wp-admin/site-editor.php?canvas=edit&p=%2Fwp_template%2Ftwentytwentyfive%2F%2Farchive-product
If redirected to login, stop and tell me — I will log in manually first.
```

**Recommended workflow:**

1. Log in to WordPress in your normal browser first (same session/cookies may not transfer — Playwright uses its own browser profile).
2. Or provide admin credentials only for the login screen (Playwright can fill the form).
3. After the Site Editor loads, use Playwright snapshots to inspect blocks and UI.

### Site Editor URL parts

| Param | Value | Meaning |
|-------|-------|---------|
| `canvas=edit` | edit mode | Visual editor canvas |
| `p` | `/wp_template/twentytwentyfive//archive-product` | Template: WooCommerce product archive (Twenty Twenty-Five) |

---

## Step 6 — WordPress MCP (abilities)

After `wordpress-plugin-local` is connected, the agent can:

- `discover-abilities` — list registered WordPress abilities
- `execute-ability` — run permitted abilities (posts, site data, etc.)

This does **not** replace Playwright for block canvas editing. Use both:

- **Playwright** → UI: Site Editor, block inserter, inspector
- **WordPress MCP** → API: create posts, read settings, plugin abilities

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| MCP servers not listed | Ensure `.cursor/mcp.json` is in workspace root (`wp-content/`) or project root Cursor opened |
| `wordpress-plugin-local` fails | Activate Abilities API + MCP Adapter; check permalinks |
| 401 on MCP endpoint | Set correct `WP_API_USERNAME` + Application Password |
| Playwright cannot reach `plugin.local` | Ensure Local site is running; add host to `hosts` file if needed |
| Site Editor login loop | Log in manually in Playwright browser first; check admin URL |
| `wp` command not found | Use HTTP transport (configured) — WP-CLI STDIO is optional on Windows |

---

## Optional — WP-CLI STDIO (if WP-CLI installed later)

```json
{
  "mcpServers": {
    "wordpress-stdio": {
      "command": "wp",
      "args": [
        "--path=C:/Users/MieuDaiNhan/Local Sites/plugin/app/public",
        "mcp-adapter",
        "serve",
        "--server=mcp-adapter-default-server",
        "--user=admin"
      ]
    }
  }
}
```

Requires [WP-CLI](https://wp-cli.org/) on PATH. HTTP transport above works without WP-CLI.

---

## Related

- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)
- [@automattic/mcp-wordpress-remote](https://www.npmjs.com/package/@automattic/mcp-wordpress-remote)
- [Playwright MCP](https://playwright.dev/docs/getting-started-mcp)
- Nextora codegraph MCP (theme only): `themes/nextora-develop/.cursor/mcp.json`
