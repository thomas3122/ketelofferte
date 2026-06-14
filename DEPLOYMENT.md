# Deploy en overdracht

## Documentroot

Zet de webserver documentroot op:

```text
public/
```

De applicatiecode staat buiten `public/`. Alleen publieke wrappers en assets staan in `public/`.

## Environment

Kopieer `.env.example` naar `.env` op de server en vul de juiste database- en mailgegevens in.

Commit `.env` niet naar GitHub. Deze staat in `.gitignore`.

## Database

Benodigde variabelen:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

## Uploads

Uploadbestanden horen in `storage/uploads/` en worden niet publiek direct geserveerd. Bestanden worden via gecontroleerde PHP-endpoints geopend.

Commit `storage/uploads/` niet naar GitHub.

## Beveiliging

Controleer voor livegang:

- `password_hash` / `password_verify`
- sessie-cookie met `HttpOnly`, `SameSite=Lax` en automatisch `Secure` op HTTPS
- CSRF-token op admin-, lead- en offerte-POST-acties
- honeypot op het publieke leadformulier tegen simpele spam
- security headers via `includes/bootstrap.php`
- `.env` en `storage/uploads/` staan niet in Git
- server gebruikt HTTPS en de documentroot staat op `public/`
