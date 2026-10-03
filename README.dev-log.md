# Development Log — AI Recipe Card (WordCamp talk demo)

A saved history of the working sessions that produced this plugin (July 4–6, 2026).
Talk: **"The Future of AI in WordPress: Orchestrating the Interactivity API, Block Bindings, and LLMs"** — 20 minutes.

Companion docs: [README.summary.md](README.summary.md) (talk outline) · [README.script.md](README.script.md) (slide-by-slide script) · [README.daily-life-example.md](README.daily-life-example.md) (the restaurant analogy).

---

## 1. Choosing the demo

- Compared two demo concepts: Gemini's "AI Product Review Optimizer" vs. the "AI Recipe Card".
- Kept Gemini's scope instinct (one flow, mocked AI) and its framing line ("AI generating HTML is messy; AI should write data"), but rejected its Step 5: it claimed a frontend button updating meta would make Block Bindings "re-render" — **bindings resolve server-side at render time and have no client reactivity**, so that demo would fail live.
- That flaw became the talk's centerpiece instead: *"Bindings own render time; the Interactivity API owns everything after; the meta field is the contract between them."*

## 2. What was built

One flow: messy text → AI → schema-validated post meta → bound core blocks → interactive frontend card.

| File | Role |
|---|---|
| `includes/schema.php` | One JSON schema shared by the AI prompt, meta registration, and REST validation ("the contract") |
| `includes/meta.php` | `register_post_meta` for summary, prep/cook time, servings, difficulty, ingredients array |
| `includes/ai.php` | `wp_ai_client_prompt()->as_json_response($schema)` extraction, mock fallback, REST route `airecipe/v1/extract`, Ability `ai-recipe-card/extract-recipe` |
| `includes/bindings.php` | Server bindings source `airecipe/fields` |
| `includes/pattern.php` | Pattern of **core** blocks bound to the AI meta + the interactive block |
| `src/recipe-card/` | Interactive block: `render.php` (server render + PHP derived state), `view.js` (store, 390-byte module), `index.js` (sidebar panel, client bindings source with `getFieldsList`, editor preview) |

Environment verified during the build: WordPress **7.0** with `ai-client.php`, `abilities-api`, and Block Bindings in core; built with `@wordpress/scripts` (`--experimental-modules` for `viewScriptModule`); pnpm.

## 3. Issues found and fixed (the bug stories — use them on stage)

### a. Client bindings API shape
This WP 7.0 build passes `select` (not the older documented `registry`) to `getValues()`. Verified against `wp-includes/js/dist/block-editor.js` before shipping. `getFieldsList()` receives `{ select, context }` and returns `{ label, type, args }[]`.

### b. Blank quantities (the "printed menu with blanks" bug)
Screenshot showed the qty column empty in the editor. Cause: the **server-side directive processor** evaluated `data-wp-text="state.scaledQuantity"`, found no such state in PHP, and blanked the span — in the editor preview *and* for no-JS visitors.
Fix: server-side **derived state via a PHP closure** in `render.php` (`wp_interactivity_state` + `wp_interactivity_get_context()`), mirroring the JS getter in `view.js`. Same rule, written once per side of the seam.

### c. Silent extract button
"Extract recipe with AI" worked but wrote to invisible post meta with no feedback → looked broken. Fix: success/error **snackbars** (`core/notices`) and a persistent summary line in the panel ("Extracted: prep 20 min · cook 45 min · serves 4 · Intermediate").

### d. Stale editor preview (the "paella instead of tortilla" bug)
The card preview used ServerSideRender, which reads **saved** DB meta — fresh extractions live in unsaved editor state, so the card showed yesterday's recipe. Fix: replaced SSR with a live preview rendered from `getEditedPostAttribute( 'meta' )` — the card now flips the instant extraction finishes. (Stepper is intentionally inert in the editor; directives run on the frontend.)

### e. Gemini "no models found that support text_generation"
Misleading error. Real cause: the AI plugin's **connector approval system** — it fingerprints the calling plugin via backtrace and blocks HTTP to provider APIs until that plugin is approved for the connector (stored in `wpai_connector_approvals`; pending queue in `wpai_connector_approval_pending`). The block even stopped the model-list fetch, hence "no models".
Fix: approved `ai/ai.php`, `ai-recipe-card/ai-recipe-card.php`, and `ai-provider-for-google/plugin.php` for the `google` connector. The AI Client then auto-selected `gemini-3.5-flash` — no model is ever named in plugin code.
**Talk anecdote:** WordPress treats AI credentials like phone app permissions — per-plugin consent.

## 4. Verification results

- All PHP files lint clean; both webpack bundles compile (editor bundle + 390-byte view module).
- Runtime smoke test via WP-CLI (Local site `Hmp_C9tmE`): block, bindings source, pattern, ability, meta all registered; mock extraction returns 9 ingredients.
- End-to-end render of demo post 14 ("Abuela's Paella Valenciana", `http://localhost:10008/abuelas-paella-valenciana/`): bindings resolved ("20 min", "Intermediate"), interactive block with directives rendered, 9 ingredients server-rendered.
- REST endpoint: 200, returns full recipe.
- **Real Gemini extraction verified** (tortilla española): normalized unquantified "olive oil" → 100 ml, salt → 1 tsp; schema-validated. Prep 15 / cook 25 / serves 4.

## 5. Current state & decisions

- `AIRECIPE_USE_MOCK` was flipped to **`false`** (real Gemini) for rehearsal. Decide before the talk: `true` = offline-safe canned paella (recommended on stage), `false` = live AI.
- Demo posts: **14** = paella, pattern inserted, meta saved (the safe one). **18** = tortilla test post.
- The active `cosparel` theme has an empty `index.php` → the site frontend renders nothing. For the frontend demo, present with a standard theme or fix cosparel's templates.
- Conceptual clarification (audience will ask): the plugin has exactly **one** custom block, and it exists for *behavior*, not display — bindings can only bind strings to existing attributes and are inert after render. Rule: *"If your custom block only displays a meta field, delete it and bind a paragraph."*

## 6. Remaining before the talk

- [ ] Re-extract on post 18 after reloading the editor (confirms the live-preview fix visually).
- [ ] Decide mock vs. live for the stage; set the constant accordingly.
- [ ] Pre-record fallback videos (editor demo, frontend demo, MCP agent clip).
- [ ] Playground blueprint + QR code for the final slide.
- [ ] Run through the script timings once (`README.script.md`).
