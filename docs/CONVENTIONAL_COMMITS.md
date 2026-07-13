# Conventional Commits

> A specification for adding human and machine readable meaning to commit messages.

**References:** [Summary](https://www.conventionalcommits.org/en/v1.0.0/#summary) · [Full Specification](https://www.conventionalcommits.org/en/v1.0.0/#specification) · [Contributing](https://github.com/conventional-commits/conventionalcommits.org)

---

## Commit Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Structural Elements

| Element | Description |
|---|---|
| `fix:` | Patches a bug — correlates with `PATCH` in SemVer |
| `feat:` | Introduces a new feature — correlates with `MINOR` in SemVer |
| `BREAKING CHANGE:` | Introduces a breaking API change — correlates with `MAJOR` in SemVer |
| Other types | `build`, `chore`, `ci`, `docs`, `style`, `refactor`, `perf`, `test`, etc. |

> **Scope** is optional and written in parentheses, e.g.: `feat(parser): add ability to parse arrays`

---

## Examples

```bash
# Simple new feature
feat: allow provided config object to extend other configs

# Breaking change with footer
feat: allow provided config object to extend other configs

BREAKING CHANGE: `extends` key in config file is now used for extending other config files

# Breaking change with exclamation mark
feat!: send an email to the customer when a product is shipped

# Breaking change with scope
feat(api)!: send an email to the customer when a product is shipped

# Breaking change with ! and footer
feat!: drop support for Node 6

BREAKING CHANGE: use JavaScript features not available in Node 6.

# Without body
docs: correct spelling of CHANGELOG

# With scope
feat(lang): add Polish language

# Multi-paragraph body and multiple footers
fix: prevent racing of requests

Introduce a request id and a reference to latest request. Dismiss
incoming responses other than from latest request.

Remove timeouts which were used to mitigate the racing issue but are
obsolete now.

Reviewed-by: Z
Refs: #123

# Revert commit
revert: let us never again speak of the noodle incident

Refs: 676104e, a215868
```

---

## Specification

1. Commits **MUST** be prefixed with a type, which consists of a noun (`feat`, `fix`, etc.), followed by an optional scope, an optional `!`, and a required colon and space.
2. The type `feat` **MUST** be used when a commit adds a new feature.
3. The type `fix` **MUST** be used when a commit represents a bug fix.
4. A scope **MAY** be provided after a type, consisting of a noun in parentheses, e.g.: `fix(parser):`.
5. A description **MUST** immediately follow the colon and space after the type/scope prefix.
6. A longer commit body **MAY** be provided after the short description, beginning one blank line after.
7. A commit body is free-form and **MAY** consist of any number of newline-separated paragraphs.
8. One or more footers **MAY** be provided one blank line after the body, in the format `token: value` or `token #value`.
9. A footer's token **MUST** use `-` in place of whitespace characters (e.g., `Acked-by`), except for `BREAKING CHANGE`.
10. A footer's value **MAY** contain spaces and newlines; parsing terminates when the next valid footer token is found.
11. Breaking changes **MUST** be indicated in the type/scope prefix or as an entry in the footer.
12. If included in the footer, a breaking change **MUST** consist of `BREAKING CHANGE: <description>`.
13. If included in the prefix, a breaking change **MUST** be indicated by a `!` immediately before the `:`. If `!` is used, `BREAKING CHANGE:` in the footer **MAY** be omitted.
14. Types other than `feat` and `fix` **MAY** be used, e.g.: `docs: update ref docs`.
15. The units of information that make up Conventional Commits **MUST NOT** be treated as case-sensitive, except for `BREAKING CHANGE` which **MUST** be uppercase.
16. `BREAKING-CHANGE` **MUST** be treated as a synonym for `BREAKING CHANGE` when used as a token in a footer.

---

## Benefits

- Automatically generate CHANGELOGs.
- Automatically determine a semantic version bump based on commit types.
- Communicate the nature of changes to teammates, the public, and other stakeholders.
- Trigger build and publish processes.
- Make it easier for contributors to explore a more structured commit history.

---

## FAQ

**How should I handle commits during initial development?**
Proceed as if the product has already been released. Others still need to know what was fixed or changed.

**Should commit types be uppercase or lowercase?**
Either is fine, but it must be consistent.

**What if a commit fits more than one type?**
Make multiple separate commits. This encourages more organized commits.

**Does this slow down rapid development?**
No — it slows down disorganized rapid development, and helps you move faster in the long run.

**How does this relate to SemVer?**
- `fix` → `PATCH`
- `feat` → `MINOR`
- `BREAKING CHANGE` → `MAJOR`

**What if I use the wrong commit type?**
- Before merging: use `git rebase -i` to edit the commit history.
- After releasing: adjust according to the tools and processes used.
- Types outside the spec: not fatal, they will simply be ignored by spec-based tools.

**Must all contributors follow this spec?**
Not required. A squash-based merge workflow allows lead maintainers to clean up commit messages at merge time.

**How do I handle reverting a commit?**
Use the `revert` type with a footer referencing the SHA of the reverted commit:

```
revert: let us never again speak of the noodle incident

Refs: 676104e, a215868
```
