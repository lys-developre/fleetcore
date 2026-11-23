# syntax=docker/dockerfile:1.7
# FleetCore web — Next.js 16 on Node 24
FROM node:24-alpine AS base
RUN npm install -g pnpm@10 && apk add --no-cache libc6-compat
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

# ---- development: source bind-mounted, deps installed inside the container ----
FROM base AS dev
ENV NODE_ENV=development \
    WATCHPACK_POLLING=true \
    CHOKIDAR_USEPOLLING=true \
    npm_config_store_dir=/pnpm/store
COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev
RUN chmod +x /usr/local/bin/entrypoint-dev
EXPOSE 3000
ENTRYPOINT ["entrypoint-dev"]
CMD ["pnpm", "dev", "--hostname", "0.0.0.0"]

# ---- builder ----
FROM base AS builder
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
ARG NEXT_PUBLIC_API_URL=/api
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
# public/ may be absent in a fresh checkout (git does not track empty dirs); the prod stage copies it
RUN mkdir -p public && pnpm build

# ---- production: standalone output ----
FROM node:24-alpine AS prod
WORKDIR /app
ENV NODE_ENV=production NEXT_TELEMETRY_DISABLED=1 PORT=3000 HOSTNAME=0.0.0.0
RUN addgroup -S nodejs && adduser -S nextjs -G nodejs
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/public ./public
USER nextjs
EXPOSE 3000
CMD ["node", "server.js"]
