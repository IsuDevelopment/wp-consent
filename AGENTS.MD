# AGENTS.md

## Agent file map

| Path | Contents | When to read |
| --- | --- | --- |
| `AGENTS.md` | Always-on rules and repository map | Always |
| `.agents/code-map/<target>.md` | Structure, entry points, and build outputs of one package | Before working on that package |
| `.agents/docs/README.md` | Index of specification docs | When a template or feature may already have a spec |
| `.claude/skills/<name>/SKILL.md` | Repeatable workflows | When the task matches a skill |
| `.github/workflows/` | Deploy behavior | Before anything deploy-related |
| `.superpowers/sdd/<plan-name>/progress.md` | Current task state, local-only | First, when resuming work mid-task |

`.agents/README.md` describes the hub. `GEMINI.md` is a pointer here. Skills have
exactly one home, `.claude/skills/` — no copies, no symlinks; every tool reads
them from that path.