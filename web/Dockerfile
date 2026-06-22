FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ARG VITE_API_BASE=
ENV VITE_API_BASE=$VITE_API_BASE
ENV NODE_ENV=production

RUN npm run build

FROM alpine:3.19

COPY --from=build /app/dist /dist

CMD ["sh", "-c", "rm -rf /output/* /output/.[!.]* 2>/dev/null; cp -r /dist/production/. /output/ && cp -r /dist/assets /dist/js /dist/images /output/ && cp /dist/sw.js /dist/site.webmanifest /output/ 2>/dev/null || true; sleep infinity"]
