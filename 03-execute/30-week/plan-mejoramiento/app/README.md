# app

The operator frontend: React with Vite, no component library (D-13).

## The seven screens

| Screen | User story | Role |
|---|---|---|
| Sign in | US-01 | public |
| Catalogue (search, create, edit, retire) | US-03, US-04 | read for both, write for admin |
| Producers (search, create, equipment out) | US-05 | admin, advisor |
| Record a rental | US-06 | admin, advisor |
| Rentals and returns | US-07, US-08 | admin, advisor |
| Overdue | US-08 | admin, advisor |
| Most requested | US-10 | admin, advisor |
| Advisors | US-02 | admin |

## Running it

```bash
npm install
cp .env.example .env     # point VITE_API_URL at a running API
npm run dev              # http://localhost:5173
npm test                 # the client, the routes and the formatters
npm run build
```

## What the frontend decides, and what it does not

It decides nothing about the rules. The limits are **shown** so the operator is
not surprised — two pieces per rental, two out per producer, four days — and
**enforced by the server**. When the API refuses, the screen reports the message
the API wrote, because that message is already written for the person reading it.

Three choices worth stating:

- **The token lives in `sessionStorage`.** It lasts eight hours on the server,
  and `localStorage` would keep it on a shared machine after the tab is closed.
  It is not a cookie, so it cannot be sent by a cross-site request.
- **Every request goes through one client.** That is what makes the bearer
  header impossible to forget and lets a 401 drop the session in one place.
  Otherwise each screen has to remember that an expired token looks exactly
  like a rejected password.
- **The interface text is Spanish; the code is English.** Identifiers, comments
  and commit messages are English (article XI); what a person reads is Spanish.
  `tool/check-language.mjs` enforces the first half and knows to skip JSX text,
  which is the second.
