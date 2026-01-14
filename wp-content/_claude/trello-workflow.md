# Trello Workflow Template

## Board Setup
**[Project Name]** - Visual project overview and task management

## Lists
| List | Purpose |
|------|---------|
| Inbox | New ideas, unstructured thoughts |
| Backlog | Prioritized tasks ready for work |
| In Progress | Active work (max 1-2 cards) |
| Review | Testing/review before completion |
| Done | Completed tasks |
| Reference | Documentation and specifications |

---

## Card Format (standard)

```markdown
# [Card Title]

**Date:** YYYY-MM-DD
**From:** [Person/source]

---

## What's needed?
[Brief description of the task/requirement]

## Why?
[Rationale - why is this important?]

## User Story
As a [role], I want [feature], so that [benefit].

## Scope

**Must have:**
- Item 1
- Item 2

**Nice to have:**
- Item 1
- Item 2

## Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2

## Notes
- Relevant details
- References

## Links
- Resources and documentation
```

---

## Comment Format (updates)

```markdown
## [Status] ✅/⏳/❌

**Date:** YYYY-MM-DD HH:MM
**By:** [Person/tool]

### Changes:
- Item 1
- Item 2

### Files/resources:
- `path/to/file`

### Remaining:
- Item 1

### Next steps:
- What to do next
```

---

## Roles and Responsibilities

### Planner
- Creates and updates Trello cards based on requirements
- Uses the standard format
- Prioritizes backlog
- Moves cards between lists on status changes

### Executor
- Reads Trello cards for task details
- Adds update comments during work
- Marks acceptance criteria as completed

### Approver
- Visual overview via Trello board
- Validates and approves work
- Moves cards to Done

---

## Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  Inbox → Backlog → In Progress → Review → Done              │
│                                                             │
│    ↓        ↓           ↓           ↓                       │
│  Capture  Prioritize  Execute    Approve                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

1. **Capture:** Ideas and needs go to Inbox
2. **Prioritize:** Structure and move to Backlog when ready
3. **Execute:** Take card from Backlog → In Progress (limit WIP)
4. **Review:** Test/review before completion
5. **Complete:** Approved work moves to Done

---

## Best Practices

### Cards
- Cards should be **self-contained** - all necessary info in the card
- Use acceptance criteria actively - check off as you go
- Keep titles short and descriptive
- Add relevant labels for categorization

### Work in Progress (WIP)
- Limit "In Progress" to 1-2 cards per person
- Complete before starting new work
- Note blockers as comments

### Updates
- Add comment on each meaningful change
- Use status icons for quick visual overview:
  - ✅ Completed
  - ⏳ In progress
  - ❌ Blocked
  - 🔄 Changed

### Archiving
- Archive completed cards regularly (e.g., monthly)
- Keep Reference cards that have long-term value

---

## Labels (suggestions)

| Color | Category | Example |
|-------|----------|---------|
| 🔴 Red | Priority | Urgent |
| 🟠 Orange | Type | Bug |
| 🟡 Yellow | Type | Enhancement |
| 🟢 Green | Type | New feature |
| 🔵 Blue | Area | Frontend |
| 🟣 Purple | Area | Backend |
| ⚫ Gray | Status | Blocked |

---

## Customization

This is a base template. Adapt as needed:

- **Solo project:** Simplify roles, drop Review list
- **Team:** Add assignee field to cards
- **Agile/Scrum:** Add Sprint lists, estimates, story points
- **Support:** Add SLA labels, priority levels
- **Development:** Add lists for QA, Staging, Deployed