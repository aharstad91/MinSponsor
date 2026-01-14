# MinSponsor - Session Log

> **Shared context file between Claude Chat and Claude Code**
> Updated continuously by both parties to stay in sync.

---

## Last Updated
- **When:** 2026-01-13 23:45
- **By:** Claude Code
- **What:** Initialized project documentation for new workspace

---

## Active Context

### Current Focus
- Project documentation initialized and adapted from BIM Verdi template
- Ready for next development task

### Latest Changes
- Adapted `CLAUDE.md` for MinSponsor project
- Reset `SESSION-LOG.md` for fresh start
- Updated `CLAUDE-CODE-INSTRUCTIONS.md` with project-specific workflow
- Updated `FEATURE-REQUEST-GUIDE.md` for MinSponsor context

### Open Questions / Blockers
- None

### Important to Remember
- Stripe Connect implementation is primary focus
- Phase 1-4 completed, Phase 5-7 remaining (see `docs/implementation-plan.md`)
- URL structure: `/stott/{klubb}/{lag}/{spiller}/`
- Teams (Lag) have Stripe accounts, not players

---

## Changelog

### 2026-01-13

#### [Code] 23:45 - Project Documentation Setup
- Adapted documentation files from BIM Verdi to MinSponsor:
  - `CLAUDE.md` - Full project overview with CPTs, architecture, Stripe Connect
  - `SESSION-LOG.md` - Fresh session log (this file)
  - `CLAUDE-CODE-INSTRUCTIONS.md` - Updated workflow instructions
  - `FEATURE-REQUEST-GUIDE.md` - Updated for MinSponsor context
- **Files modified:**
  - `/CLAUDE.md`
  - `/wp-content/SESSION-LOG.md`
  - `/wp-content/CLAUDE-CODE-INSTRUCTIONS.md`
  - `/wp-content/FEATURE-REQUEST-GUIDE.md`

---

## Next Steps (Prioritized)

1. [ ] Continue Stripe Connect implementation (Phase 5: Payment flow)
2. [ ] Implement webhook handlers (Phase 6)
3. [ ] Prepare for deployment (Phase 7)

---

## Relevant Files

| File | Description |
|------|-------------|
| `CLAUDE.md` | Project documentation for Claude |
| `CLAUDE-CODE-INSTRUCTIONS.md` | Workflow instructions for Claude Code |
| `FEATURE-REQUEST-GUIDE.md` | Feature request guide |
| `themes/spons/docs/implementation-plan.md` | Stripe Connect implementation status |
| `themes/spons/docs/stripe-connect-spec.md` | Full Stripe Connect specification |
| `themes/spons/docs/security-invariants.md` | Security requirements |

---

## Notes

- **Tech stack:** WordPress + WooCommerce + Stripe Connect + Vipps
- **CPTs:** klubb (clubs), lag (teams), spiller (players)
- **Key relationship:** Teams have Stripe accounts, players route to team
- **Fee model:** Fees on top (supporter pays extra, team receives full amount)
