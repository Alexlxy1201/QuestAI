# === Laravel + PHP 8.3 + OpenAI API (for Railway) ===
FROM php:8.3-fpm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libpng-dev libonig-dev libicu-dev && \
    docker-php-ext-install pdo pdo_mysql zip intl mbstring gd

# 工作目录
WORKDIR /app

# 复制项目文件
COPY . .

# 安装 Composer 依赖
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-dev --optimize-autoloader && \
    rm -f php composer.phar

# 修复权限
RUN chmod -R 775 storage bootstrap/cache

# 暴露 Railway 端口（Railway 会自动设置 $PORT）
EXPOSE 8080

# 🧠 启动命令 — 使用 $PORT 而不是固定 8080
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
