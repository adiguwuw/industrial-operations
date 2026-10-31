# Deploying the backend to Vercel

This repository has a Vercel-specific container configuration. It leaves the Docker Compose and Render files intact:

- `Dockerfile.vercel` runs Laravel through FrankenPHP.
- `Caddyfile.vercel` routes HTTP requests to Laravel and serves public uploads from the container's temporary storage.
- `vercel.json` selects Vercel's Container runtime.
- `vercel-entrypoint.sh` prepares writable temporary directories and the Aiven CA certificate.

## Vercel project setup

Import this backend repository as a separate Vercel project. Select the repository root as the project root and use the Hobby plan. The repository's `vercel.json` selects the Container framework; Vercel uses `Dockerfile.vercel`.

Add these Production environment variables in Vercel:

| Variable | Value |
| --- | --- |
| `APP_NAME` | `Industrial Operations` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate a new production key locally; never commit it |
| `APP_URL` | The backend's Vercel URL |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Values from the Aiven connection details |
| `MYSQL_CA_CERT_BASE64` | One-line base64 encoding of the downloaded Aiven CA certificate |

To encode the CA certificate locally in WSL, run `base64 -w0 /path/to/ca.pem`, then paste that output directly into the Vercel environment variable. Do not send certificate contents or database credentials in chat or commit them to Git.

The container defaults to array-backed sessions and cache, and synchronous queues. Those settings suit this token-authenticated portfolio demo and avoid writing runtime state to the read-only image.

## Database

Run Laravel migrations as a separate one-time deployment step after configuring the database. Do not run migrations during container startup. If the Aiven database already has all current migrations applied, do not rerun seeders or reset the database. Keep a backup before applying any schema changes.

## Uploaded files

Vercel's container filesystem is read-only except for `/tmp`, which has up to 500 MB of scratch space. This setup directs Laravel's local storage to `/tmp`, so photo uploads can work during a running instance, but they are not durable or shared across instances. Containers can scale down after five idle minutes; assume uploaded files may disappear when an instance scales down, is recycled, or is archived. The Aiven database remains separate and persistent. Add durable object storage before treating uploaded photos as permanent portfolio data.

Vercel Functions also limit each request body to 4.5 MB total. The task submission endpoint sends two photos in one request, so keep each photo around 2 MB or less and the complete request below 4.5 MB. Larger uploads need direct-to-object-storage handling.

## Frontend

Deploy the frontend as a separate Vercel project. Once the backend project has a URL, copy the frontend's `vercel.json.example` to `vercel.json` and replace `YOUR-VERCEL-API` with the backend hostname. See the frontend README for the rewrite setup.

## Limits

Vercel's Container runtime is in beta and runs as a Vercel Function. Hobby is free for personal projects and has monthly usage caps; exceeding a cap pauses that resource until it resets.
