FROM node:24-alpine AS build
WORKDIR /app

# We copied the monorepo structure from the frontend
COPY package*.json ./
COPY apps/machine/package*.json ./apps/machine/
COPY apps/service/package*.json ./apps/service/

RUN npm ci

COPY . .
RUN npm run build --workspace apps/machine

FROM nginx:alpine
COPY --from=build /app/apps/machine/dist /usr/share/nginx/html
COPY apps/machine/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
