# Deploy Iyra Motors to your Cloudflare account

This is a React/Vinext application running on Cloudflare Workers, with D1 for inventory, dealership settings and inquiries, and a private R2 bucket for photos. No WordPress or Dealer Inspire subscription is required. It is a custom implementation inspired by the requested reference, not Dealer Inspire's proprietary platform.

The existing private preview uses Sites sign-in. Your independent deployment uses **Cloudflare Access** and validates the signature, audience, issuer, expiry, and administrator email on the server. The packaging script always selects Access mode. Never set `AUTH_MODE=sites` on an independently hosted Worker; Sites identity headers are only trusted behind the Sites dispatcher.

The independent deployment has been packaged and dry-run checked locally. Live Cloudflare Access login and deployment to your account require your Cloudflare account, domain, and Access application configuration.

## 1. Install and sign in

Use Node 22.13+ and the pnpm version in package.json. From the project folder:

```sh
pnpm install --frozen-lockfile
pnpm exec wrangler login
pnpm exec wrangler d1 create iyra-motors-inventory
pnpm exec wrangler r2 bucket create iyra-motors-photos
pnpm cloudflare:configure
```

Keep the R2 bucket private; do not enable an r2.dev URL or public bucket domain. Copy the D1 database ID returned by Cloudflare into `cloudflare.config.json`.

## 2. Configure administrator sign-in

In Cloudflare Zero Trust, create one **self-hosted Access application** for your dealership domain with both paths covered:

- `/admin` and its descendants
- `/api/admin` and its descendants

Use the same application and audience for both paths. Include the exact base paths as well as their descendants when configuring path matching. Leave the public website, `/api/inquiries`, and `/media/*` public; the application verifies authorization before serving draft images. Access's signed `CF_Authorization` cookie lets an authorized admin view draft photos.

Create an **Allow** policy restricted to your administrator email addresses. Choose email one-time PIN or your existing identity provider. Do not use an Everyone or Bypass policy. Copy the team domain and the application's **Application Audience (AUD) Tag** into `cloudflare.config.json`. Also set `adminEmails` to the same approved email addresses and `publicOrigin` to the exact HTTPS origin customers will visit, without a trailing slash. The app's own allowlist adds a second authorization check.

Official guides: [Access applications](https://developers.cloudflare.com/cloudflare-one/access-controls/applications/http-apps/), [JWT validation and audience tag](https://developers.cloudflare.com/cloudflare-one/access-controls/applications/http-apps/authorization-cookie/validating-json/).

## 3. Build and deploy

After all values in `cloudflare.config.json` are complete:

```sh
pnpm test
pnpm build
pnpm cloudflare:package
pnpm exec wrangler d1 migrations apply DB --remote --config cloudflare-dist/wrangler.json
pnpm exec wrangler deploy --config cloudflare-dist/wrangler.json
pnpm exec wrangler secret put RATE_LIMIT_SECRET --config cloudflare-dist/wrangler.json
```

For `RATE_LIMIT_SECRET`, generate a random 32-byte value using a password manager or a cryptographic random generator, and paste it into Wrangler's secret prompt. This secret hashes client addresses for short-lived submission rate limits; it is not an administrator password. Requests fail closed until it is configured. Do not commit secret values or send them in chat.

Attach your dealership domain to this Worker in Cloudflare's Workers dashboard, and make sure the Access application uses that same domain. Set `PUBLIC_ORIGIN` to the final domain; writes from another origin are rejected. Set a consistent canonical domain or redirect alternate hostnames to it.

## 4. Check the live site

Open your dealership domain, select **Dealer login**, and sign in with an approved email. Add a draft and upload a photo. Confirm it does not appear in an incognito public window, publish it, and confirm it appears. Submit one test contact request and find it in the inbox. Sign out and check that `/api/admin` never returns inventory drafts, inquiries, or customer details. Confirm an unapproved account cannot access the dashboard.

The dashboard's **View website** opens a new tab. Refresh that tab after edits. Adding vehicles and changing settings needs no rebuild or redeployment.

## Ongoing use

For code updates, repeat the build, package, migration, and deploy commands. Keep existing D1 and R2 bindings to retain data. D1 migrations are append-only; do not edit a migration already applied in production. Back up D1 and R2 according to your retention needs, and periodically delete inquiries you no longer need from the admin inbox. No automated email notifications, inventory-feed/DMS integration, or credit application service is connected.

Deployment to your own Cloudflare account starts with a new database and bucket. Data from the private preview does not transfer automatically. Add real stock on the intended final deployment, or plan a data export/import before switching.
