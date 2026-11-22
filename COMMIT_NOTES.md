# Git / GitHub notes — Laboratory Exercise 6

A local repository has been initialized in this folder and the Lab 6 work is committed.

```bash
git status
git log --oneline -1
# ed59ce0 Lab 6: real-time inventory tracking with observer, notifications, API and reports
```

## Safety check

- `.env` is **not** committed. It is listed in `.gitignore`.
- Only `.env.example` (no secrets) is tracked.
- `vendor/`, `node_modules/`, compiled caches and `.env.backup` are ignored.
- `screenshots/`, `README.md`, `LAB_REPORT.md` and the generated PDF are tracked so the
  report artifacts are included.

## Push to GitHub

There is no remote configured because the repository URL was not supplied. To publish:

```bash
# Option A - HTTPS
git remote add origin https://github.com/<your-username>/<your-repository>.git
git branch -M main
git push -u origin main

# Option B - SSH
git remote add origin git@github.com:<your-username>/<your-repository>.git
git push -u origin main
```

If GitHub asks for authentication, use a Personal Access Token with `repo` scope for HTTPS,
or configure an SSH key for Option B.

## Before submitting the PDF

Rename the generated report to the required filename:

```bash
Rename-Item Lab6_2411600148_YourLastName.pdf Lab6_2411600148_<YourLastName>.pdf
```

The PDF is generated from `LAB_REPORT.md` and embeds the browser screenshots in
`screenshots/`. If you want to include VS Code, phpMyAdmin or GitHub-page captures, add them
to `screenshots/`, reference them in `LAB_REPORT.md`, and regenerate the PDF with Edge:

```powershell
$edge='C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$html=(Resolve-Path 'storage\app\public\lab6-report.html').Path -replace '\\','/'
$pdf=(Join-Path (Get-Location) 'Lab6_2411600148_YourLastName.pdf')
& $edge --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf="$pdf" "file:///$html"
```
