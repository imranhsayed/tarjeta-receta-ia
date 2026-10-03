# Talk Script — The Future of AI in WordPress (Restaurant Edition)

**Orchestrating the Interactivity API, Block Bindings, and LLMs**
20 minutes · 14 slides · Demo plugin: `ai-recipe-card`

> Timing Rule of Thumb: If you are not on Slide 8 by minute 11, cut Slide 7's code walk and jump straight to the editor demo. The frontend demo (Slide 11) and the "seam" line (Slide 9) are the two moments the talk absolutely cannot lose.

---

## Slide 1 — Title (0:00 – 1:00)

**On slide:** Talk title, your name/handle, QR code to the GitHub repository.

**Say:**

> "Hi, I'm Imran. Quick show of hands—who here has asked Claude or ChatGPT to write a Gutenberg block? Keep it up if the code actually worked on the first paste. That massive gap between those two hand counts is exactly what this talk is about.
>
> In the next twenty minutes, I'm going to show you how to build a plugin where AI, Block Bindings, and the Interactivity API each do the one job they're actually good at. To do that, we aren't going to look at complex cloud architecture. We're going to look at how to run a small restaurant."

---

## Slide 2 — The Problem (1:00 – 3:00)

**On slide:** Two screenshots side by side: (left) an AI-generated wall of HTML with inline styles; (right) the same content as clean bound blocks. Caption: *"AI is great at understanding content. It's bad at maintaining a stable presentation layer."*

**Say:**

> "Imagine hiring a brilliant kitchen assistant for your new restaurant, and then asking him to also design the menus, paint the walls, and choose the furniture. He'll produce something—but it's going to clash with your décor, it will look completely different every single night, and your interior designer won't be able to review it.
>
> That is exactly what happens when we ask AI to generate HTML. It fights your theme, no two generations ever match, and it leaves you with a catastrophic wall of 'div-soup' that nobody can safely maintain or review.
>
> The fix isn't finding a smarter assistant. The fix is a proper **job description**: his only job is reading the messy letters that arrive and turning them into clean, structured order forms. The dining room already exists; professionals already designed it. In WordPress terms: **AI should write data. WordPress should render it.**"

---

## Slide 3 — The Architecture (3:00 – 4:00)

**On slide:** The architecture diagram mapped directly to a restaurant floor plan:

```
[ Grandma's Letter ] ──► [ Kitchen Assistant ] ──► [ Filing Cabinet ]
                               (AI Client)              (Post Meta)
                                                             │
                                      ┌──────────────────────┴──────────────────────┐
                                      ▼                                             ▼
                             [ The Menu Printer ]                               [ The Waiter ]
                         Block Bindings (Render Time)               Interactivity API (Client Time)
```

**Say:**

> "Every piece of technology in this architecture maps onto an everyday restaurant story. Every API is a single worker in that restaurant—and here is the secret: the workers never meet each other. They just pass data forward.
>
> A messy handwritten letter arrives at the kitchen door. Our clever kitchen assistant reads it, extracts the core info, and puts it into a central filing cabinet. From that cabinet, there are two separate doors out: one goes to the print shop to print the physical menus, and the other goes straight to the waiter standing in the dining room. Let's look at how WordPress handles this workflow."

---

## Slide 4 — Meet the Content (4:00 – 5:00)

**On slide:** Screenshot of the demo post: a rambling "grandma's paella email". Vague text highlighted: "a glug of olive oil," and "rice for four, or five if your cousin shows up."

**Say:**

> "Our raw content is a rambling handwritten letter from Grandma about how to cook paella. It's charming, but completely unusable for a database. No database filing system on earth accepts 'a glug' as a valid data type.
>
> But reading messy, unstructured human intent is the one job our kitchen assistant—the LLM—is genuinely world-class at executing."

**Demo cue:** Have this post already open in a browser tab in the block editor.

---

## Slide 5 — The Schema is the Contract (5:00 – 7:00)

**On slide:** Trimmed `includes/schema.php` (the fields) on the left; the AI Client call from `includes/ai.php` on the right.

```php
$json = wp_ai_client_prompt( $content )
    ->using_system_instruction( 'You extract recipe data… normalize vague quantities…' )
    ->as_json_response( airecipe_recipe_schema() )
    ->generate_text();
```

**Say:**

> "To keep our kitchen assistant honest, we don't just hand him the letter and say, 'please summarize this nicely.' We hand him a pre-printed form with fixed, rigid boxes: a prep time box, a servings box, and three strict columns per ingredient.
>
> He can be as creative as he wants reading Grandma's letter, but he is strictly forced to write his answers inside our boxes. There are no margins to scribble in. If he tries to hand over a messy form, a supervisor at the kitchen door instantly rejects it.
>
> This is prompt engineering in 2026: it's not about pleading with a model, it's about constraining it using JSON schemas as contracts. This is how 'a glug' reliably becomes '2 tbsp'—the form forces a normalized number and unit into the database."

---

## Slide 6 — DEMO: Extract (7:00 – 8:00)

**Demo, in the editor tab:**

1. Open the "AI Recipe" panel in the document sidebar.
2. Click **Extract recipe with AI**.
3. Bound blocks flip from "Awaiting AI analysis..." to real values live on screen.

**Say (while clicking):**

> "When I click 'Extract', we hand the letter to the kitchen assistant. Seconds later, a perfectly filled form drops right into our post meta filing cabinet.
>
> Notice one crucial detail: this form isn't permanently glued into the cabinet yet—it's just paper-clipped. Because it uses the normal `editPost()` flow, it's sitting in the editor's temporary state. The user can hit undo, and nothing hits the database until they click Save. The AI isn't a wizard wielding a magic wand; it's just a kitchen assistant filling out a card."

---

## Slide 7 — Block Bindings: The Menu Printer (8:00 – 10:00)

**On slide:** `includes/bindings.php`, registered source and callback.

**Say:**

> "Now we've extracted structured data—but how does it actually appear on the page?

This is where Block Bindings come in.

Think of them as the placeholders inside our restaurant's menu template.

We already have a beautiful menu designed with standard WordPress blocks. We don't build a custom menu from scratch.

Instead, we simply tell each field where its data should come from. This title comes from this drawer in the filing cabinet. This prep time comes from another. This ingredient list comes from another.

Every time WordPress renders the page, it pulls the latest values from the filing cabinet and drops them into those placeholders automatically.

The design stays exactly the same. Only the data changes.

That's the power of Block Bindings—we keep using familiar core blocks while making them display dynamic content."

---

## Slide 8 — DEMO: Binding in the UI (10:00 – 11:30)

**Demo, in the editor tab:**

1. Insert a fresh core paragraph block.
2. Open block settings → **Attributes/Bindings** panel.
3. Select **AI Recipe Fields** and pick a field (e.g., Prep Time). Show the clean code view.

**Say:**

> "Because this is native WordPress, the menu printer is built right into the UI. I insert a standard paragraph, link it to our filing cabinet via the attributes panel, and it populates instantly. Looking at the code view, the HTML remains a clean `<p>` tag. The layout belongs entirely to WordPress; the data belongs to the cabinet."

---

## Slide 9 — The Seam (The Money Slide) (11:30 – 12:30)

**On slide:** Placed in massive text:

> **"Block Bindings fill in the placeholders while WordPress renders the page. Once the page reaches the browser, their job is done. The Interactivity API takes over from there. They never talk to each other directly—they simply trust the same filing cabinet."**

**Say:**

"This is the one slide I want you to remember after this talk.

So far, we've followed Grandma's recipe through the kitchen assistant, into the filing cabinet, and finally into our menu template using Block Bindings.

But here's the important boundary.

Block Bindings only exist while WordPress is rendering the page on the server.

Their job is to take the latest values from the filing cabinet and fill in the placeholders in our menu template before the HTML is sent to the browser.

Once that HTML reaches the browser, Block Bindings are finished.

That's the seam in the architecture.

From that point on, if anything needs to change because a user clicks a button or updates a value, something else has to take over.

And that's exactly where the Interactivity API comes in."

---

## Slide 10 — The Interactivity API: The Waiter (12:30 – 14:30)

**On slide:** Left: `render.php` directives (`data-wp-interactive`, `data-wp-context`). Right: `view.js` store with the `scaledQuantity` getter.

**Say:**

> "Now imagine a customer sitting down and saying,

'Actually, our cousin just arrived. We're six people now, not four.'

At this point, the menu has already been served. Block Bindings have done their job.

This is where the waiter takes over.

The waiter doesn't go back to the kitchen to create a brand-new menu. He simply updates what the customer sees at the table.

In our plugin, that's exactly what the Interactivity API does.

The server already rendered the complete recipe card using the data from the filing cabinet.

Then the Interactivity API adds behavior in the browser.

When the serving size changes, it recalculates every ingredient quantity instantly, without reloading the page.

The interesting part is that it doesn't store every possible answer. It stores the rule:

Scale the quantities based on the number of servings.

That's what we call derived state.

The UI reacts by applying the rule to the existing data."

> **The Bug Story**
>
> When I first built this, the printed menus had a literal blank space where the quantities belonged. The template assumed, 'Oh, the waiter will write that in when he walks over.' But if a customer looked at the menu before the waiter arrived—like a search engine bot, or a browser with JavaScript disabled—they saw absolutely nothing.
>
> The fix was simple: give the print shop the exact same arithmetic rule the waiter uses. We wrote the calculation twice: once for the kitchen side in PHP (`render.php`), and once for the table side in JavaScript (`view.js`). Two workers, one shared rule, one filing cabinet. Now the menu prints perfectly on the server, and the waiter only tweaks it if the party grows."

---

## Slide 11 — DEMO: The Frontend (14:30 – 16:00)

**Demo, frontend tab:**

1. Show the beautiful recipe card component at 4 servings.
2. Click the **+** button to scale up to 6 servings; watch quantities multiply instantly.
3. Open DevTools → Disable JavaScript → Reload page. Show the card still fully rendered.

**Say (while clicking):**

> "Watch the waiter at work. The cousin arrives, we click plus, and every quantity scales instantly—including the '2 tablespoons' that our kitchen assistant successfully normalized from Grandma's email.
>
> Now, let's kill JavaScript entirely and reload the page. The recipe card is still completely readable and correct. Why? Because the menu printer still did its job flawlessly on the server. The waiter is a luxury; the restaurant still functions entirely when he calls in sick. Try pulling that off with a pure client-side React application."

---

## Slide 12 — The 390 Bytes (16:00 – 17:00)

**On slide:** `package.json` build scripts alongside build output emphasizing: `view.js — 390 bytes`.

**Say:**

> "Because of this declarative layout, our waiter's entire training manual fits onto one single index card—three quick actions and a single mathematical rule. He doesn't need to haul a massive multi-volume encyclopedia of hospitality—a massive framework bundle—to your table just to serve a meal.
>
> Our entire frontend compiled file is just 390 bytes. That isn't a framework footprint; that is a single tweet."

---

## Slide 13 — The Future: Delivery Apps & Agents (17:00 – 18:30)

**On slide:** Ability registration code block. Highlight: *Abilities API → MCP Adapter → External AI Agents.*

**Say:**

> "Right now, only our internal staff can press the 'Extract' button in our editor. But what happens when we want to expand?
>
> By registering this workflow as an Ability, we are essentially publishing our restaurant's menu onto standardized delivery apps like DoorDash or UberEats. We publish a machine-readable listing: 'This kitchen accepts messy letters, and this kitchen returns structured recipe cards.'
>
> Thanks to the Model Context Protocol (MCP) adapter, any external AI agent can discover your site's capabilities and interact with it seamlessly. And look at what's executing the heavy lifting: it's the exact same pre-printed form from Slide 5. One contract, three distinct consumers: the local model, the human editor, and external automated agents."

---

## Slide 14 — Takeaways (18:30 – 19:30)

**On slide:**

1. **Declarative beats imperative for AI** (Give workers rules and labels, not step-by-step choreography).
2. **Schemas are contracts** (The form keeps the kitchen assistant, printer, waiter, and agents honest).
3. **Bindings render, Interactivity reacts** (The printer and waiter never meet; they just trust the filing cabinet).

QR Code pointing to the working Playground blueprint and repository.

**Say:**

> "If you walk out of this room remembering only one core analogy from this stage, let it be the seam: the printed menu versus the waiter, both mutually trusting the central filing cabinet.
>
> It explains elegantly why both WordPress APIs exist, how they cooperate, and how they cleanly isolate your AI data from your layout.
>
> Scan the QR code to load this entire restaurant configuration inside a browser-based WordPress Playground right now. ¡Gracias! What questions do you have?"
