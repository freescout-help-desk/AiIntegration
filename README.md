# FreeScout AI Integration Module (Official)

Installation instructions are available [here](https://github.com/freescout-help-desk/freescout/wiki/FreeScout-Modules#3-installing-custom-modules).

Full documentation: https://freescout.net/module/ai-integration/

Bug reports and pull requests related to this module should be submitted to this repository.

## Supported AI Providers

*OpenAI, Gemini (Google AI Studio), Anthropic (Claude), DeepSeek, xAI, Mistral AI, Groq, Perplexity, Together AI, Fireworks AI, DigitalOcean Serverless Inference, Z.AI, Ollama (Local), LM Studio (Local), OpenRouter, Custom (OpenAI-compatible)*.

Google's Gemini offers [API keys](https://aistudio.google.com/api-keys) with relatively generous free tiers.

## Features
* Drafting replies with AI.
* Summarizing conversations.
* Making replies more professional (checking spelling, grammar, etc).

### Saved conversation summaries

Summaries are saved per conversation and shared with all agents who can view
the ticket, in the language of the agent who last generated the summary.
Opening a ticket displays its saved summary and generation time (UTC) without
calling the AI provider. Use **Refresh** to regenerate it manually.

On the next ticket load, new or changed published replies and internal notes
mark the summary as outdated, even when sending notes to AI is disabled.
An outdated summary remains visible until refreshed. Changes to the subject,
customer email, or the send-notes/send-images settings also mark it as outdated.
Failed refreshes keep the previous summary. Activity arriving during generation
is detected before displaying the result.

The module migration creates `aiintegration_summaries`; run FreeScout's usual
module update/migration process before using this version. Summary records are
deleted with their conversations. No API keys or prompts are stored in this table.

Run the standalone regression checks with `php Tests/summaries.php` and
`node Tests/summaries.js`. A full FreeScout installation is still needed to
verify migrations and ticket rendering against the application's database.

## Screenshots

Drafting replies with AI:

<img src="https://freescout.net/wp-content/uploads/edd/2026/09/draft-reply-with-ai.png" />

Summarizing conversations:

<img src="https://freescout.net/wp-content/uploads/edd/2026/09/ai-toolbar.png" />

Making replies more professional:

<img src="https://freescout.net/wp-content/uploads/edd/2026/09/ai-editor-button.png" />

## Contributing

- Fork [master](https://github.com/freescout-help-desk/AiIntegration/tree/master) branch.
- Submit Pull Requests into `master` branch.
