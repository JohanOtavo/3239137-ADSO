# Build the bundle, then serve the static files. The build tools do not ship:
# the running image is a web server and a folder of assets.
FROM node:20-alpine AS build

WORKDIR /app

# The lockfile is copied first so a dependency install is cached independently
# of a source change.
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Baked in at build time, because a static bundle has no server to read an
# environment variable at run time.
ARG VITE_API_URL=http://localhost:8000/api/v1
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

FROM nginx:1.27-alpine AS serve

COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
