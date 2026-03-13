# BlackTenderscore MCP Server

Serveur MCP (Model Context Protocol) qui expose les données du plugin WordPress BlackTenderscore à Claude Desktop.

## Outils disponibles

| Outil | Description |
|-------|-------------|
| `bt_kpis` | KPIs résumés : CA, réservations, panier moyen, taux d'annulation, clients uniques, taux de repeat |
| `bt_timeline` | Série temporelle des réservations et CA (J/S/M) |
| `bt_top_products` | Classement des activités/produits par réservations ou CA |
| `bt_top_dates` | Meilleures journées (pics de réservations) |
| `bt_bookings_list` | Liste paginée des réservations individuelles |
| `bt_ga4` | Google Analytics 4 : sessions, utilisateurs, canaux, top pages |
| `bt_gsc` | Google Search Console : clics, position, top requêtes, quick wins |

## Installation

```bash
cd wp-content/plugins/blacktenderscore/mcp
npm install
cp .env.example .env
# Éditer .env avec WORDPRESS_URL et WORDPRESS_AUTH
```

## Générer WORDPRESS_AUTH

1. Dans WP Admin → Users → ton profil → "Application Passwords"
2. Créer un mot de passe nommé "Claude MCP"
3. Encoder en base64 :
   ```bash
   echo -n "admin:xxxx xxxx xxxx xxxx xxxx xxxx" | base64
   ```
4. Coller le résultat dans `.env` → `WORDPRESS_AUTH=...`

## Configuration Claude Desktop

Ajouter dans `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS) :

```json
{
  "mcpServers": {
    "blacktenderscore": {
      "command": "node",
      "args": ["/CHEMIN/ABSOLU/vers/blacktenderscore/mcp/index.js"],
      "env": {
        "WORDPRESS_URL": "https://ton-site.com",
        "WORDPRESS_AUTH": "base64encodé=="
      }
    }
  }
}
```

> **Note :** Les variables `env` dans la config Claude Desktop remplacent le `.env` — tu peux utiliser l'un ou l'autre.

## Configuration Claude Code (claude_code_config.json)

```json
{
  "mcpServers": {
    "blacktenderscore": {
      "command": "node",
      "args": ["/var/www/studiojae-dev/scala/wp-content/plugins/blacktenderscore/mcp/index.js"]
    }
  }
}
```

Avec un `.env` dans le dossier `mcp/` contenant les credentials.

## Test rapide

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | node index.js
```

## Exemples de questions à poser à Claude

- "Donne-moi les KPIs de janvier 2026 vs janvier 2025"
- "Quels sont nos 5 meilleurs produits cette année ?"
- "Comment évolue notre trafic Google depuis 3 mois ?"
- "Quelles requêtes SEO sont en position 4-10 et méritent un coup de pouce ?"
- "Montre-moi les réservations annulées du mois dernier"
