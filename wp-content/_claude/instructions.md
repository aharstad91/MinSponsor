# Claude Code - Session Workflow Instructions

> **IMPORTANT:** Read and follow these instructions in every session.

## At Session Start (ALWAYS)

1. **Read session-log.md** at `wp-content/_claude/session-log.md`
2. **Give brief summary** of last status to Andreas
3. **Check "Next Steps"** to see what should be prioritized

## During Work

### Update session-log.md when you:
- Create, modify, or delete files
- Fix bugs or implement features
- Encounter blockers or problems
- Complete a task from "Next Steps"

### Format for entries:

```markdown
#### [Code] HH:MM - Short description
- What was done
- Files affected: `filename.php`, `other-file.php`
- **Next:** Any follow-up if needed
```

### Also update:
- **"Last Updated"** section at top
- **"Active Context"** if focus changes
- **"Next Steps"** - check off completed, add new ones

## At Session End

Before ending a longer session, update session-log.md with:
1. Summary of what was done
2. Any unresolved problems
3. Recommended next steps

## Communication with Claude Chat

Andreas also uses Claude Chat (same app, different tab) for:
- Strategic planning
- Architecture and design discussions
- Review of documentation

Claude Chat also updates session-log.md. **Always read the file** to see if Chat has added decisions or changed priorities.

## Important Project Files

| File | Read when |
|------|-----------|
| `_claude/session-log.md` | **ALWAYS** at start |
| `CLAUDE.md` | When needing project overview |
| `themes/spons/docs/implementation-plan.md` | Before Stripe Connect work |
| `themes/spons/docs/stripe-connect-spec.md` | For payment flow details |
| `themes/spons/docs/security-invariants.md` | Before security-related changes |

## Example of Good Session

```
Andreas: "Continue where we left off"

Claude Code: *reads session-log.md*

"According to session-log.md, we last worked on X.
Chat has since decided Y.
Next step is Z. Should I continue with that?"

*does the work*

*updates session-log.md*

"Done with Z. Have updated session-log.md with changes."
```

---

## Project-Specific Notes

### Namespace Convention
All PHP classes should use the `MinSponsor` namespace:
```php
namespace MinSponsor\Admin;
namespace MinSponsor\Checkout;
```

### Key Documentation
- **Stripe Connect spec:** `themes/spons/docs/stripe-connect-spec.md`
- **Implementation status:** `themes/spons/docs/implementation-plan.md`
- **Security rules:** `themes/spons/docs/security-invariants.md`

### Testing Stripe Locally
```bash
# Start webhook listener
stripe listen --forward-to localhost:8888/spons/wp-json/minsponsor/v1/stripe-webhook

# Test events
stripe trigger payment_intent.succeeded
stripe trigger account.updated
```

---

**This file is located at:** `wp-content/_claude/instructions.md`
