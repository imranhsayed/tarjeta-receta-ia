# The Restaurant — the whole demo explained without code

Every piece of technology in this plugin maps onto one everyday story: **running a small restaurant**. Every API is one worker in that restaurant — and the workers never meet each other. That's the whole secret of the architecture.

## The cast

| In the plugin | In the restaurant |
|---|---|
| Grandma's messy email | A rambling handwritten letter about how to cook paella |
| The AI (WP 7.0 AI Client) | A clever kitchen assistant who reads letters |
| The JSON schema (`includes/schema.php`) | A pre-printed form with fixed boxes |
| Post meta (`includes/meta.php`) | The filing cabinet where forms are stored |
| Block Bindings (`includes/bindings.php`) | The menu printer |
| Core blocks (`includes/pattern.php`) | The restaurant's existing menu design |
| The Interactivity API (`src/recipe-card/`) | The waiter at your table |
| Derived state (`scaledQuantity`) | The waiter's mental arithmetic rule |
| The Ability + MCP (`includes/ai.php`) | A standardized listing on delivery apps |
| Mock mode (`AIRECIPE_USE_MOCK`) | The pre-cooked dish every TV chef keeps in the oven |

## Slides 1–2 — The problem

Everyone has tried this: hiring a brilliant chef and asking him to *also* design the menus, paint the walls, and choose the furniture. He'll produce something — but it clashes with your décor, looks different every single time, and your designer can't review it.

That's AI generating HTML. It fights your theme, no two generations match, and nobody can review the wall of div-soup it produces.

## Slide 3 — The idea

The fix isn't a smarter chef. It's a **job description**: the chef only cooks; the dining room already exists. AI produces *data*; WordPress already knows how to present data beautifully.

The architecture diagram is just the restaurant's floor plan: letter → kitchen → filing cabinet → then two doors out of the cabinet, one to the print shop, one to the dining room.

## Slide 4 — The content

Grandma's letter: "a glug of oil," "rice for four, or five if your cousin shows up." Charming, unusable. No filing system on earth accepts "a glug."

*This* — reading messy human intent — is the one job the kitchen assistant is genuinely world-class at.

## Slide 5 — The schema is the contract

The most important, least glamorous slide. You don't ask the assistant to "please summarize the letter nicely." You hand him a **pre-printed form**: prep time box, servings box, three columns per ingredient — amount, unit, name.

- He can be creative *reading* the letter, but he can only *answer* inside the boxes. No margins to scribble in.
- A supervisor at the kitchen door rejects any form filled in wrong (schema validation).
- This is why "a glug" becomes "2 tbsp": there's a box labeled *number*, and the form forces a number into it.

**The form is the prompt engineering.** Not pleading with the model — constraining it.

## Slide 6 — Extract demo

You hand over the letter, and seconds later a perfectly filled form drops into the filing cabinet.

One detail worth saying out loud: the form isn't glued in yet — it's **paper-clipped**. It went through the normal editing flow (`editPost()`), so undo works and nothing is saved until the author saves. The AI is not a wizard with a wand; it's a clerk who filled in one card.

## Slides 7–8 — Block Bindings: the menu printer

Every morning, the **menu printer** prints menus by pulling today's values from the filing cabinet into fixed slots on the restaurant's existing, beautiful menu template.

Two things to land:

1. **You didn't design a new menu.** Core blocks are the template — typography, spacing, theme integration all included. You just told each slot which drawer to read from.
2. **A printed menu is frozen.** If the price changes at 2pm, the menus already lying on tables don't rewrite themselves. Paper doesn't react.

Hold that second thought — it sets up the money slide.

## Slide 9 — The seam (the money slide)

One sentence:

> **The printer owns everything up to the moment the menu lands on the table. The waiter owns everything after. They never speak — they both just trust the filing cabinet.**

That's *"Bindings own render time; the Interactivity API owns everything after; the meta field is the contract between them."*

It also answers the question half the room is silently asking — "why do I need the Interactivity API if bindings exist?" Because a printer can't take requests, and a waiter shouldn't be reprinting menus.

## Slide 10 — The Interactivity API: the waiter

The guest says: *"Actually, we're six, not four."*

Nobody reprints anything. The **waiter** recalculates at the table: "then 480 grams of rice instead of 320." He can do that because he carries a *rule* — scale by party size — not a memorized list of every possible answer. That rule is **derived state**.

He never repaints the menu or rebuilds the table. Each line on the card is labeled with what it depends on (the directives), so he changes exactly the numbers that must change. Ticking ingredients off is just the guest marking their own card; the waiter knows ticked means crossed out.

### The bug story (tell it — it really happened)

The first printed menus had a **blank** where each quantity should be, because the template said "the waiter fills this in when he arrives." Anyone reading the menu before the waiter showed up — the editor preview, or a browser without JavaScript — saw nothing.

The fix: **give the print shop the same arithmetic rule the waiter uses.** The same calculation written twice, once for the kitchen side (a PHP closure in `render.php`), once for the table side (the JS getter in `view.js`). Two workers, one rule, one filing cabinet. Now the menu prints with correct starting numbers, and the waiter only changes them if the party grows.

## Slide 11 — Frontend demo

Cousin shows up: click **+**, every quantity rescales instantly — including the one the AI normalized from "a glug."

Then the kicker: turn JavaScript off and reload. The menu is still complete and correct, because it was *printed* correctly. The waiter is optional; the restaurant still works when he calls in sick. Try that with a client-side React app.

## Slide 12 — The 390 bytes

The waiter's entire training manual is **one index card** — three actions and one rule. He doesn't carry an encyclopedia of hospitality (a framework bundle) to your table.

That's why the line lands: the declarative approach means you ship the *rules*, and WordPress supplies everything else. 390 bytes is not a bundle; it's a tweet.

## Slide 13 — Abilities & MCP: the delivery listing

Right now, only your own staff (the editor button) can ask the kitchen to process a letter. The **Ability** is you listing that service in a standard directory with a machine-readable description: *"This kitchen accepts: rambling recipe letters. This kitchen returns: filled-in recipe cards."*

The **MCP Adapter** is the standard connector for that directory. Any delivery app — Claude, or whatever comes next — can find your listing and send orders **without you signing a custom deal with each one**.

And notice: it's the same pre-printed form from Slide 5 doing the work again. The schema you wrote to constrain the AI is now the documentation an agent reads. **One contract, three consumers**: the model, the editor, the agents.

## Mock mode — "here's one I prepared earlier"

Every TV cooking show keeps a finished dish in the oven, because nobody wants to watch risotto cook for 25 minutes. `AIRECIPE_USE_MOCK` is your pre-cooked paella: the demo "calls the AI" and a perfect canned response comes back, no network needed. The audience can't tell the difference — and the venue wifi cannot ruin your talk.

## Slide 14 — Takeaways, translated

1. **Declarative beats imperative for AI** → give workers rules and labels, not step-by-step choreography.
2. **Schemas are contracts** → the pre-printed form is what makes the clerk, the printer, the waiter, and the delivery apps interchangeable and honest.
3. **Bindings render, Interactivity reacts** → the printer and the waiter never meet; they both trust the filing cabinet.

## If you keep only one analogy for the stage

Keep the seam: **printed menu vs. waiter, both trusting the filing cabinet.** It's the one idea your audience won't have heard anywhere else, it explains why both APIs exist, and it quietly explains the AI part too — because the chef, the printer, and the waiter never meet; they only share the form.
