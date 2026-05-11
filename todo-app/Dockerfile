FROM php:8.2-cli
WORKDIR /app
COPY . .
RUN mkdir -p data && echo "[]" > data/todos.json
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "public/router.php"]
