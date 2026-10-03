# Talk Summary — The Future of AI in WordPress

**Orchestrating the Interactivity API, Block Bindings, and LLMs** · 20-minute talk

The demo plugin: **AI Recipe Card**. One flow — messy text → AI → structured meta → bound core blocks → interactive frontend — with each talk section mapped to a file in this plugin.

## 1. The problem (2 min)

AI writing raw HTML layouts is messy: it fights your theme's CSS, produces unreviewable diffs, and every generation is different. AI should write **data**, and WordPress should render it.

## 2. AI → meta (4 min)

Paste messy text ("grandma's paella email"), one click, and the WordPress 7.0 AI Client returns **schema-validated JSON** into post meta.

- The schema is the contract: `includes/schema.php` — one JSON schema shared by the AI prompt (`as_json_response()`), the meta registration, and the REST validation.
- The extraction: `includes/ai.php` — `wp_ai_client_prompt()->using_system_instruction()->as_json_response( $schema )->generate_text()`. Provider-agnostic, no vendor hardcoded on stage.
- Mock/cache the response so it works offline: `AIRECIPE_USE_MOCK` (default `true`) returns a canned paella so the demo survives venue wifi.

## 3. Block Bindings (5 min)

`register_block_bindings_source()` in `includes/bindings.php`, plus `getFieldsList()` in `src/recipe-card/index.js`, then bind a **core paragraph** live via the WordPress 6.9 bindings dropdown UI (`includes/pattern.php` ships a pre-bound pattern as backup). Core blocks become your template engine — no custom display blocks, no render callbacks for layout.

## 4. Interactivity API (6 min)

The servings stepper (`src/recipe-card/render.php` + `view.js`): bound meta provides the **server-rendered initial HTML**, the store provides **derived client state** (`scaledQuantity` recomputes every ingredient as servings change).

This is where you explain the seam explicitly:

> **"Bindings own render time; the Interactivity API owns everything after — the meta field is the contract between them."**

That one line is the "orchestration" the title promises — and it's the thing neither API's docs say out loud.

## 5. The future (2 min)

One slide: the same extraction registered as an **Ability** (`ai-recipe-card/extract-recipe` in `includes/ai.php`), exposed via the **MCP Adapter**, so agents can use it too. Pre-recorded 20-second clip if you want the wow without the risk.

## 6. Takeaways (1 min)

1. **Declarative beats imperative for AI.**
2. **Schemas are contracts.**
3. **Bindings render, Interactivity reacts.**
