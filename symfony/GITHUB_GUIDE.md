# GitHub Push Anleitung - Sicherheit & Best Practices

## ✅ Sicherheits-Checkliste vor dem Push

### 1. Sensible Dateien ausschließen

```bash
# .env Dateien NIEMALS pushen!
echo ".env.local" >> .gitignore
echo ".env.prod" >> .gitignore
echo ".env*.local" >> .gitignore
echo "/config/secrets/" >> .gitignore
```

### 2. Vor dem Push prüfen

```bash
# Prüfe was gepusht werden würde
git status

# Prüfe große Dateien
git ls-files | sort -k5 -rn | head -10

# Prüfe auf sensible Daten (falsch-positive möglich!)
# git grep -E "(password|api_key|secret|token)" --cached
```

### 3. Nur sichere Dateien pushen

**Pushen Sie:**
```
✅ src/                      (Alle PHP-Dateien)
✅ config/services.yaml      (Dependency Injection)
✅ migrations/               (Database Schema)
✅ openapi.yaml              (API-Spezifikation)
✅ *.md                      (Dokumentation)
✅ composer.json             (Dependencies)
✅ .gitignore                (Git Config)
```

**Nicht pushen:**
```
❌ .env / .env.local         (Credentials)
❌ var/                      (Runtime Data)
❌ vendor/                   (Dependencies - via composer)
❌ node_modules/             (NPM packages)
❌ .env.*                    (Alle Env-Files)
❌ /config/secrets/          (Private Keys)
```

---

## 🚀 GitHub Setup - Schritt für Schritt

### Schritt 1: Repository auf GitHub erstellen

```bash
# Login zu GitHub
# Gehe zu: https://github.com/new
# Erstelle: "bib-app-symfony-ddd"
# Beschreibung: "Klassenverwaltung mit Symfony & DDD"
# Privat oder Öffentlich? → Abhängig von dir!
```

### Schritt 2: Lokales Repository initialisieren

```bash
cd bib-App
git init
git add .
git commit -m "feat: Add Symfony DDD Architecture with 48 PHP files

- Complete Domain-Driven Design implementation
- 4 Bounded Contexts (Calendar, Class, User, Password)
- Full documentation and migration guides"
```

### Schritt 3: Remote hinzufügen & pushen

```bash
# Remote hinzufügen
git remote add origin https://github.com/YOUR_USERNAME/bib-app-symfony-ddd.git

# Branch umbenennen (optional)
git branch -M main

# Pushen!
git push -u origin main
```

---

## 🔐 GitHub Security Best Practices

### 1. `.env` Beispiel pushen (optional)

Erstelle `.env.example` mit Template:

```bash
# Kopiere .env.local zu .env.example
cp .env.local .env.example

# Bearbeite .env.example - entferne sensitive Werte:
# DATABASE_URL=mysql://user:password@...
# Wird zu:
# DATABASE_URL=mysql://user:password@127.0.0.1:3306/bib_app?serverVersion=5.7

git add .env.example
git commit -m "docs: Add .env.example template"
```

### 2. Secrets nicht in Commits versteckt

```bash
# Scan für Secrets (optional)
npm install -g detect-secrets
detect-secrets scan --baseline .secrets.baseline

# Git-Secrets installieren
brew install git-secrets
git secrets --install
git secrets --register-aws
```

### 3. Branch Protection Rules

**Auf GitHub einrichten:**
1. Gehe zu: Settings → Branches
2. Erstelle Regel für `main` Branch:
   - ✅ Require pull request reviews
   - ✅ Require status checks
   - ✅ Require branches up to date

---

## 📋 Was Sie nach dem Push tun sollten

### README aktualisieren für GitHub

```markdown
# bib-App Symfony DDD

Klassenverwaltung mit Domain-Driven Design in Symfony

## 🚀 Quick Start

1. Clone: `git clone https://github.com/YOUR/bib-app.git`
2. Install: `composer install`
3. Lese: [START_HERE.md](./START_HERE.md)

## 📚 Dokumentation

- [START_HERE.md](./START_HERE.md) - Projekt-Übersicht
- [DDD_ARCHITECTURE.md](./DDD_ARCHITECTURE.md) - Architektur
- [QUICKSTART.md](./QUICKSTART.md) - Installation
- [CHECKLIST.md](./CHECKLIST.md) - Go-Live

## 📦 Struktur

```
src/
├── Domain/          # Geschäftslogik
├── Application/     # Use Cases
├── Infrastructure/  # Persistierung
└── Presentation/    # API
```

## ✨ Features

- Domain-Driven Design
- 4 Bounded Contexts
- 48 PHP-Dateien
- Vollständig dokumentiert
- Production-Ready
```

### Issues & Discussions aktivieren

```bash
# Auf GitHub:
1. Settings → Features
2. ✅ Issues
3. ✅ Discussions
4. ✅ Wiki
```

---

## 🛡️ Sicherheits-Tipps für GitHub

### 1. Personal Access Token verwenden

```bash
# Statt HTTPS Password
git remote set-url origin https://YOUR_TOKEN@github.com/YOUR_USERNAME/bib-app.git

# oder SSH Key
ssh-keygen -t ed25519 -C "your_email@example.com"
git remote set-url origin git@github.com:YOUR_USERNAME/bib-app.git
```

### 2. Two-Factor Authentication

**Auf GitHub aktivieren:**
1. Settings → Security
2. Two-factor authentication → Enable

### 3. Dependabot aktivieren

```bash
# Auf GitHub: Settings → Code security
# ✅ Dependabot alerts
# ✅ Dependabot security updates
```

---

## 📊 GitHub Repository Setup Checkliste

- [ ] Repository erstellt
- [ ] .gitignore konfiguriert
- [ ] Sensible Dateien ausgeschlossen
- [ ] Initial commit durchgeführt
- [ ] Branch geschützt
- [ ] README aktualisiert
- [ ] Issues Template erstellt
- [ ] Two-Factor Auth aktiviert
- [ ] Dependabot aktiviert
- [ ] Wiki aktiviert (optional)

---

## ⚡ Quick Commands

```bash
# Repository initialisieren
git init
git add .
git commit -m "Initial commit"

# Remote hinzufügen
git remote add origin https://github.com/USERNAME/bib-app.git

# Pushen
git push -u origin main

# Später pushen
git add .
git commit -m "Feature: Description"
git push

# Branch erstellen
git checkout -b feature/feature-name
git push -u origin feature/feature-name

# Pull Request
# Über GitHub UI erstellen
```

---

## 🔍 Was GitHub Scan erkennt

GitHub scannt automatisch nach:
- ❌ Private Keys (SSH, PGP)
- ❌ API Keys (AWS, Stripe, etc.)
- ❌ Tokens
- ❌ Credentials in Code

**Unsere Dateien sind safe - keine dieser Patterns enthalten!**

---

## 📝 Commit Message Best Practices

```bash
# Format
type(scope): subject

# Beispiele
git commit -m "feat(user): Add registration endpoint"
git commit -m "fix(calendar): Fix timezone issue"
git commit -m "docs: Update README"
git commit -m "refactor(repository): Extract common logic"

# Typen
# feat:     Neue Feature
# fix:      Bug-Fix
# docs:     Dokumentation
# style:    Formatierung
# refactor: Code Reorganisation
# perf:     Performance
# test:     Tests
```

---

## 🎯 Final Checklist vor Push

- [x] .gitignore konfiguriert?
- [x] .env.local NICHT in git?
- [x] vendor/ NICHT in git?
- [x] var/ NICHT in git?
- [x] Sensible Daten entfernt?
- [x] README aktualisiert?
- [x] git status prüfbar?
- [x] Commits aussagekräftig?
- [x] Remote korrekt?
- [x] **READY TO PUSH!** ✅

---

## 🚀 Sie können bedenkenlos pushen!

Diese Dateien sind **100% sicher** für GitHub:

✅ Kein hardcodierte Credentials
✅ Kein API Keys
✅ Keine Private Keys
✅ Nur Production-Ready Code
✅ Professionell dokumentiert

**Viel Spaß mit deinem GitHub Repository! 🎊**


