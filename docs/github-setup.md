# GitHub MCP + Git push

Repo: [ducdung196qtr/beplus-smart-search](https://github.com/ducdung196qtr/beplus-smart-search)

## 1. GitHub MCP (Cursor)

This plugin includes [`.cursor/mcp.json`](../.cursor/mcp.json) with the official [GitHub MCP server](https://github.com/github/github-mcp-server/blob/main/docs/installation-guides/install-cursor.md) (HTTP transport).

1. Create a [GitHub Personal Access Token](https://github.com/settings/tokens) with `repo` scope.
2. Set it in your **user** environment (do not commit the token):

   **Windows PowerShell (current session):**
   ```powershell
   $env:GITHUB_PERSONAL_ACCESS_TOKEN = "ghp_xxxxxxxx"
   ```

   **Windows (persistent):** System Properties → Environment Variables → User → New:
   - Name: `GITHUB_PERSONAL_ACCESS_TOKEN`
   - Value: your token

3. Restart Cursor.
4. Open **Settings → Tools & MCP** — **github** should show a green status.

After that, you can ask Cursor to create issues, read PRs, or inspect the remote repo via MCP.

## 2. First push to GitHub

From the plugin root (`beplus-smart-search/`):

```bash
git init
git add .
git commit -m "Initial commit: BePlus Smart Search plugin"
git branch -M main
git remote add origin https://github.com/ducdung196qtr/beplus-smart-search.git
git push -u origin main
```

Or use the helper:

```powershell
npm run git:push
```

Git will prompt for GitHub credentials. Use your GitHub username and a **Personal Access Token** as the password (not your GitHub account password).

## 3. Daily workflow

```bash
git add .
git commit -m "Describe your change"
git push
```

Before commit, run:

```bash
npm run build
npm run typecheck
npm run lint:php:all   # after npm run composer:install
```

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `git is not recognized` | Install [Git for Windows](https://git-scm.com/download/win), reopen terminal |
| MCP github red / offline | Set `GITHUB_PERSONAL_ACCESS_TOKEN`, restart Cursor |
| `remote origin already exists` | `git remote set-url origin https://github.com/ducdung196qtr/beplus-smart-search.git` |
| Push rejected (non-fast-forward) | Pull first: `git pull --rebase origin main` |
