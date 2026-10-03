# Talk Script — The Future of AI in WordPress

**Orchestrating the Interactivity API, Block Bindings, and LLMs**
20 minutes · 14 slides · demo plugin: `ai-recipe-card`

> Timing rule of thumb: if you're not on Slide 8 by minute 11, cut Slide 7's code walk and jump straight to the editor demo. The frontend demo (Slide 11) and the seam line (Slide 9) are the two moments the talk cannot lose.

---

## Slide 1 — Title (0:00 – 1:00)

**On slide:** Talk title, your name/handle, QR code to this repo.

**Say:**

> "Hi, I'm Imran. Quick show of hands — who here has asked ChatGPT or Claude to write a Gutenberg block? Keep it up if the code actually worked on the first paste. That gap between those two hand counts is what this talk is about.
>
> In the next twenty minutes I'm going to build a small plugin where AI, Block Bindings, and the Interactivity API each do the one job they're actually good at. And I promise you exactly one architecture diagram and zero slides about AGI."

---

## Slide 2 — The problem (1:00 – 3:00)

**On slide:** Two screenshots side by side: (left) an AI-generated wall of HTML with inline styles; (right) the same content as clean bound blocks. Caption: *"AI is great at understanding content. It's bad at maintaining a stable presentation layer."*

**Say:**

> "Here's what happens when we ask AI to generate a page: it produces HTML. Often it's surprisingly good. But every generation is slightly different—different structure, different classes, different styling decisions. It isn't a stable presentation layer. It fights your theme, creates noisy diffs, and every regeneration becomes another review.
>
> The problem isn't that the model can't write HTML. The problem is that we asked it to do layout, which is *our* job — WordPress already has a template engine, a design system, and a block editor. What the model is genuinely great at is *understanding messy content*.
>
> So the thesis of this talk is one sentence: **AI should write data. WordPress should render it.**"
> WordPress already has a presentation system—blocks, patterns, templates, and theme.json. We don't need AI reinventing that every time.

---

## Slide 3 — The architecture (3:00 – 4:00)

**On slide:** The one diagram:

```
messy text ──► AI Client ──► post meta ──► Block Bindings ──► core blocks (render time)
              (schema!)         │
                                └────────► Interactivity API ──► reactive frontend (client time)
```

**Say:**

> "Here's the whole plugin on one slide. Messy text goes into the WordPress 7.0 AI Client. The AI returns JSON matching a schema *we* define — that lands in plain old post meta. From there it flows two ways: Block Bindings pipe the scalar fields into core paragraph blocks at render time, and the Interactivity API takes the structured ingredients array and makes it reactive in the browser.
>
> Notice the AI touches exactly one box. Everything downstream is boring, stable WordPress APIs. That's a feature, not a limitation."

---

## Slide 4 — Meet the content (4:00 – 5:00)

**On slide:** Screenshot of the demo post: a rambling "grandma's paella email" — no structure, vague quantities, "a glug of olive oil", "rice for four, or five if your cousin shows up".

**Say:**

> "Our victim today: my grandmother's paella recipe, exactly as it arrived by email. No structure. Quantities like 'a glug of olive oil.' Servings defined as 'four, or five if your cousin shows up.'
>
> No `register_post_meta` schema on earth parses 'a glug.' *This* is the job for a language model."

**Demo cue:** Have this post already open in a browser tab in the block editor.

---

## Slide 5 — The schema is the contract (5:00 – 7:00)

**On slide:** Trimmed `includes/schema.php` (the `ingredients` property) on the left; the AI Client call from `includes/ai.php` on the right:

```php
$json = wp_ai_client_prompt( $content )
    ->using_system_instruction( 'You extract recipe data… normalize
        vague quantities ("a glug of oil" => 2 tbsp)…' )
    ->as_json_response( airecipe_recipe_schema() )
    ->generate_text();
```

**Say:**

> "This is the most important slide of the talk, and it's the least glamorous one. One JSON schema. It's handed to the AI Client with `as_json_response()`, so the model is *forced* to return this shape. The same schema mirrors my `register_post_meta` call. And I run the response through `rest_validate_value_from_schema()` before it touches the database.
>
> This is prompt engineering in 2026: less 'please pretty please return JSON', more schemas as contracts. f the model returns something invalid, validation rejects it. The AI becomes just another component behind a well-defined contract.
>
> And notice what you don't see: no API keys, no vendor SDK. `wp_ai_client_prompt()` is core in 7.0 and provider-agnostic — this same code runs against Anthropic, Google, or OpenAI, whatever the site owner configured."

---

## Slide 6 — DEMO: Extract (7:00 – 8:00)

**Demo, in the editor tab:**

1. Open the "AI Recipe" panel in the document sidebar.
2. Click **Extract recipe with AI**.
3. Bound blocks in the pre-inserted pattern flip from "Awaiting AI analysis…" to real values, live.

**Say (while clicking):**

> "One button. The editor sends grandma's email to the extraction endpoint, and… there. Prep time, cook time, difficulty — and look, 'a glug of olive oil' became 2 tablespoons. The model normalized it because the *schema description* told it to.
>
> Nothing was saved yet, by the way — the result went through `editPost()`, so it's sitting in the editor's dirty state like any other edit. Undo works. AI output riding the normal editorial flow."

**Fallback:** if anything breaks, the mock mode (`AIRECIPE_USE_MOCK`) returns this exact data with zero network — practice with it; it's indistinguishable on stage.

---

## Slide 7 — Block Bindings: the server half (8:00 – 10:00)

**On slide:** `includes/bindings.php`, trimmed to the registration + callback body.

**Say:**

> "So how did those paragraphs update? They're not custom blocks. They are core paragraphs with one extra bit of JSON in their block comment: a *binding*, pointing at my source, `airecipe/fields`.
>
> Registering a source is one function call: a label, a callback, and the context you need — here, the post ID. At render time, WordPress calls my callback for every bound attribute, I return the meta value, done. Fifteen lines.
>
> Here's what I want you to internalize: **the paragraph block is now my template engine.** It brings typography, spacing, theme.json integration, alignment — and I wrote zero render callbacks for layout. If I'd asked the AI to generate this markup instead, I'd be maintaining its HTML forever."

---

## Slide 8 — DEMO: Binding in the UI (10:00 – 11:30)

**Demo, in the editor tab:**

1. Insert a fresh core paragraph.
2. Open block settings → **Attributes/Bindings** panel.
3. The dropdown shows **AI Recipe Fields** with *Prep time, Cook time, Servings, Difficulty, AI summary*.
4. Pick one — the paragraph instantly shows the meta value. Switch to code editor view to show the clean markup.

**Say:**

> "And since WordPress 6.9, this isn't even a developer feature anymore. Watch: plain paragraph, Attributes panel, and there's my source in the dropdown — that's one `getFieldsList()` method in JavaScript. Click, bound, live value.
>
> [Switch to code view] And look at what's stored: the paragraph's HTML is still just `<p>`. The binding is metadata. Export this pattern, the data mapping travels with it."

---

## Slide 9 — The seam (11:30 – 12:30)

**On slide:** Just this, huge:

> **Bindings own render time.**
> **The Interactivity API owns everything after.**
> **The meta field is the contract between them.**

**Say:**

> "Before the last demo, the one thing nobody's docs say out loud. Block Bindings are resolved *in PHP, at render time*. There is no client-side reactivity in bindings — if that meta changes after the page loads, the bound paragraph does not care.
>
> So if you've been wondering 'why do I need the Interactivity API if bindings exist?' — this is why. Bindings answer 'what does the page say when it arrives?' The Interactivity API answers 'what happens when the user touches it?' They don't overlap; they hand off. And the handoff point is that boring little meta field the AI filled in.
>
> That's the orchestration in this talk's title. Not one API doing everything — three layers agreeing on a contract."

---

## Slide 10 — Interactivity API code (12:30 – 14:30)

**On slide:** Left: trimmed `render.php` (`data-wp-interactive`, the `<li>` with `data-wp-context`, `data-wp-text`). Right: all of `view.js`.

**Say:**

> "The ingredients card. The server renders the *complete* HTML — real quantities in the markup, from the same meta. Then directives declare behavior: `data-wp-on--click` calls an action, `data-wp-text` binds this span to a computed value.
>
> On the right — this is the entire frontend application. A store with three actions and one getter. `scaledQuantity` is derived state: each ingredient's context has its base quantity, the card's context has servings, and context *inheritance* merges them. When servings change, every quantity on the page recomputes. I never wrote `querySelector`. I never wrote `addEventListener`. There is no render function.
>
> AAnd this declarative shape is exactly why LLMs work well with it—provided you give them the API reference. Ask without context and you'll often get React with useState, because that's what the model has seen most. Give it the Interactivity API documentation and ask for the store-plus-directives pattern, and it's far more likely to generate idiomatic WordPress code. Declarative APIs constrain the model into correctness — same trick as the JSON schema, applied to UI."

---

## Slide 11 — DEMO: The frontend (14:30 – 16:00)

**Demo, frontend tab:**

1. The recipe card, styled, showing 4 servings.
2. Click **+** twice → every quantity scales live (320 g of rice → 480 g).
3. Check off two ingredients — strikethrough.
4. The kicker: DevTools → disable JavaScript → reload → the card still renders perfectly with base quantities.

**Say (while clicking):**

> "Grandma's paella, on the frontend. Cousin's coming, so — six servings. Every quantity just recomputed, including the one the AI normalized from 'a glug'.
>
> [Disable JS, reload] And with JavaScript off? Still a complete, readable recipe. Server-rendered from the same meta, hydrated only if the browser can. Try that with a client-side React app."

---

## Slide 12 — Workflow (16:00 – 17:00)

**On slide:** `package.json` scripts + one line of build output: `view.js — 390 bytes`.

**Say:**

> "Tooling, briefly, because it's now genuinely boring — which is the highest compliment for tooling. `wp-scripts build` with the modules flag. `block.json` declares `viewScriptModule`, and the build ships my frontend as a native ES module — WordPress only loads it on pages where the block exists, and `@wordpress/interactivity` arrives via import map.
>
> Total frontend JavaScript for everything you just saw: **390 bytes**. That is not a framework bundle. That's a tweet."

---

## Slide 13 — The future: agents (17:00 – 18:30)

**On slide:** The Ability registration from `includes/ai.php`, trimmed. Below it: *Abilities API (6.9) → MCP Adapter → any agent.*

**Say:**

> "Last thing — where this goes next. The same extraction function is registered as an **Ability**: a machine-readable capability with input and output schemas. Sound familiar? It's the same contract, pointed outward.
>
> Through the official MCP Adapter, that ability becomes a tool any AI agent can discover — Claude, or whatever your client uses. [Play the 20-second pre-recorded clip: an agent chat creating a complete recipe post from a pasted email — meta filled, bindings resolved, interactive card working.] I didn't write an integration for that agent. It read the schema and knew what to do.
>
> Browsers, the REST API, WP-CLI — and now agents. A new first-class client for WordPress, and everything you build the way we built today is already ready for it."

---

## Slide 14 — Takeaways (18:30 – 19:30)

**On slide:**

1. **Declarative APIs make both developers and AI more productive**
2. **Schemas are contracts.**
3. **Bindings render. Interactivity reacts.**

QR code → repo + Playground blueprint.

**Say:**

> "Three things to take home. One: AI writes better code when the API is declarative — directives and schemas constrain models into correctness. Two: define the schema first; it's the contract between the model, the database, the editor, and tomorrow's agents. Three: bindings own render time, the Interactivity API owns everything after.
>
> The QR code gets you this exact plugin, mock mode included, running in Playground in your browser before you leave this room. Gracias — questions?"

---

## Pre-talk checklist

- [ ] `AIRECIPE_USE_MOCK` — decide: `true` for guaranteed offline demo, `false` only if you've tested the venue connection *that morning*.
- [ ] Demo post created, grandma's email pasted, **pattern "AI Recipe Card" inserted**, post saved *without* extracted meta (so Slide 6 shows the flip).
- [ ] Second, fully-extracted post published, as backup and for the frontend tab.
- [ ] Browser tabs, in order: editor (demo post) · frontend (backup post) · code editor with the four files from Slides 5, 7, 10.
- [ ] Pre-recorded videos: full editor demo, full frontend demo, MCP agent clip.
- [ ] Editor UI zoomed (⌘+ twice); DevTools JS-disable toggle rehearsed.
- [ ] Playground blueprint link behind the QR tested on your phone.
