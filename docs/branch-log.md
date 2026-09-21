# Branch log

Branches are stacked: each feature branch is created from the previous one,
never from `main` (except the first).

- `feat/user-auth` (based on `main`): tenancy foundation, public website,
  organization registration, authentication, branded dashboard, user
  management, settings, tests and README.
- `feature/02-ui-redesign` (based on `feat/user-auth`): redesign the public site
  and dashboard in the Imagine/PrebuiltUI style — Geist typography, monochrome
  palette with opacity tiers, soft radii and restrained motion.

- `feature/03-workspace-redesign` (based on `feature/02-ui-redesign`): reference-inspired public website, workspace UI, new identity and platform administration.
- `feature/04-calling-categories` (based on `feature/03-workspace-redesign`): Calling module part 1 — call categories and business types (tables, policies, CRUD pages, sidebar entry).
- `feature/05-calling-businesses` (based on `feature/04-calling-categories`): Calling module part 2 — full business records (contact fields, multiple phone numbers, screenshots), duplicate phone detection, and private per-organization screenshot storage.
- `feature/06-calling-workflow` (based on `feature/05-calling-businesses`): Calling module part 3 — the call outcome workflow (not answered / pending / interested / rejected), call history, To Call and Answered lists with filters and search, demo seed data, and the README section.
- `feature/07-unified-brand-theme` (based on `feature/06-calling-workflow`): remove organization color selection and unify the workspace with the Startsuite lime, black, and white theme.
