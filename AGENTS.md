# Agent Notes

- The host environment does not provide `php` or `composer`.
- Run PHP and Composer commands through Docker Compose instead of first trying host commands.
- Prefer:
  - `docker compose run --rm app php ...`
  - `docker compose run --rm app composer ...`
  - `docker compose exec -T app php ...` when the running app container is appropriate
- Use `docker compose ps` if the app/db service state matters.
- Do not repeat a status note that host PHP/Composer is missing unless it is directly relevant to the user.
- When planning development work, implementing or testing, or evaluating code quality, performance, or architecture, read `docs/README.md` and `docs/plan.md` first, then follow the referenced documents as needed.
- Commit after each coherent work unit. Do not leave unrelated work units staged or unstaged together when the user has asked for implementation work.
