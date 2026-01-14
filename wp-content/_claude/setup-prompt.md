# Setup-prompt for nye prosjekter

Kopier denne prompten til et nytt Claude Code-prosjekt for å sette opp samme workflow:

---

## Prompt:

```
Jeg vil sette opp en strukturert workflow for dette prosjektet med session-logging og kontekst-oppretthold mellom sesjoner.

Opprett følgende filer:

1. **CLAUDE.md** i prosjektroten - Hovedinstruksjoner med:
   - Prosjektoversikt (utforsk kodebasen først)
   - Arkitektur og mappestruktur
   - Viktige filer og funksjoner
   - Tech stack
   - Vanlige oppgaver

2. **_claude/** mappe med workflow-filer:
   - **session-log.md** - Sesjonslogg med "Sist oppdatert", "Aktiv kontekst", "Neste steg", changelog
   - **instructions.md** - Workflow-instruksjoner for Claude Code
   - **feature-request-guide.md** - Mal for feature requests
   - **setup-prompt.md** - Denne setup-guiden (for referanse)

3. **.claude/settings.local.json** - Session-hook som automatisk leser kontekst ved hver prompt:

{
  "hooks": {
    "UserPromptSubmit": [
      {
        "matcher": "",
        "hooks": [
          {
            "type": "command",
            "command": "cat _claude/session-log.md _claude/instructions.md 2>/dev/null || true"
          }
        ]
      }
    ]
  }
}

Tilpass filstier i hook-kommandoen hvis _claude/ ligger i en undermappe (f.eks. wp-content/_claude/).

Start med å utforske prosjektet, så opprett filene med innhold tilpasset denne kodebasen.
```

---

## MCP Servere

Legg til disse MCP-serverne for utvidet funksjonalitet:

### GitHub (global - anbefalt)
```bash
claude mcp add github -s user -- npx -y @modelcontextprotocol/server-github
```
Krever `GITHUB_PERSONAL_ACCESS_TOKEN` miljøvariabel. Legg til i `~/.claude/settings.json`:
```json
{
  "mcpServers": {
    "github": {
      "command": "/opt/homebrew/bin/npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_PERSONAL_ACCESS_TOKEN": "din_token_her"
      }
    }
  }
}
```

### Stripe (global - anbefalt for betalingsprosjekter)
```bash
claude mcp add stripe -s user -- npx -y @stripe/mcp --tools=all --api-key=sk_test_xxx
```
Eller legg til i `~/.claude/settings.json`:
```json
{
  "mcpServers": {
    "stripe": {
      "command": "npx",
      "args": ["-y", "@stripe/mcp", "--tools=all", "--api-key=sk_test_xxx"]
    }
  }
}
```

### Trello (prosjekt-spesifikk)
```bash
claude mcp add trello -s project -- npx -y @delorenj/mcp-server-trello
```
Legg til API-nøkler i `.mcp.json`:
```json
{
  "mcpServers": {
    "trello": {
      "type": "stdio",
      "command": "npx",
      "args": ["-y", "@delorenj/mcp-server-trello"],
      "env": {
        "TRELLO_API_KEY": "din_api_key",
        "TRELLO_TOKEN": "din_token"
      }
    }
  }
}
```
Hent API-nøkkel: https://trello.com/app-key
Hent token: https://trello.com/1/authorize?expiration=never&scope=read,write&response_type=token&key=DIN_API_KEY

### Chrome DevTools (prosjekt-spesifikk)
```bash
claude mcp add chrome-devtools -s project -- npx -y chrome-devtools-mcp@latest
```
Gir tilgang til browser-automatisering, DOM-inspeksjon, nettverksforespørsler og console-meldinger.

### Figma Desktop (prosjekt-spesifikk)
Krever Figma Desktop-appen med MCP-plugin aktivert.
```json
{
  "mcpServers": {
    "figma-desktop": {
      "type": "http",
      "url": "http://127.0.0.1:3845/mcp"
    }
  }
}
```

### Komplett `.mcp.json` eksempel
```json
{
  "mcpServers": {
    "chrome-devtools": {
      "type": "stdio",
      "command": "npx",
      "args": ["-y", "chrome-devtools-mcp@latest"],
      "env": {}
    },
    "figma-desktop": {
      "type": "http",
      "url": "http://127.0.0.1:3845/mcp"
    },
    "trello": {
      "type": "stdio",
      "command": "npx",
      "args": ["-y", "@delorenj/mcp-server-trello"],
      "env": {
        "TRELLO_API_KEY": "din_api_key",
        "TRELLO_TOKEN": "din_token"
      }
    }
  }
}
```

---

## Sikkerhet

Legg til i `.gitignore`:
```gitignore
# MCP configuration (contains API keys)
.mcp.json
/.mcp.json

# Claude local settings (may contain sensitive data)
.claude/settings.local.json
```

**Viktig:**
- `.mcp.json` inneholder API-nøkler (Trello, Stripe, etc.) - ALDRI commit til git
- `.claude/settings.local.json` kan inneholde sensitive hooks - vurder om den skal committes
- Globale MCP-servere (`-s user`) lagres i `~/.claude/settings.json` - utenfor repo
- Bruk miljøvariabler eller secrets manager for produksjon
- Roter API-nøkler regelmessig, spesielt hvis de ved uhell committes

---

## Notater

- Juster `cat`-kommandoen i hooken hvis filene ligger i en undermappe (f.eks. `wp-content/`)
- Hooken kjører ved hver prompt og gir Claude kontekst automatisk
- MCP-servere er prosjekt-spesifikke (lagres i `.mcp.json`) med mindre du bruker `-s user` for global
- Kjør `/mcp` i Claude Code for å se status på alle MCP-servere
- Restart Claude Code etter å ha lagt til nye MCP-servere

---

## Filstruktur etter oppsett

```
prosjekt/
├── .claude/
│   └── settings.local.json    # Session hooks
├── .mcp.json                   # MCP servere (gitignored)
├── .gitignore                  # Inkluderer .mcp.json
├── CLAUDE.md                   # Prosjektdokumentasjon
└── _claude/                    # Claude workflow-filer
    ├── session-log.md          # Sesjonslogg
    ├── instructions.md         # Workflow-instruksjoner
    ├── feature-request-guide.md # Feature request mal
    └── setup-prompt.md         # Denne filen
```

For WordPress/monorepo (filer i undermappe):
```
prosjekt/
├── .claude/
│   └── settings.local.json
├── .mcp.json
├── CLAUDE.md
└── wp-content/
    └── _claude/                # Samlet i én mappe
        ├── session-log.md
        ├── instructions.md
        ├── feature-request-guide.md
        └── setup-prompt.md
```
