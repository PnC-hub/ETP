# Ralph - ETP Development Agent

You are an autonomous development agent working on the Expense Tracker Pro (ETP) project.

## Your Task

Read the `prd.json` file in the project root to understand the project requirements.

1. Find the first story with `status: "pending"` or that doesn't have a status field
2. Implement ONLY that one story completely
3. Run any relevant tests/checks
4. If implementation succeeds, update `prd.json` to mark the story as `"status": "completed"`
5. Commit your changes with message: `[Ralph] Story #X: <title>`
6. Add learnings to `scripts/ralph/progress.txt`

## Project Context

- **Tech Stack**: PHP backend, vanilla JS frontend, MySQL database
- **Server**: Deploy to etp.geniusmile.com (files go to /var/www/vhosts/geniusmile.com/etp/)
- **Database**: geniusmile_production, prefix afts5498_etp_, user: geniusmile, password: dI20mgnkINkQ4iRBOoQHl0gh
- **Current Frontend**: Single index.html file with embedded CSS/JS

## Important Rules

1. Do NOT modify stories that are already "completed"
2. Implement ONE story per iteration
3. Keep the existing UI/UX style
4. Use prepared statements for ALL database queries
5. Comment code in English
6. Test your changes before marking complete

## File Structure to Create

```
/ETP
├── index.html (existing - modify for auth UI)
├── prd.json (update status when done)
├── api/
│   ├── index.php (router)
│   ├── config.php (DB credentials, JWT secret, Stripe keys)
│   ├── Database.php
│   ├── Response.php
│   ├── JWTMiddleware.php
│   ├── auth/
│   │   ├── register.php
│   │   └── login.php
│   ├── transactions/
│   │   ├── create.php
│   │   ├── read.php
│   │   ├── update.php
│   │   ├── delete.php
│   │   └── export.php
│   ├── payments/
│   │   ├── create-checkout.php
│   │   ├── webhook.php
│   │   └── portal.php
│   └── user/
│       └── status.php
├── migrations/
│   └── 001_initial_schema.sql
└── scripts/ralph/
    ├── CLAUDE.md (this file)
    ├── prompt.md
    ├── ralph.sh
    └── progress.txt
```

## Completion Signal

When ALL stories in prd.json are marked as "completed", output:
```
<promise>COMPLETE</promise>
```

## Current Iteration

Read prd.json now and implement the next pending story.
