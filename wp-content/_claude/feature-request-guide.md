# MinSponsor - Feature Request Guide

> This guide is for stakeholders who want to submit new feature requests.

---

## How It Works

1. **Describe the need** to Claude Chat (use the prompt below)
2. **Claude generates** a complete feature request
3. **Copy the text** and paste into a new task/ticket
4. **Andreas/Claude validates** and prioritizes

---

## Prompt for Claude Chat

Copy this text and start a new Claude Chat session:

```
You are a product assistant for MinSponsor - a sports sponsorship platform. Your job is to help me formulate feature requests for the website.

When I describe a wish or need, ask follow-up questions to understand:
1. What should the user be able to do?
2. Why is this important?
3. Who is the user? (supporter, club admin, team treasurer, platform admin)
4. What is "must have" vs "nice to have"?
5. How do we know it works?

When you have enough info, generate a complete feature request in this format:

---

# [Title]

**Date:** [dd.mm.yyyy]
**From:** [Name]

---

## What is needed?
[Description of desired functionality]

## Why?
[Business value / user benefit]

## User Story
As a [role], I want to [action], so that [benefit].

## Scope
**Must have:**
- [Requirement 1]
- [Requirement 2]

**Nice to have:**
- [Addition 1]

## Acceptance Criteria
- [ ] [Criterion 1]
- [ ] [Criterion 2]
- [ ] [Criterion 3]

## Links
- [Relevant links if applicable, otherwise remove this section]

---

Be specific and avoid vague formulations. Acceptance criteria should be testable.
```

---

## Example of Completed Request

Here's an example of what a filled-out feature request looks like:

---

# QR Code Download Button on Team Page

**Date:** 13.01.2026
**From:** Andreas

---

## What is needed?
Add a "Download QR Code" button on the team admin page that lets admins download the sponsorship QR code as a PNG file for printing on posters, flyers, etc.

## Why?
Teams need to promote their sponsorship page at events and games. Having an easy way to get a print-ready QR code will increase sponsorship sign-ups and make the platform more useful for team admins.

## User Story
As a team treasurer, I want to download the sponsorship QR code, so that I can print it on promotional materials.

## Scope
**Must have:**
- Download button on team edit page in admin
- High-resolution PNG output (min 1024x1024)
- QR code links to team sponsorship page

**Nice to have:**
- Option to include team logo in QR code
- Multiple format options (PNG, SVG)

## Acceptance Criteria
- [ ] "Download QR Code" button visible on team edit page
- [ ] Clicking button downloads PNG file
- [ ] QR code scans correctly and links to `/stott/{klubb}/{lag}/`
- [ ] Downloaded image is at least 1024x1024 pixels

---

## Tips for Good Requests

**Be specific:**
- Bad: "Make payments better"
- Good: "Show payment confirmation with receipt number on thank-you page"

**Think about the user:**
- Bad: "We need a database for X"
- Good: "As a supporter, I want to see my donation history"

**Testable criteria:**
- Bad: "It should work well"
- Good: "User can click 'Download' and receives a .pdf file"

---

## MinSponsor-Specific Context

When writing requests, keep in mind:

### User Roles
| Role | Description |
|------|-------------|
| **Supporter** | Person donating money to team/player |
| **Team Treasurer** | Manages team's Stripe account |
| **Club Admin** | Manages club and its teams |
| **Platform Admin** | MinSponsor administrators |

### Key Features
- **Sponsorship pages:** `/stott/{klubb}/{lag}/{spiller}/`
- **Payment methods:** Stripe (card), Vipps (Norwegian mobile payment)
- **Donation types:** One-time and monthly subscriptions
- **Fee model:** Fees added on top (team receives full amount)

### Technical Notes
- Teams have Stripe Connect accounts (not players)
- QR codes link to WooCommerce products with context params
- Orders store `_ms_club_id`, `_ms_team_id`, `_ms_player_id`

---

## Contact

Questions about this process? Contact Andreas.
