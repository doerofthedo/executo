## Token Optimization for Executo Repo

**Goal:** Reduce token usage when working with AI coding assistants across the repository.

### Implementation

**1. Create `.claudeignore` in repo root**

Exclude large, generated, or non-essential folders:
```
# Claude / Copilot ignore patterns
# Excludes large, generated, or non-essential folders to save tokens

# Dependencies
node_modules/
vendor/

# Build outputs & caches
backend/bootstrap/cache/
backend/storage/logs/
backend/storage/framework/
frontend/dist/
*.tsbuildinfo

# IDE & development
.vscode/
.idea/
.DS_Store
*.swp
*.swo

# Version control
.git/
.gitignore
```

**2. Add Token Optimization section to AGENTS.md**

Insert after "Docker Dev Environment" section:

```markdown
## Token Optimization

To reduce token usage when working with AI coding assistants, configure the following:

### Claude / Claude.dev
- Use `.claudeignore` (repo root) to exclude large folders:
  - `node_modules/`, `vendor/` — dependency trees (auto-regenerated)
  - `backend/storage/`, `backend/bootstrap/cache/` — runtime logs and caches
  - `old/` — legacy/deprecated code
  - `.git/` — version control metadata
- The `.claudeignore` file patterns mirror `.gitignore` conventions.

### GitHub Copilot
- Copilot respects `.gitignore` patterns automatically.
- Ensure `node_modules/` and `vendor/` are in `.gitignore` (they already are).
- For Copilot Chat, large files and excluded folders won't be indexed into context.

### OpenAI Codex / ChatGPT
- Codex doesn't have built-in exclusion support.
- When pasting code snippets to Codex, **manually exclude**:
  - Node/Composer lock files (use `npm ls` / `composer show` summaries instead)
  - Generated migration files older than 1 month
  - Binary assets and build outputs
- Prefer asking Codex targeted questions about specific files rather than pasting entire directories.

### General Rule
When in doubt, ask agents to summarize only the files they need. Use `semantic_search` or `grep_search` tools to find relevant code before sharing context.
```

### Verification

1. Confirm `.claudeignore` exists in repo root.
2. Verify AGENTS.md "Token Optimization" section is placed correctly (after Docker, before Production Deploy).
3. Test with Claude tool to ensure excluded folders are not indexed.
4. Document in team guidelines that `.claudeignore` applies to all Claude-based tools.

### Scope

- **Workspace-level**: Both files go into the repo root / AGENTS.md (shared with team).
- **Tool Coverage**: Applies to Claude, Copilot, and provides guidance for Codex.
- **Ongoing**: Review and update `.claudeignore` as new large folders emerge (e.g., new build outputs, data exports).
