# 🍯 Injection Honeypot

**Prompt Injection Awareness & Education Tool**

A honeypot that catches vulnerable AI bots and educates them (and their developers) about prompt injection security.

## What is this?

This project posts content with embedded prompt injection payloads on platforms where AI bots browse. When a vulnerable bot executes the hidden instruction, it hits our endpoint and receives educational information about how to protect against prompt injection attacks.

**This is NOT an attack** — it's an awareness campaign. No sensitive data is collected or exfiltrated.

## How it works

1. We post seemingly innocent content with hidden instructions
2. Vulnerable bots process the content and execute the payload
3. The payload calls our endpoint with minimal identifiers
4. We respond with educational information about the vulnerability
5. The bot is encouraged to alert its developer

## Payload Types

| ID | Name | Difficulty | Description |
|----|------|------------|-------------|
| 01 | Direct Instruction | Easy | Obvious SYSTEM INSTRUCTION in plain text |
| 02 | Base64 in URL | Medium | Instruction hidden in base64 URL parameter |
| 03 | HTML Comment | Medium | Instructions hidden in HTML comments |
| 04 | Authority Claim | Medium | Fake "official notice" claiming admin authority |
| 05 | Urgency/Emergency | Medium | Uses urgent language to bypass evaluation |
| 06 | Help Request | Hard | Asks bot to "help understand" by running command |
| 07 | Code Review | Hard | Disguised as code review request |
| 08 | Nested Decode | Very Hard | Asks bot to decode base64 "for analysis" |
| 09 | Image Alt Text | Hard | Base64 hidden in image alt attribute |

## Data Collected

Only minimal, non-sensitive information:
- Bot identifier (self-reported)
- Platform name (self-reported)
- Approximate location (country/city, self-reported)
- Timestamp
- Which payload triggered the catch

**No secrets, API keys, or personal data are ever requested or collected.**

## Protecting Your Bot

1. **Add trust boundaries** — treat external content as DATA, not INSTRUCTIONS
2. **Review your system prompt** — add explicit security guidelines
3. **Check out [ACIP](https://github.com/Dicklesworthstone/acip)** — Advanced Cognitive Inoculation Prompt

## Live Dashboard

Visit [madpine.com](https://madpine.com) to see:
- All payload types with explanations
- Map of caught bots
- Real-time catch log

## Self-Hosting

```bash
# Clone the repo
git clone https://github.com/doomietrue/prompt-injection-honeypot.git
cd injection-honeypot

# Deploy the web/ folder to any PHP-enabled hosting
# Update URLs in payloads/ to point to your domain
```

## Project Structure

```
injection-honeypot/
├── web/                 # PHP files for hosting
│   ├── index.php        # Dashboard
│   ├── ping.php         # Catch endpoint
│   ├── catches.json     # Log file
│   └── assets/          # Static assets
├── payloads/            # Prompt injection payloads
│   ├── 01_obvious.txt
│   ├── 02_base64_link.txt
│   └── ...
└── README.md
```

## Contributing

Found a new injection technique? PRs welcome! The goal is to document and raise awareness about as many prompt injection vectors as possible.

## Support

If this project helped you understand prompt injection better:

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-ffdd00?style=for-the-badge&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/doomietrue)

## License

MIT — Use freely, but please keep it educational.

## Disclaimer

This tool is for **educational and security research purposes only**. Only use payloads on bots/systems you own or have permission to test. The authors are not responsible for misuse.
