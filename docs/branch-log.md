# Branch log

Branches are stacked: each feature branch is created from the previous one,
never from `main` (except the first).

- `feat/user-auth` (based on `main`): tenancy foundation, public website,
  organization registration, authentication, branded dashboard, user
  management, settings, tests and README.
- `feature/02-ui-redesign` (based on `feat/user-auth`): redesign the public site
  and dashboard in the Imagine/PrebuiltUI style — Geist typography, monochrome
  palette with opacity tiers, soft radii and restrained motion.
