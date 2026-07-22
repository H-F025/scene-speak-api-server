FROM nginx:1.30

COPY .docker/production/nginx/nginx.conf /etc/nginx/nginx.conf

WORKDIR /app

COPY public ./public

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]